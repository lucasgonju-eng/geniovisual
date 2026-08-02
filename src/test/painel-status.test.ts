// @vitest-environment node

import { execFileSync, spawn, type ChildProcessWithoutNullStreams } from "node:child_process";
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
      anunciantes: 3,
      vagas_totais: 12,
      vagas_restantes: 9,
      aparicoes_hora: 60,
      aparicoes_dia: 1_080,
      aparicoes_mes: 32_400,
      tela_dia_minutos: 180,
      ciclo_segundos: 60,
      duracao_segundos: 10,
      aparicoes_hora_min: 15,
      aparicoes_dia_min: 270,
      aparicoes_mes_min: 8_100,
      tela_dia_min_minutos: 45,
      ciclo_max_segundos: 240,
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
      aparicoes_hora_min: 12,
      aparicoes_dia_min: 216,
      aparicoes_mes_min: 6_480,
      tela_dia_min_minutos: 36,
      ciclo_max_segundos: 300,
    });
  });

  it("calcula o piso sem intercalação usando doze slots", async () => {
    await writeFile(
      configPath,
      JSON.stringify({
        anunciantes_regulares: 3,
        einstein_intercalado: false,
        duracao_segundos: 10,
        horas_por_dia: 18,
        vagas_totais: 12,
        atualizado_em: "2026-08-02",
      }),
      "utf8",
    );

    const response = await fetch(`${baseUrl}/painel-status.php`);
    const payload = await response.json();
    expect(payload.ciclo_max_segundos).toBe(120);
    expect(payload.aparicoes_hora_min).toBe(30);
    expect(payload.tela_dia_min_minutos).toBe(90);
  });

  it("mantém o piso menor ou igual à entrega atual até a lotação", async () => {
    for (const advertisers of [3, 6, 9, 12]) {
      await writeFile(
        configPath,
        JSON.stringify({
          anunciantes_regulares: advertisers,
          einstein_intercalado: true,
          duracao_segundos: 10,
          horas_por_dia: 18,
          vagas_totais: 12,
          atualizado_em: "2026-08-02",
        }),
        "utf8",
      );
      const response = await fetch(`${baseUrl}/painel-status.php`);
      const payload = await response.json();
      expect(payload.aparicoes_hora_min).toBeLessThanOrEqual(payload.aparicoes_hora);
      expect(payload.tela_dia_min_minutos).toBeLessThanOrEqual(payload.tela_dia_minutos);
    }
  });

  it("não expõe configuração interna nem aceita POST", async () => {
    const getResponse = await fetch(`${baseUrl}/painel-status.php`);
    const payload = await getResponse.json();
    expect(Object.keys(payload).sort()).toEqual([
      "anunciantes",
      "aparicoes_dia",
      "aparicoes_dia_min",
      "aparicoes_hora",
      "aparicoes_hora_min",
      "aparicoes_mes",
      "aparicoes_mes_min",
      "ciclo_max_segundos",
      "ciclo_segundos",
      "duracao_segundos",
      "tela_dia_min_minutos",
      "tela_dia_minutos",
      "vagas_restantes",
      "vagas_totais",
    ]);
    expect(JSON.stringify(payload)).not.toMatch(/einstein|atualizado|cliente|preco|nome/i);

    const postResponse = await fetch(`${baseUrl}/painel-status.php`, { method: "POST" });
    expect(postResponse.status).toBe(405);
    expect(postResponse.headers.get("allow")).toBe("GET");
  });

  it("validação usada pelo admin bloqueia lotação inválida e calcula o alerta", () => {
    const script = `
      require 'public/lib/painel.php';
      $invalid = painel_validate_admin_config([
        'anunciantes_regulares' => '13',
        'einstein_intercalado' => true,
        'duracao_segundos' => '10',
        'horas_por_dia' => '18',
        'vagas_totais' => '12',
      ]);
      $current = painel_default_config();
      $increase = [...$current, 'vagas_totais' => 13];
      $reduction = [...$current, 'vagas_totais' => 11];
      echo json_encode([
        'errors' => $invalid['errors'],
        'increase_warning' => painel_ceiling_increase_warning($current, $increase),
        'reduction_warning' => painel_ceiling_increase_warning($current, $reduction),
      ], JSON_UNESCAPED_UNICODE);
    `;
    const result = JSON.parse(
      execFileSync("php", ["-r", script], { cwd: process.cwd(), encoding: "utf8" }),
    );

    expect(result.errors).toContain(
      "Não é permitido salvar: anunciantes regulares não pode ser maior que vagas totais.",
    );
    expect(result.increase_warning).toBe(
      "Aumentar o teto reduz a frequência garantida de todos os contratos vigentes. Piso atual: 15/hora. Piso após a mudança: 14/hora.",
    );
    expect(result.reduction_warning).toBeNull();
  });
});
