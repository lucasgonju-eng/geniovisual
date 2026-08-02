import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";

import Index from "@/pages/Index";

describe("Index", () => {
  it("renderiza os principais caminhos de conversão da landing", () => {
    render(<Index />);

    expect(
      screen.getByRole("heading", {
        name: /sua marca vista por quem está parado/i,
      }),
    ).toBeInTheDocument();

    expect(screen.getByRole("link", { name: /falar agora no whatsapp/i })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: /planos e valores/i })).toBeInTheDocument();
    expect(screen.getAllByRole("heading", { level: 3 }).filter((heading) =>
      ["Trimestral", "Semestral", "Anual"].includes(heading.textContent ?? ""),
    )).toHaveLength(3);
    expect(screen.queryByRole("heading", { name: /bronze|black/i })).not.toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: /quero receber uma proposta/i }),
    ).toBeInTheDocument();
  });
});
