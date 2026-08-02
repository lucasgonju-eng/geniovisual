export const PLANS = [
  {
    name: "Trimestral",
    highlight: "Para testar o painel com constância real",
    contract: "Contrato de 90 dias",
    creativeSupport: ["1 troca de arte no período"],
    featured: false,
  },
  {
    name: "Semestral",
    highlight: "Equilíbrio entre compromisso e flexibilidade",
    contract: "Contrato de 6 meses",
    creativeSupport: ["Troca de arte a cada 60 dias", "Revisão criativa sazonal"],
    featured: false,
  },
  {
    name: "Anual",
    badge: "Mais vendido",
    highlight: "Melhor valor mensal e presença contínua",
    contract: "Contrato de 12 meses",
    creativeSupport: ["Troca de arte mensal", "Planejamento de datas e campanhas do ano"],
    featured: true,
  },
] as const;

export const PLAN_OPTIONS = PLANS.map((plan) => plan.name);
