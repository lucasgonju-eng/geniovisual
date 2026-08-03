import { MessageCircle, Zap } from "lucide-react";

import { usePainelStatus } from "@/hooks/usePainelStatus";
import { formatCampaignDate, formatPrice } from "@/lib/pricing";
import { buildWhatsAppLink, trackWhatsAppClick } from "@/lib/whatsapp";

const PromoBanner = () => {
  const { status, isLive } = usePainelStatus();
  const promotion = status.promocao;

  if (!isLive || !promotion) {
    return null;
  }

  return (
    <section
      role="region"
      aria-label="Promoção"
      className="relative overflow-hidden border-y border-cyan-400/30 bg-gradient-to-r from-cyan-950 via-slate-950 to-blue-950"
    >
      <div
        aria-hidden="true"
        className="absolute inset-0 opacity-30 [background-image:radial-gradient(circle_at_20%_20%,rgba(34,211,238,0.35),transparent_38%)]"
      />
      <div className="container relative mx-auto grid items-center gap-7 px-4 py-8 lg:grid-cols-[minmax(0,1.4fr)_auto_auto] lg:py-10">
        <div>
          <p className="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-[0.18em] text-cyan-300">
            <Zap className="h-4 w-4 fill-current" aria-hidden="true" />
            {promotion.rotulo}
          </p>
          <p className="max-w-3xl text-base leading-relaxed text-slate-200">
            {promotion.descricao}
          </p>
          <div className="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm">
            <span className="font-semibold text-amber-300">
              Restam {promotion.vagas_restantes} vagas nesta condição
            </span>
            {promotion.validade !== "" && (
              <span className="text-slate-400">
                válido até {formatCampaignDate(promotion.validade)}
              </span>
            )}
          </div>
        </div>

        <div className="lg:text-right">
          <p className="text-4xl font-black tracking-tight text-white">
            {formatPrice(promotion.preco_total)}
          </p>
          <p className="mt-1 text-sm text-cyan-200">
            equivale a {formatPrice(promotion.equivalente_mensal)}/mês
          </p>
          <p className="mt-2 text-xs text-slate-400">{promotion.forma_pagamento}</p>
        </div>

        <a
          href={buildWhatsAppLink(promotion.mensagem_whatsapp, "promo-banner")}
          onClick={() => trackWhatsAppClick("promo-banner")}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center justify-center gap-2 rounded-lg bg-cyan-400 px-6 py-3.5 font-bold text-slate-950 transition hover:bg-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-200 focus:ring-offset-2 focus:ring-offset-slate-950"
        >
          <MessageCircle className="h-5 w-5" aria-hidden="true" />
          Quero esta condição
        </a>
      </div>
    </section>
  );
};

export default PromoBanner;
