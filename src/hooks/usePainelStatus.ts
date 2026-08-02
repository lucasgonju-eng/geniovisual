import { useEffect, useState } from "react";
import type { SegmentStatus } from "@/lib/segments";

export type PlanPrice = {
  slug: string;
  nome: string;
  meses: number;
  preco: number;
  preco_efetivo: number;
  em_campanha: boolean;
  rotulo: string | null;
  validade: string | null;
  exclusividade: boolean;
  destaque: boolean;
};

export type PainelStatus = {
  anunciantes: number;
  vagas_totais: number;
  vagas_restantes: number;
  aparicoes_hora: number;
  aparicoes_dia: number;
  aparicoes_mes: number;
  tela_dia_minutos: number;
  ciclo_segundos: number;
  duracao_segundos: number;
  aparicoes_hora_min: number;
  aparicoes_dia_min: number;
  aparicoes_mes_min: number;
  tela_dia_min_minutos: number;
  ciclo_max_segundos: number;
  segmentos: SegmentStatus[];
  segmentos_livres: number;
  segmentos_consistente: boolean;
  planos: PlanPrice[];
  preco_a_partir_de: number | null;
};

export const PAINEL_STATUS_FALLBACK: PainelStatus = {
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
  segmentos: [],
  segmentos_livres: 0,
  segmentos_consistente: false,
  planos: [],
  preco_a_partir_de: null,
};

const isPainelStatus = (value: unknown): value is PainelStatus => {
  if (!value || typeof value !== "object") return false;

  const candidate = value as Record<string, unknown>;
  const numericKeys = [
    "anunciantes",
    "vagas_totais",
    "vagas_restantes",
    "aparicoes_hora",
    "aparicoes_dia",
    "aparicoes_mes",
    "tela_dia_minutos",
    "ciclo_segundos",
    "duracao_segundos",
    "aparicoes_hora_min",
    "aparicoes_dia_min",
    "aparicoes_mes_min",
    "tela_dia_min_minutos",
    "ciclo_max_segundos",
    "segmentos_livres",
  ];
  const validNumbers = numericKeys.every((key) => {
    const field = candidate[key];
    return typeof field === "number" && Number.isFinite(field) && field >= 0;
  });
  const validSegments = Array.isArray(candidate.segmentos)
    && candidate.segmentos.every((segment) => {
      if (!segment || typeof segment !== "object") return false;
      const item = segment as Record<string, unknown>;
      return typeof item.slug === "string"
        && item.slug !== ""
        && typeof item.nome === "string"
        && item.nome !== ""
        && typeof item.ocupado === "boolean";
    });
  const validPlans = Array.isArray(candidate.planos)
    && candidate.planos.every((plan) => {
      if (!plan || typeof plan !== "object") return false;
      const item = plan as Record<string, unknown>;
      return typeof item.slug === "string"
        && item.slug !== ""
        && typeof item.nome === "string"
        && item.nome !== ""
        && typeof item.meses === "number"
        && item.meses > 0
        && typeof item.preco === "number"
        && item.preco > 0
        && typeof item.preco_efetivo === "number"
        && item.preco_efetivo > 0
        && typeof item.em_campanha === "boolean"
        && (item.rotulo === null || typeof item.rotulo === "string")
        && (item.validade === null || typeof item.validade === "string")
        && typeof item.exclusividade === "boolean"
        && typeof item.destaque === "boolean";
    });
  const validStartingPrice = candidate.preco_a_partir_de === null
    || (
      typeof candidate.preco_a_partir_de === "number"
      && Number.isFinite(candidate.preco_a_partir_de)
      && candidate.preco_a_partir_de > 0
    );

  return validNumbers
    && validSegments
    && validPlans
    && validStartingPrice
    && typeof candidate.segmentos_consistente === "boolean";
};

let inFlightRequest: Promise<PainelStatus | null> | null = null;

const requestPainelStatus = () => {
  if (inFlightRequest) return inFlightRequest;

  inFlightRequest = fetch("/painel-status.php", {
    headers: { Accept: "application/json" },
    cache: "no-store",
  })
    .then((response) => {
      if (!response.ok) throw new Error("Painel status indisponível");
      return response.json();
    })
    .then((payload) => (isPainelStatus(payload) ? payload : null))
    .catch(() => null)
    .finally(() => {
      inFlightRequest = null;
    });

  return inFlightRequest;
};

export const usePainelStatus = () => {
  const [status, setStatus] = useState<PainelStatus>(PAINEL_STATUS_FALLBACK);
  const [isLive, setIsLive] = useState(false);

  useEffect(() => {
    let active = true;

    requestPainelStatus()
      .then((payload) => {
        if (active && payload) {
          setStatus(payload);
          setIsLive(true);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  return { status, isLive };
};
