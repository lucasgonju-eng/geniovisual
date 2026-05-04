import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";

import Index from "@/pages/Index";

describe("Index", () => {
  it("renderiza os principais caminhos de conversão da landing", () => {
    render(<Index />);

    expect(
      screen.getByRole("heading", {
        name: /sua marca no maior palco digital de goiânia/i,
      }),
    ).toBeInTheDocument();

    expect(screen.getByRole("link", { name: /falar no whatsapp agora/i })).toBeInTheDocument();
    expect(screen.getAllByRole("link", { name: /receber proposta/i }).length).toBeGreaterThan(0);
    expect(screen.getByRole("heading", { name: /planos premium/i })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /receber proposta agora/i })).toBeInTheDocument();
  });
});
