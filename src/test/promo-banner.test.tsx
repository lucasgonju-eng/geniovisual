import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import PromoBanner from "@/components/PromoBanner";
import { PAINEL_STATUS_FALLBACK } from "@/hooks/usePainelStatus";

const promotion = {
  rotulo: "Promoção Relâmpago — 5 primeiros",
  descricao: "3 meses pelo preço de 2, direto com o proprietário.",
  preco_total: 5_760,
  equivalente_mensal: 1_920,
  forma_pagamento: "PIX à vista (100% antecipado)",
  vagas_restantes: 5,
  validade: "2026-08-31",
  mensagem_whatsapp: "Quero a condição dos 5 primeiros - R$ 5.760 no PIX",
};

afterEach(() => {
  vi.unstubAllGlobals();
  window.sessionStorage.clear();
  delete (window as typeof window & { dataLayer?: unknown[] }).dataLayer;
});

describe("PromoBanner", () => {
  it("exibe a oferta pública e rastreia o CTA do WhatsApp", async () => {
    window.sessionStorage.setItem(
      "gv_attr",
      JSON.stringify({ utm_campaign: "campanha-teste" }),
    );
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({ ...PAINEL_STATUS_FALLBACK, promocao: promotion }),
      }),
    );

    render(<PromoBanner />);

    const banner = await screen.findByRole("region", { name: "Promoção" });
    expect(banner).toHaveTextContent("Promoção Relâmpago — 5 primeiros");
    expect(banner).toHaveTextContent("R$ 5.760");
    expect(banner).toHaveTextContent("equivale a R$ 1.920/mês");
    expect(banner).toHaveTextContent("Restam 5 vagas nesta condição");
    expect(banner).toHaveTextContent("válido até 31/08/2026");

    const cta = screen.getByRole("link", { name: "Quero esta condição" });
    expect(decodeURIComponent(cta.getAttribute("href") ?? "")).toContain(
      "Quero a condição dos 5 primeiros - R$ 5.760 no PIX [via: campanha-teste]",
    );
    fireEvent.click(cta);

    expect((window as typeof window & { dataLayer?: unknown[] }).dataLayer).toContainEqual({
      event: "whatsapp_click",
      cta_location: "promo-banner",
      plan_name: "",
    });
  });

  it.each([
    ["desligada", null],
    ["inválida", { rotulo: "Incompleta", preco_total: "5760" }],
  ])("não renderiza quando a promoção está %s", async (_label, promocao) => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({ ...PAINEL_STATUS_FALLBACK, promocao }),
    });
    vi.stubGlobal("fetch", fetchMock);

    render(<PromoBanner />);

    await waitFor(() => expect(fetchMock).toHaveBeenCalledOnce());
    await new Promise((resolve) => setTimeout(resolve, 0));
    expect(screen.queryByRole("region", { name: "Promoção" })).not.toBeInTheDocument();
  });

  it("mantém o banner escondido quando o endpoint está offline", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("offline")));

    render(<PromoBanner />);

    await new Promise((resolve) => setTimeout(resolve, 0));
    expect(screen.queryByRole("region", { name: "Promoção" })).not.toBeInTheDocument();
  });
});
