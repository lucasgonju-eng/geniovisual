// @vitest-environment node

import { execFileSync, spawn, type ChildProcessWithoutNullStreams } from "node:child_process";
import { createServer } from "node:net";
import { mkdtemp, readFile, rm, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import path from "node:path";
import { afterAll, beforeAll, describe, expect, it } from "vitest";

let phpServer: ChildProcessWithoutNullStreams;
let baseUrl: string;
let temporaryDirectory: string;
let configPath: string;
let promoPath: string;
let serverOutput = "";

const segments = [
  ["automotivo", "Automotivo"],
  ["imobiliario", "Imobiliário"],
  ["saude-odontologia", "Saúde e Odontologia"],
  ["educacao", "Educação"],
  ["alimentacao", "Alimentação e Restaurantes"],
  ["varejo-moda", "Varejo e Moda"],
  ["beleza-estetica", "Beleza e Estética"],
  ["academias", "Academias e Fitness"],
  ["financeiro", "Serviços Financeiros"],
  ["construcao", "Construção e Reforma"],
  ["tecnologia", "Tecnologia"],
  ["advocacia-contabilidade", "Advocacia e Contabilidade"],
  ["pet", "Pet"],
].map(([slug, nome], index) => ({
  slug,
  nome,
  ocupado: index < 3,
  cliente: `Cliente secreto ${index}`,
}));

const approvedPlans = [
  { slug: "mensal", nome: "Mensal", meses: 1, preco: 4_500, exclusividade: false },
  { slug: "trimestral", nome: "Trimestral", meses: 3, preco: 3_600, exclusividade: true },
  { slug: "semestral", nome: "Semestral", meses: 6, preco: 3_150, exclusividade: true },
  { slug: "anual", nome: "Anual", meses: 12, preco: 2_700, exclusividade: true, destaque: true },
];

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
  promoPath = path.join(temporaryDirectory, "promocao.json");
  baseUrl = `http://127.0.0.1:${port}`;
  phpServer = spawn("php", ["-S", `127.0.0.1:${port}`, "-t", "public"], {
    cwd: process.cwd(),
    env: {
      ...process.env,
      GENIO_PAINEL_CONFIG_PATH: configPath,
      GENIO_PROMO_CONFIG_PATH: promoPath,
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
    expect(response.headers.get("cache-control")).toBe("no-store, no-cache, must-revalidate");
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
      segmentos: [],
      segmentos_livres: 0,
      segmentos_consistente: false,
      planos: [],
      preco_a_partir_de: null,
      promocao: null,
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
      segmentos: [],
      segmentos_livres: 0,
      segmentos_consistente: false,
      planos: [],
      preco_a_partir_de: null,
      promocao: null,
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

  it("publica disponibilidade sem dados de clientes e sem confundir categorias com vagas", async () => {
    await writeFile(
      configPath,
      JSON.stringify({
        anunciantes_regulares: 3,
        einstein_intercalado: true,
        duracao_segundos: 10,
        horas_por_dia: 18,
        vagas_totais: 12,
        segmentos: segments,
        atualizado_em: "2026-08-02",
      }),
      "utf8",
    );

    const response = await fetch(`${baseUrl}/painel-status.php`);
    const payload = await response.json();
    expect(payload.segmentos).toHaveLength(13);
    expect(payload.segmentos_livres).toBe(10);
    expect(payload.vagas_restantes).toBe(9);
    expect(payload.segmentos_consistente).toBe(true);
    expect(payload.segmentos[0]).toEqual({
      slug: "automotivo",
      nome: "Automotivo",
      ocupado: true,
    });
    expect(JSON.stringify(payload)).not.toContain("Cliente secreto");
  });

  it("marca inconsistência quando há menos segmentos ocupados que anunciantes", async () => {
    await writeFile(
      configPath,
      JSON.stringify({
        anunciantes_regulares: 3,
        einstein_intercalado: true,
        duracao_segundos: 10,
        horas_por_dia: 18,
        vagas_totais: 12,
        segmentos: segments.map((segment, index) => ({
          ...segment,
          ocupado: index < 2,
        })),
        atualizado_em: "2026-08-02",
      }),
      "utf8",
    );

    const response = await fetch(`${baseUrl}/painel-status.php`);
    const payload = await response.json();
    expect(payload.segmentos_consistente).toBe(false);
    expect(payload.segmentos_livres).toBe(11);
  });

  it("considera apenas anunciantes com exclusividade na consistência dos segmentos", async () => {
    await writeFile(
      configPath,
      JSON.stringify({
        anunciantes_regulares: 3,
        anunciantes_com_exclusividade: 2,
        einstein_intercalado: true,
        duracao_segundos: 10,
        horas_por_dia: 18,
        vagas_totais: 12,
        segmentos: segments.map((segment, index) => ({
          ...segment,
          ocupado: index < 2,
        })),
        atualizado_em: "2026-08-02",
      }),
      "utf8",
    );

    const response = await fetch(`${baseUrl}/painel-status.php`);
    const payload = await response.json();
    expect(payload.segmentos_consistente).toBe(true);
    expect(payload.aparicoes_hora).toBe(60);
    expect(payload.aparicoes_hora_min).toBe(15);
  });

  it("publica a tabela operacional e calcula o menor preço vigente", async () => {
    await writeFile(
      configPath,
      JSON.stringify({
        anunciantes_regulares: 3,
        anunciantes_com_exclusividade: 2,
        einstein_intercalado: true,
        duracao_segundos: 10,
        horas_por_dia: 18,
        vagas_totais: 12,
        segmentos: [],
        planos: approvedPlans,
        preco_minimo: 2_500,
        atualizado_em: "2026-08-02",
      }),
      "utf8",
    );

    const response = await fetch(`${baseUrl}/painel-status.php`);
    const payload = await response.json();
    expect(payload.preco_a_partir_de).toBe(2_700);
    expect(payload.planos).toHaveLength(4);
    expect(payload.planos.map((plan: { preco_efetivo: number }) => plan.preco_efetivo)).toEqual([
      4_500,
      3_600,
      3_150,
      2_700,
    ]);
    expect(payload.planos[3]).toMatchObject({
      slug: "anual",
      em_campanha: false,
      exclusividade: true,
      destaque: true,
    });
    expect(JSON.stringify(payload)).not.toContain("preco_minimo");
  });

  it("normaliza a promoção isolada e oculta ofertas expiradas ou esgotadas", () => {
    const script = `
      require 'public/lib/painel.php';
      $active = [...promo_default(), 'ativa' => true];
      $normalized = promo_normalize([
        ...$active,
        'rotulo' => str_repeat('x', 140),
        'preco_total' => -1,
        'limite_vagas' => 3,
        'vagas_restantes' => 9,
        'validade' => 'data-invalida',
      ]);
      echo json_encode([
        'normalized' => $normalized,
        'active' => promo_public_view($active, '2026-08-02'),
        'expired' => promo_public_view([...$active, 'validade' => '2026-08-01'], '2026-08-02'),
        'sold_out' => promo_public_view([...$active, 'vagas_restantes' => 0], '2026-08-02'),
        'disabled' => promo_public_view(promo_default(), '2026-08-02'),
      ], JSON_UNESCAPED_UNICODE);
    `;
    const result = JSON.parse(
      execFileSync("php", ["-r", script], { cwd: process.cwd(), encoding: "utf8" }),
    );

    expect(result.normalized.rotulo).toHaveLength(120);
    expect(result.normalized.preco_total).toBe(0);
    expect(result.normalized.vagas_restantes).toBe(3);
    expect(result.normalized.validade).toBe("");
    expect(result.active).toMatchObject({
      rotulo: "Promoção Relâmpago — 5 primeiros",
      preco_total: 5_760,
      equivalente_mensal: 1_920,
      vagas_restantes: 5,
    });
    expect(result.active).not.toHaveProperty("ativa");
    expect(result.active).not.toHaveProperty("limite_vagas");
    expect(result.active).not.toHaveProperty("atualizado_em");
    expect(result.expired).toBeNull();
    expect(result.sold_out).toBeNull();
    expect(result.disabled).toBeNull();
  });

  it("grava a promoção sem alterar painel-config e publica o toggle sem deploy", async () => {
    const panelBefore = await readFile(configPath, "utf8");
    const script = `
      require 'public/lib/painel.php';
      promo_write([
        ...promo_default(),
        'ativa' => true,
        'vagas_restantes' => 4,
        'validade' => '2099-08-31',
        'atualizado_em' => '2026-08-02T21:00:00-03:00',
      ]);
    `;
    execFileSync("php", ["-r", script], {
      cwd: process.cwd(),
      env: { ...process.env, GENIO_PROMO_CONFIG_PATH: promoPath },
    });

    expect(await readFile(configPath, "utf8")).toBe(panelBefore);
    const stored = JSON.parse(await readFile(promoPath, "utf8"));
    expect(stored).toMatchObject({ ativa: true, vagas_restantes: 4 });

    const activeResponse = await fetch(`${baseUrl}/painel-status.php`);
    const activePayload = await activeResponse.json();
    expect(activePayload.promocao).toMatchObject({
      rotulo: "Promoção Relâmpago — 5 primeiros",
      preco_total: 5_760,
      vagas_restantes: 4,
      validade: "2099-08-31",
    });
    expect(JSON.stringify(activePayload.promocao)).not.toMatch(/ativa|limite_vagas|atualizado_em/i);

    await writeFile(promoPath, JSON.stringify({ ...stored, ativa: false }), "utf8");
    const disabledResponse = await fetch(`${baseUrl}/painel-status.php`);
    await expect(disabledResponse.json()).resolves.toMatchObject({ promocao: null });
  });

  it("ativa campanha somente até a validade e restaura o preço cheio depois", () => {
    const script = `
      require 'public/lib/painel.php';
      $config = painel_default_config();
      $config['preco_minimo'] = 2500;
      $config['planos'] = [[
        'slug' => 'trimestral',
        'nome' => 'Trimestral',
        'meses' => 3,
        'preco' => 3600,
        'exclusividade' => true,
        'destaque' => false,
        'campanha' => [
          'preco_promocional' => 2700,
          'rotulo' => 'Trimestral pelo preço do anual',
          'validade' => '2026-08-31',
        ],
      ]];
      echo json_encode([
        'active' => painel_calculate_pricing($config, '2026-08-31'),
        'expired' => painel_calculate_pricing($config, '2026-09-01'),
        'invalid_date' => painel_calculate_pricing([
          ...$config,
          'planos' => [[
            ...$config['planos'][0],
            'campanha' => [
              ...$config['planos'][0]['campanha'],
              'validade' => 'sem-data',
            ],
          ]],
        ], '2026-08-01'),
      ], JSON_UNESCAPED_UNICODE);
    `;
    const result = JSON.parse(
      execFileSync("php", ["-r", script], { cwd: process.cwd(), encoding: "utf8" }),
    );

    expect(result.active.preco_a_partir_de).toBe(2_700);
    expect(result.active.planos[0]).toMatchObject({
      preco: 3_600,
      preco_efetivo: 2_700,
      em_campanha: true,
      validade: "2026-08-31",
    });
    expect(result.expired.preco_a_partir_de).toBe(3_600);
    expect(result.expired.planos[0]).toMatchObject({
      preco: 3_600,
      preco_efetivo: 3_600,
      em_campanha: false,
      rotulo: null,
      validade: null,
    });
    expect(result.invalid_date.planos[0]).toMatchObject({
      preco_efetivo: 3_600,
      em_campanha: false,
    });
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
      "planos",
      "preco_a_partir_de",
      "promocao",
      "segmentos",
      "segmentos_consistente",
      "segmentos_livres",
      "tela_dia_min_minutos",
      "tela_dia_minutos",
      "vagas_restantes",
      "vagas_totais",
    ]);
    expect(JSON.stringify(payload)).not.toMatch(/einstein|atualizado|cliente|empresa|email|telefone/i);

    const postResponse = await fetch(`${baseUrl}/painel-status.php`, { method: "POST" });
    expect(postResponse.status).toBe(405);
    expect(postResponse.headers.get("allow")).toBe("GET");
  });

  it("validação usada pelo admin bloqueia lotação inválida e calcula o alerta", () => {
    const script = `
      require 'public/lib/painel.php';
      $invalid = painel_validate_admin_config([
        'anunciantes_regulares' => '13',
        'anunciantes_com_exclusividade' => '13',
        'einstein_intercalado' => true,
        'duracao_segundos' => '10',
        'horas_por_dia' => '18',
        'vagas_totais' => '12',
      ]);
      $invalidExclusive = painel_validate_admin_config([
        'anunciantes_regulares' => '3',
        'anunciantes_com_exclusividade' => '4',
        'einstein_intercalado' => true,
        'duracao_segundos' => '10',
        'horas_por_dia' => '18',
        'vagas_totais' => '12',
      ]);
      $current = painel_default_config();
      $increase = [...$current, 'vagas_totais' => 13];
      $reduction = [...$current, 'vagas_totais' => 11];
      $tooManySegments = painel_default_segments();
      $partialSegments = painel_default_segments();
      foreach ($tooManySegments as $index => &$segment) {
        $segment['ocupado'] = $index < 3;
      }
      unset($segment);
      foreach ($partialSegments as $index => &$segment) {
        $segment['ocupado'] = $index < 1;
      }
      unset($segment);
      $invalidSegments = painel_validate_segments_config($tooManySegments, 2);
      $partial = painel_validate_segments_config($partialSegments, 2);
      $monthlyCompatible = painel_validate_segments_config(
        array_map(
          static fn(array $segment, int $index): array => [...$segment, 'ocupado' => $index < 2],
          painel_default_segments(),
          array_keys(painel_default_segments())
        ),
        2
      );
      $belowFloor = painel_validate_pricing_config([[
        'slug' => 'mensal',
        'nome' => 'Mensal',
        'meses' => 1,
        'preco' => 2499,
        'exclusividade' => false,
      ]], 2500);
      $unsafeConfig = painel_default_config();
      $unsafeConfig['planos'] = [[
        'slug' => 'mensal',
        'nome' => 'Mensal',
        'meses' => 1,
        'preco' => 2499,
        'exclusividade' => false,
      ]];
      $unsafePublic = painel_calculate_pricing($unsafeConfig, '2026-08-02');
      $campaignWithoutDate = painel_validate_pricing_config([[
        'slug' => 'trimestral',
        'nome' => 'Trimestral',
        'meses' => 3,
        'preco' => 3600,
        'exclusividade' => true,
        'campanha' => [
          'preco_promocional' => 2700,
          'rotulo' => 'Oferta real',
          'validade' => '',
        ],
      ]], 2500);
      echo json_encode([
        'errors' => $invalid['errors'],
        'exclusive_errors' => $invalidExclusive['errors'],
        'increase_warning' => painel_ceiling_increase_warning($current, $increase),
        'reduction_warning' => painel_ceiling_increase_warning($current, $reduction),
        'segment_errors' => $invalidSegments['errors'],
        'partial_consistent' => $partial['consistent'],
        'partial_warning' => painel_segments_warning(2, $partial['occupied']),
        'monthly_compatible' => $monthlyCompatible['consistent'],
        'below_floor_errors' => $belowFloor['errors'],
        'unsafe_public' => $unsafePublic,
        'campaign_errors' => $campaignWithoutDate['errors'],
      ], JSON_UNESCAPED_UNICODE);
    `;
    const result = JSON.parse(
      execFileSync("php", ["-r", script], { cwd: process.cwd(), encoding: "utf8" }),
    );

    expect(result.errors).toContain(
      "Não é permitido salvar: anunciantes regulares não pode ser maior que vagas totais.",
    );
    expect(result.exclusive_errors).toContain(
      "Não é permitido salvar: anunciantes com exclusividade não pode ser maior que anunciantes regulares.",
    );
    expect(result.increase_warning).toBe(
      "Aumentar o teto reduz a frequência garantida de todos os contratos vigentes. Piso atual: 15/hora. Piso após a mudança: 14/hora.",
    );
    expect(result.reduction_warning).toBeNull();
    expect(result.segment_errors).toContain(
      "Não é permitido salvar: existem 3 segmentos ocupados para 2 anunciantes com exclusividade.",
    );
    expect(result.partial_consistent).toBe(false);
    expect(result.partial_warning).toBe(
      "Você tem 2 anunciantes com exclusividade e 1 segmento marcado. Ou algum anunciante com exclusividade está sem segmento atribuído, ou dois dividem a mesma categoria — o que contraria a exclusividade vendida.",
    );
    expect(result.monthly_compatible).toBe(true);
    expect(result.below_floor_errors).toContain(
      "O preço do plano Mensal não pode ser inferior ao piso de R$ 2.500.",
    );
    expect(result.unsafe_public).toEqual({ planos: [], preco_a_partir_de: null });
    expect(result.campaign_errors).toContain(
      "Informe uma validade válida para a campanha do plano Trimestral.",
    );
  });
});
