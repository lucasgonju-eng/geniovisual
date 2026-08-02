import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import Formulario from "@/components/Formulario";
import Exclusividade from "@/components/Exclusividade";
import Planos from "@/components/Planos";
import Vantagens from "@/components/Vantagens";
import { DEFAULT_SEGMENTS } from "@/lib/segments";

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
  segmentos: DEFAULT_SEGMENTS.map((segment, index) => ({
    ...segment,
    ocupado: index < 3,
  })),
  segmentos_livres: 10,
  segmentos_consistente: true,
  planos: [
    { slug: "mensal", nome: "Mensal", meses: 1, preco: 4_500, preco_efetivo: 4_500, em_campanha: false, rotulo: null, validade: null, exclusividade: false, destaque: false },
    { slug: "trimestral", nome: "Trimestral", meses: 3, preco: 3_600, preco_efetivo: 3_600, em_campanha: false, rotulo: null, validade: null, exclusividade: true, destaque: false },
    { slug: "semestral", nome: "Semestral", meses: 6, preco: 3_150, preco_efetivo: 3_150, em_campanha: false, rotulo: null, validade: null, exclusividade: true, destaque: false },
    { slug: "anual", nome: "Anual", meses: 12, preco: 2_700, preco_efetivo: 2_700, em_campanha: false, rotulo: null, validade: null, exclusividade: true, destaque: true },
  ],
  preco_a_partir_de: 2_700,
};

afterEach(() => {
  vi.unstubAllGlobals();
});

describe("copy operacional da landing", () => {
  it("usa o teto do endpoint e mantém os quatro planos na ordem comercial", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => painelStatus,
    });
    vi.stubGlobal("fetch", fetchMock);

    render(
      <>
        <Vantagens />
        <Exclusividade />
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
    expect(fetchMock).toHaveBeenCalledWith(
      "/painel-status.php",
      expect.objectContaining({ cache: "no-store" }),
    );

    const planNames = ["Mensal", "Trimestral", "Semestral", "Anual"];
    const planHeadings = screen.getAllByRole("heading", { level: 3 }).filter((heading) =>
      planNames.includes(heading.textContent ?? ""),
    );
    expect(planHeadings).toHaveLength(4);
    expect(planHeadings.map((heading) => heading.textContent)).toEqual(planNames);
    expect(screen.getAllByText(/frequência garantida: 15 aparições\/hora/i)).toHaveLength(4);
    expect(screen.getByText("Sem exclusividade de segmento")).toBeVisible();
    expect(screen.getByText("Mais vendido")).toBeInTheDocument();
    expect(screen.queryByText(/prioridade.*rodízio/i)).not.toBeInTheDocument();

    const planSelect = screen.getByRole("combobox", { name: /plano desejado/i });
    expect(planSelect.querySelectorAll("option")).toHaveLength(5);
    const segmentSelect = screen.getByRole("combobox", { name: /segmento/i });
    expect(segmentSelect.querySelectorAll("option")).toHaveLength(14);
    expect(screen.getByText(/10 segmentos ainda livres/i)).toBeInTheDocument();

    fireEvent.click(screen.getByRole("link", { name: /automotivo ocupado/i }));
    expect(segmentSelect).toHaveValue("automotivo");
    expect(screen.getByText(/este segmento já está ocupado/i)).toBeInTheDocument();

    fireEvent.change(planSelect, { target: { value: "Mensal" } });
    expect(screen.getByText(/no plano mensal isso não impede sua entrada/i)).toBeInTheDocument();
    expect(screen.queryByText(/este segmento já está ocupado/i)).not.toBeInTheDocument();

  });

  it("omite disponibilidade quando o registro é inconsistente", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({
          ...painelStatus,
          segmentos: painelStatus.segmentos.map((segment, index) => ({
            ...segment,
            ocupado: index < 2,
          })),
          segmentos_livres: 11,
          segmentos_consistente: false,
        }),
      }),
    );

    render(<Exclusividade />);

    await waitFor(() => {
      expect(screen.getByText("Automotivo")).toBeInTheDocument();
    });
    expect(screen.queryByText("Livre")).not.toBeInTheDocument();
    expect(screen.queryByText("Ocupado")).not.toBeInTheDocument();
    expect(screen.queryByText(/segmentos ainda livres/i)).not.toBeInTheDocument();
  });

  it("usa a lista estática sem disponibilidade quando o endpoint falha", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("offline")));

    render(<Exclusividade />);

    expect(screen.getByText("Automotivo")).toBeInTheDocument();
    expect(screen.getByText("Pet")).toBeInTheDocument();
    expect(screen.queryByText("Livre")).not.toBeInTheDocument();
    expect(screen.queryByText("Ocupado")).not.toBeInTheDocument();
  });

});
