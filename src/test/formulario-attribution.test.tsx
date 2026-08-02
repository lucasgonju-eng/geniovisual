import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import Formulario from "@/components/Formulario";

describe("envio atribuído do formulário", () => {
  beforeEach(() => {
    window.sessionStorage.clear();
    window.history.replaceState(
      {},
      "",
      "/?utm_source=linkedin&utm_medium=cpc&utm_campaign=teste",
    );
    delete (window as typeof window & { dataLayer?: unknown[] }).dataLayer;
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("envia origem e consentimento e registra a conversão sem PII", async () => {
    const fetchMock = vi.fn().mockResolvedValue({ ok: true });
    vi.stubGlobal("fetch", fetchMock);

    render(<Formulario />);
    fireEvent.change(screen.getByPlaceholderText("Seu nome"), { target: { value: "Lead Teste" } });
    fireEvent.change(screen.getByPlaceholderText("seu@email.com"), { target: { value: "lead@example.com" } });
    fireEvent.change(screen.getByPlaceholderText("(62) 99999-9999"), { target: { value: "(62) 99999-0000" } });
    fireEvent.change(screen.getByPlaceholderText(/imobiliário, saúde, alimentação/i), {
      target: { value: "Imobiliário" },
    });
    fireEvent.click(screen.getByRole("checkbox"));
    fireEvent.click(screen.getByRole("button", { name: /quero saber se meu segmento está livre/i }));

    await waitFor(() => {
      expect(fetchMock.mock.calls.some(([, init]) => (init as RequestInit | undefined)?.method === "POST")).toBe(true);
    });
    const request = fetchMock.mock.calls.find(
      ([, init]) => (init as RequestInit | undefined)?.method === "POST",
    )?.[1] as RequestInit;
    const payload = JSON.parse(request.body as string);

    expect(payload).toMatchObject({
      utm_source: "linkedin",
      utm_medium: "cpc",
      utm_campaign: "teste",
      segmento: "Imobiliário",
      consent: true,
      consent_text: "Autorizo o contato da Gênio Visual para envio de proposta comercial.",
    });
    expect(payload.consent_at).toMatch(/Z$/);
    expect(payload.page_url).toContain("utm_source=linkedin");

    await waitFor(() => {
      expect((window as typeof window & { dataLayer?: unknown[] }).dataLayer).toEqual([
        {
          event: "proposal_form_success",
          plan_name: "(nenhum)",
          utm_source: "linkedin",
          utm_campaign: "teste",
        },
      ]);
    });
  });
});
