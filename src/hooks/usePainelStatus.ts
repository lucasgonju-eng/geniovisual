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
};

export const PAINEL_STATUS_FALLBACK: PainelStatus = {
  anunciantes: 9,
  vagas_totais: 15,
  vagas_restantes: 6,
  aparicoes_hora: 20,
  aparicoes_dia: 360,
  aparicoes_mes: 10_800,
  tela_dia_minutos: 60,
  ciclo_segundos: 180,
  duracao_segundos: 10,
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
        if (isPainelStatus(payload)) setStatus(payload);
      })
      .catch(() => {
        // O fallback conservador já está renderizado.
      });

    return () => controller.abort();
  }, []);

  return status;
};
