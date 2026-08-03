// @vitest-environment node

import { readFile } from "node:fs/promises";
import path from "node:path";
import { describe, expect, it } from "vitest";

describe("isolamento operacional da promoção", () => {
  it("mantém a aba protegida por login e CSRF com todos os campos esperados", async () => {
    const admin = await readFile(path.join("public", "admin.php"), "utf8");
    const handler = admin.slice(
      admin.indexOf("// --- Ação: atualizar promoção relâmpago ---"),
      admin.indexOf("// --- Se não logado, exibir tela de login ---"),
    );

    expect(admin).toContain("'promo' => 'Promoção'");
    expect(admin).toContain('href="admin.php?tab=promo"');
    expect(handler).toContain("!empty($_SESSION['admin_logged'])");
    expect(handler).toContain("if (!$isValidCsrf())");
    expect(handler.indexOf("if (!$isValidCsrf())")).toBeLessThan(handler.indexOf("promo_write("));

    for (const field of [
      "ativa",
      "rotulo",
      "descricao",
      "preco_total",
      "equivalente_mensal",
      "forma_pagamento",
      "limite_vagas",
      "vagas_restantes",
      "validade",
      "mensagem_whatsapp",
    ]) {
      expect(admin).toContain(`name="${field}"`);
    }
    expect(admin).toContain("Pré-visualização");
    expect(admin).toContain("Salvar promoção");
  });

  it("posiciona o banner após o Hero sem acoplar a tabela de planos", async () => {
    const index = await readFile(path.join("src", "pages", "Index.tsx"), "utf8");
    const plans = await readFile(path.join("src", "components", "Planos.tsx"), "utf8");

    expect(index.indexOf("<PromoBanner />")).toBeGreaterThan(index.indexOf("<Hero />"));
    expect(index.indexOf("<PromoBanner />")).toBeLessThan(index.indexOf("<ProvaVisual />"));
    expect(plans).not.toMatch(/PromoBanner|promocao|promo-banner/i);
  });

  it("preserva promocao.json fora do Git e do rsync", async () => {
    const gitignore = await readFile(".gitignore", "utf8");
    const deploy = await readFile("DEPLOY.md", "utf8");
    const workflow = await readFile(
      path.join(".github", "workflows", "deploy-hostinger.yml"),
      "utf8",
    );

    expect(gitignore).toContain("private/*.json");
    expect(deploy).toContain("private/promocao.json");
    expect(workflow).toContain("--exclude='private/***'");
  });
});
