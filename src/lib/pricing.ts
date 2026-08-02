import type { PlanPrice } from "@/hooks/usePainelStatus";

export const formatPrice = (value: number) =>
  new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
    maximumFractionDigits: 0,
  }).format(value).replace(/\u00a0/g, " ");

export const formatCampaignDate = (value: string) => {
  const [year, month, day] = value.split("-");
  return [day, month, year].filter(Boolean).join("/");
};

export const findPlanPrice = (plans: PlanPrice[], slug: string) =>
  plans.find((plan) => plan.slug === slug);
