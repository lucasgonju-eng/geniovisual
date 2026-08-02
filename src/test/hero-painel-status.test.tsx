import { render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import Hero from "@/components/Hero";

afterEach(() => {
  vi.unstubAllGlobals();
});

describe("status operacional no Hero", () => {
  it("renderiza fallback conservador quando o endpoint falha", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("offline")));

    render(<Hero />);

    expect(screen.getByText("9", { exact: true })).toBeInTheDocument();
    expect(screen.getByText("6", { exact: true })).toBeInTheDocument();
    expect(
      screen.getByText("Sua marca: 360 aparições por dia — 1 hora de tela, todo dia."),
    ).toBeInTheDocument();
    expect(screen.queryByText("—")).not.toBeInTheDocument();
  });

  it("atualiza a landing com os valores retornados pelo endpoint", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({
          anunciantes: 10,
          vagas_totais: 15,
          vagas_restantes: 5,
          aparicoes_hora: 18,
          aparicoes_dia: 324,
          aparicoes_mes: 9_720,
          tela_dia_minutos: 54,
          ciclo_segundos: 200,
          duracao_segundos: 10,
        }),
      }),
    );

    render(<Hero />);

    await waitFor(() => {
      expect(
        screen.getByText("Sua marca: 324 aparições por dia — 54 minutos de tela, todo dia."),
      ).toBeInTheDocument();
    });
    expect(screen.getByText("No mínimo 18 aparições por hora.")).toBeInTheDocument();
    expect(screen.getByText("5", { exact: true })).toBeInTheDocument();
  });
});
