// @vitest-environment node

import { spawn, type ChildProcessWithoutNullStreams } from "node:child_process";
import { createServer } from "node:net";
import { mkdtemp, readFile, rm } from "node:fs/promises";
import { tmpdir } from "node:os";
import path from "node:path";
import { afterAll, beforeAll, describe, expect, it } from "vitest";

type Lead = {
  email: string;
  whatsapp: string;
  segmento: string;
  email_status: string;
};

let phpServer: ChildProcessWithoutNullStreams;
let baseUrl: string;
let dataDir: string;
let serverOutput = "";

const getFreePort = () =>
  new Promise<number>((resolve, reject) => {
    const server = createServer();
    server.on("error", reject);
    server.listen(0, "127.0.0.1", () => {
      const address = server.address();
      if (!address || typeof address === "string") {
        reject(new Error("Não foi possível reservar uma porta."));
        return;
      }
      server.close(() => resolve(address.port));
    });
  });

const postJson = (payload: Record<string, unknown>) =>
  fetch(`${baseUrl}/send.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

const validPayload = (suffix = "1") => ({
  nome: `Lead Teste ${suffix}`,
  email: `lead${suffix}@example.com`,
  whatsapp: `62999990${suffix.padStart(3, "0")}`,
  empresa: "Empresa Teste",
  segmento: "Imobiliário",
  plano: "Plano Teste",
  mensagem: "Mensagem de teste",
  consent: true,
});

const readLeads = async (): Promise<Lead[]> => {
  try {
    return JSON.parse(await readFile(path.join(dataDir, "leads.json"), "utf8"));
  } catch {
    return [];
  }
};

beforeAll(async () => {
  const port = await getFreePort();
  dataDir = await mkdtemp(path.join(tmpdir(), "gv-send-hardening-"));
  baseUrl = `http://127.0.0.1:${port}`;
  phpServer = spawn("php", ["-S", `127.0.0.1:${port}`, "-t", "public"], {
    cwd: process.cwd(),
    env: {
      ...process.env,
      GENIO_CRM_DATA_DIR: dataDir,
      GENIO_DISABLE_MAIL: "1",
    },
  });
  phpServer.stdout.on("data", (chunk) => {
    serverOutput += chunk.toString();
  });
  phpServer.stderr.on("data", (chunk) => {
    serverOutput += chunk.toString();
  });

  for (let attempt = 0; attempt < 40; attempt += 1) {
    try {
      const response = await fetch(`${baseUrl}/send.php`);
      if (response.status === 405) {
        return;
      }
    } catch {
      await new Promise((resolve) => setTimeout(resolve, 100));
    }
  }
  throw new Error(`Servidor PHP não iniciou.\n${serverOutput}`);
}, 15_000);

afterAll(async () => {
  phpServer?.kill();
  await rm(dataDir, { recursive: true, force: true });
});

describe("hardening do send.php", () => {
  it("rejeita Content-Type, e-mail, WhatsApp e payload fora dos limites", async () => {
    const wrongType = await fetch(`${baseUrl}/send.php`, {
      method: "POST",
      headers: { "Content-Type": "text/plain" },
      body: "{}",
    });
    expect(wrongType.status).toBe(415);

    const invalidEmail = await postJson({ ...validPayload("1"), email: "invalido" });
    expect(invalidEmail.status).toBe(422);

    const invalidWhatsapp = await postJson({ ...validPayload("1"), whatsapp: "123" });
    expect(invalidWhatsapp.status).toBe(422);

    const missingSegment = await postJson({ ...validPayload("1"), segmento: "" });
    expect(missingSegment.status).toBe(422);

    const oversized = await fetch(`${baseUrl}/send.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ padding: "x".repeat(17_000) }),
    });
    expect(oversized.status).toBe(413);
  });

  it("ignora honeypot sem gravar ou consumir o limite", async () => {
    const response = await postJson({ ...validPayload("1"), website: "https://spam.example" });
    expect(response.status).toBe(200);
    expect(await readLeads()).toHaveLength(0);
  });

  it("grava lead legítimo, normaliza WhatsApp e deduplica em 60 segundos", async () => {
    const payload = {
      ...validPayload("1"),
      whatsapp: "(62) 99999-0001",
      empresa: "<img src=x onerror=alert(1)>",
      plano: "<script>alert(1)</script>",
    };
    const first = await postJson(payload);
    expect(first.status).toBe(200);

    const leadsAfterFirst = await readLeads();
    expect(leadsAfterFirst).toHaveLength(1);
    expect(leadsAfterFirst[0]).toMatchObject({
      email: "lead1@example.com",
      whatsapp: "62999990001",
      segmento: "Imobiliário",
      email_status: "failed",
    });

    const duplicate = await postJson(payload);
    expect(duplicate.status).toBe(200);
    expect(await readLeads()).toHaveLength(1);
  });

  it("bloqueia o sexto envio por IP dentro de uma hora", async () => {
    for (const suffix of ["2", "3", "4", "5"]) {
      const response = await postJson(validPayload(suffix));
      expect(response.status).toBe(200);
    }

    const sixth = await postJson(validPayload("6"));
    expect(sixth.status).toBe(429);
    expect(await readLeads()).toHaveLength(5);

    const rateLimit = JSON.parse(
      await readFile(path.join(dataDir, "ratelimit.json"), "utf8"),
    ) as Record<string, number[]>;
    expect(Object.keys(rateLimit)).toHaveLength(1);
    expect(Object.keys(rateLimit)[0]).toMatch(/^[a-f0-9]{64}$/);
  });

  it("registra erros sem e-mail, WhatsApp ou IP completo", async () => {
    const lines = (await readFile(path.join(dataDir, "errors.log"), "utf8"))
      .trim()
      .split("\n")
      .map((line) => JSON.parse(line));

    expect(lines.length).toBeGreaterThan(0);
    for (const entry of lines) {
      expect(Object.keys(entry).sort()).toEqual(["ip", "timestamp", "type"]);
      expect(entry.ip).toBe("127.0.0.x");
    }
    const serialized = JSON.stringify(lines);
    expect(serialized).not.toContain("@example.com");
    expect(serialized).not.toContain("6299999");
  });

  it("escapa todos os campos do usuário interpolados no HTML", async () => {
    const source = await readFile(path.join(process.cwd(), "public", "send.php"), "utf8");
    expect(source).toContain("$safeFirstName = $escapeHtml($firstName)");
    expect(source).toContain("$safePlano = $escapeHtml($plano)");
    expect(source).toContain("$safeEmpresa = $escapeHtml($empresa)");
    expect(source).toContain("$safeSegmento = $escapeHtml($segmento)");
    expect(source).toContain("$safeEmail = $escapeHtml($email)");
    expect(source).not.toContain(">{$plano}<");
    expect(source).not.toContain(">{$empresa}<");
    expect(source).not.toContain(">{$segmento}<");
    expect(source).not.toContain(">{$email}<");
    expect(source).not.toContain(">{$firstName}!");
  });
});
