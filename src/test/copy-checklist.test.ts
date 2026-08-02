// @vitest-environment node

import { readdir, readFile } from "node:fs/promises";
import path from "node:path";
import { describe, expect, it } from "vitest";

const ignoredDirectories = new Set([".git", "dist", "node_modules"]);
const textExtensions = new Set([
  ".html",
  ".json",
  ".md",
  ".php",
  ".ts",
  ".tsx",
  ".txt",
  ".xml",
  ".yaml",
  ".yml",
]);

const blockedClaims = [
  ["1,7", "milhão"].join(" "),
  ["1,7", "mi"].join(" "),
  ["115", "mil"].join(" "),
  ["maior", "palco", "digital"].join(" "),
  ["maior", "painel"].join(" "),
  ["mais", "visível"].join(" "),
  ["custo-benefício", "e", "ROI"].join(" "),
  ["15", "marcas"].join(" "),
  ["15", "anunciantes"].join(" "),
  ["10", "a", "15", "segundos"].join(" "),
  ["prioridade", "máxima"].join(" "),
  ["prioridade", "total"].join(" "),
  ["valor", "travado"].join(" "),
];

const listTextFiles = async (directory: string): Promise<string[]> => {
  const entries = await readdir(directory, { withFileTypes: true });
  const nested = await Promise.all(entries.map(async (entry) => {
    const fullPath = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      return ignoredDirectories.has(entry.name) ? [] : listTextFiles(fullPath);
    }
    return textExtensions.has(path.extname(entry.name)) ? [fullPath] : [];
  }));
  return nested.flat();
};

describe("checklist de copy", () => {
  it("não mantém claims removidos em arquivos textuais do repositório", async () => {
    const root = process.cwd();
    const files = await listTextFiles(root);
    const violations: string[] = [];

    for (const file of files) {
      const contents = (await readFile(file, "utf8")).toLocaleLowerCase("pt-BR");
      for (const claim of blockedClaims) {
        if (contents.includes(claim.toLocaleLowerCase("pt-BR"))) {
          violations.push(`${path.relative(root, file)}: ${claim}`);
        }
      }
    }

    expect(violations).toEqual([]);
  });
});
