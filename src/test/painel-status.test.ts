// @vitest-environment node

import { spawn, type ChildProcessWithoutNullStreams } from "node:child_process";
import { createServer } from "node:net";
import { mkdtemp, rm, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import path from "node:path";
import { afterAll, beforeAll, describe, expect, it } from "vitest";

let phpServer: ChildProcessWithoutNullStreams;
let baseUrl: string;
let temporaryDirectory: string;
let configPath: string;
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

beforeAll(async () => {
  const port = await getFreePort();
  temporaryDirectory = await mkdtemp(path.join(tmpdir(), "gv-painel-status-"));
  configPath = path.join(temporaryDirectory, "painel-config.json");
  baseUrl = `http://127.0.0.1:${port}`;
  phpServer = spawn("php", ["-S", `127.0.0.1:${port}`, "-t", "public"], {
    cwd: process.cwd(),
    env: {
      ...process.env,
      GENIO_PAINEL_CONFIG_PATH: configPath,
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
      const response = await fetch(`${baseUrl}/painel-status.php`);
      if (response.ok) return;
    } catch {
      await new Promise((resolve) => setTimeout(resolve, 100));
    }
  }
  throw new Error(`Servidor PHP não iniciou.\n${serverOutput}`);
}, 15_000);

afterAll(async () => {
  phpServer?.kill();
  await rm(temporaryDirectory, { recursive: true, force: true });
});

describe("painel-status.php", () => {
  it("usa defaults conservadores quando o arquivo privado não existe", async () => {
    const response = await fetch(`${baseUrl}/painel-status.php`);
    expect(response.status).toBe(200);
    expect(response.headers.get("cache-control")).toBe("public, max-age=300");
    await expect(response.json()).resolves.toEqual({
      anunciantes: 9,
      vagas_totais: 15,
      vagas_restantes: 6,
      aparicoes_hora: 20,
      aparicoes_dia: 360,
      aparicoes_mes: 10_800,
      tela_dia_minutos: 60,
      ciclo_segundos: 180,
      duracao_segundos: 10,
    });
  });

  it("reflete uma alteração privada sem novo build", async () => {
    await writeFile(
      configPath,
      JSON.stringify({
        anunciantes_regulares: 10,
        einstein_intercalado: true,
        duracao_segundos: 10,
        horas_por_dia: 18,
        vagas_totais: 15,
        atualizado_em: "2026-08-02",
      }),
      "utf8",
    );

    const response = await fetch(`${baseUrl}/painel-status.php`);
    await expect(response.json()).resolves.toEqual({
      anunciantes: 10,
      vagas_totais: 15,
      vagas_restantes: 5,
      aparicoes_hora: 18,
      aparicoes_dia: 324,
      aparicoes_mes: 9_720,
      tela_dia_minutos: 54,
      ciclo_segundos: 200,
      duracao_segundos: 10,
    });
  });

  it("não expõe configuração interna nem aceita POST", async () => {
    const getResponse = await fetch(`${baseUrl}/painel-status.php`);
    const payload = await getResponse.json();
    expect(Object.keys(payload).sort()).toEqual([
      "anunciantes",
      "aparicoes_dia",
      "aparicoes_hora",
      "aparicoes_mes",
      "ciclo_segundos",
      "duracao_segundos",
      "tela_dia_minutos",
      "vagas_restantes",
      "vagas_totais",
    ]);
    expect(JSON.stringify(payload)).not.toMatch(/einstein|atualizado|cliente|preco|nome/i);

    const postResponse = await fetch(`${baseUrl}/painel-status.php`, { method: "POST" });
    expect(postResponse.status).toBe(405);
    expect(postResponse.headers.get("allow")).toBe("GET");
  });
});
