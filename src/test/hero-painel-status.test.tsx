import { render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import Hero from "@/components/Hero";

afterEach(() => {
  vi.unstubAllGlobals();
});

describe("status operacional no Hero", () => {
  it("renderiza somente o piso quando o endpoint falha", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("offline")));

    render(<Hero />);

    expect(
      screen.getByText("15 aparições por hora, garantidas em contrato"),
    ).toBeInTheDocument();
    expect(
      screen.getByText("45 minutos de tela por dia — o mínimo, mesmo com o painel lotado."),
    ).toBeInTheDocument();
    expect(screen.queryByText("Vantagem de entrar agora")).not.toBeInTheDocument();
    expect(screen.queryByText(/restam \d+ vagas/i)).not.toBeInTheDocument();
    expect(screen.getByText("Valor mensal sob consulta.")).toBeInTheDocument();
    expect(
      screen.getByText("Direto com o proprietário, sem agência e sem comissão."),
    ).toBeInTheDocument();
  });

  it("separa garantia da vantagem atual retornada pelo endpoint", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({
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
          planos: [{
            slug: "anual",
            nome: "Anual",
            meses: 12,
            preco: 2_700,
            preco_efetivo: 2_700,
            em_campanha: false,
            rotulo: null,
            validade: null,
            exclusividade: true,
            destaque: true,
          }],
          preco_a_partir_de: 2_700,
        }),
      }),
    );

    render(<Hero />);

    await waitFor(() => {
      expect(
        screen.getByText(
          "Hoje, com apenas 3 anunciantes, sua marca receberia 60 por hora e 180 minutos de tela por dia.",
        ),
      ).toBeInTheDocument();
    });
    expect(
      screen.getByText("15 aparições por hora, garantidas em contrato"),
    ).toBeInTheDocument();
    expect(screen.getByText("Restam 9 vagas.")).toBeInTheDocument();
    expect(screen.getByText("A partir de R$ 2.700 por mês.")).toBeInTheDocument();
  });
});
