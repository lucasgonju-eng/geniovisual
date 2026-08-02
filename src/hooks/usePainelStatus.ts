import { useEffect, useState } from "react";

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
};

const isPainelStatus = (value: unknown): value is PainelStatus => {
  if (!value || typeof value !== "object") return false;

  return Object.keys(PAINEL_STATUS_FALLBACK).every((key) => {
    const field = (value as Record<string, unknown>)[key];
    return typeof field === "number" && Number.isFinite(field) && field >= 0;
  });
};

export const usePainelStatus = () => {
  const [status, setStatus] = useState<PainelStatus>(PAINEL_STATUS_FALLBACK);
  const [isLive, setIsLive] = useState(false);

  useEffect(() => {
    const controller = new AbortController();

    fetch("/painel-status.php", {
      headers: { Accept: "application/json" },
      signal: controller.signal,
    })
      .then((response) => {
        if (!response.ok) throw new Error("Painel status indisponível");
        return response.json();
      })
      .then((payload) => {
        if (isPainelStatus(payload)) {
          setStatus(payload);
          setIsLive(true);
        }
      })
      .catch(() => {
        // O fallback conservador já está renderizado.
      });

    return () => controller.abort();
  }, []);

  return { status, isLive };
};
