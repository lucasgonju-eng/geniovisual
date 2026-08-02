import { render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import Formulario from "@/components/Formulario";
import Planos from "@/components/Planos";
import Vantagens from "@/components/Vantagens";

const painelStatus = {
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
};

afterEach(() => {
  vi.unstubAllGlobals();
});

describe("copy operacional da landing", () => {
  it("usa o teto do endpoint e mantém exatamente três planos", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => painelStatus,
    });
    vi.stubGlobal("fetch", fetchMock);

    render(
      <>
        <Vantagens />
        <Planos />
        <Formulario />
      </>,
    );

    await waitFor(() => {
      expect(
        screen.getByText(/rodízio limitado a 12 anunciantes/i),
      ).toBeInTheDocument();
    });
    expect(fetchMock).toHaveBeenCalledOnce();

    const planNames = ["Trimestral", "Semestral", "Anual"];
    expect(
      screen.getAllByRole("heading", { level: 3 }).filter((heading) =>
        planNames.includes(heading.textContent ?? ""),
      ),
    ).toHaveLength(3);
    expect(screen.getAllByText(/frequência garantida: 15 aparições\/hora/i)).toHaveLength(3);
    expect(screen.queryByText(/prioridade.*rodízio/i)).not.toBeInTheDocument();

    const planSelect = screen.getByRole("combobox", { name: /plano desejado/i });
    expect(planSelect.querySelectorAll("option")).toHaveLength(4);
    expect(screen.getByPlaceholderText(/imobiliário, saúde, alimentação/i)).toBeRequired();
  });
});
