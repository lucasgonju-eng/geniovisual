import { beforeEach, describe, expect, it } from "vitest";

import { ATTRIBUTION_STORAGE_KEY, getAttribution } from "@/lib/attribution";
import { buildWhatsAppLink, trackWhatsAppClick, WHATSAPP_NUMBER } from "@/lib/whatsapp";

describe("atribuição de campanha", () => {
  beforeEach(() => {
    window.sessionStorage.clear();
    window.history.replaceState({}, "", "/");
    delete (window as typeof window & { dataLayer?: unknown[] }).dataLayer;
  });

  it("captura a primeira origem da sessão e preserva durante a navegação", () => {
    window.history.replaceState(
      {},
      "",
      "/?utm_source=linkedin&utm_medium=cpc&utm_campaign=teste&gclid=abc",
    );

    const first = getAttribution();
    window.history.replaceState({}, "", "/#planos");
    const second = getAttribution();

    expect(first).toMatchObject({
      utm_source: "linkedin",
      utm_medium: "cpc",
      utm_campaign: "teste",
      gclid: "abc",
      landing_path: "/?utm_source=linkedin&utm_medium=cpc&utm_campaign=teste&gclid=abc",
    });
    expect(second).toEqual(first);
    expect(window.sessionStorage.getItem(ATTRIBUTION_STORAGE_KEY)).not.toBeNull();
  });

  it("gera link sem sinal de mais e inclui a campanha na mensagem", () => {
    window.history.replaceState({}, "", "/?utm_campaign=ig-agosto");

    const link = buildWhatsAppLink("Olá! Quero uma proposta.", "hero");

    expect(WHATSAPP_NUMBER).toBe("5562995077995");
    expect(link).not.toContain("wa.me/+");
    expect(decodeURIComponent(link)).toContain("[via: ig-agosto]");
  });

  it("envia ao dataLayer apenas os dados permitidos do clique", () => {
    trackWhatsAppClick("planos", "Anual");

    expect((window as typeof window & { dataLayer?: unknown[] }).dataLayer).toEqual([
      {
        event: "whatsapp_click",
        cta_location: "planos",
        plan_name: "Anual",
      },
    ]);
  });
});
