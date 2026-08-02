import { MessageCircle, Star } from "lucide-react";
import logo from "@/assets/logo-optimized.png";
import { usePainelStatus } from "@/hooks/usePainelStatus";
import { PLANS } from "@/lib/plans";
import { findPlanPrice, formatCampaignDate, formatPrice } from "@/lib/pricing";
import { buildWhatsAppLink, trackWhatsAppClick } from "@/lib/whatsapp";

const Planos = () => {
  const { status: painel, isLive } = usePainelStatus();
  const monthlyPrice = findPlanPrice(painel.planos, "mensal");

  return (
    <section id="planos" className="scroll-mt-28 py-20 relative particles-bg">
      <div className="container mx-auto px-4">
        <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-4">
          Planos e <span className="neon-gradient-text">valores</span>
        </h2>
        <p className="text-muted-foreground text-center text-lg mb-8 max-w-2xl mx-auto">
          Direto com o proprietário. Sem agência, sem comissão, sem intermediário.
        </p>
        <div className="mx-auto mb-10 max-w-3xl rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-center text-sm text-muted-foreground">
          {isLive && painel.preco_a_partir_de !== null
            ? `Planos a partir de ${formatPrice(painel.preco_a_partir_de)} por mês.`
            : "Valores mensais sob consulta."}
          {" "}Quanto maior o compromisso, menor o valor mensal.
          {isLive && ` Restam ${painel.vagas_restantes} vagas no rodízio.`}
        </div>

        <div className="grid gap-5 max-w-7xl mx-auto items-stretch md:grid-cols-2 xl:grid-cols-4">
          {PLANS.map((plan) => {
            const price = isLive ? findPlanPrice(painel.planos, plan.slug) : undefined;
            const hasExclusivity = price?.exclusividade ?? plan.exclusive;
            const isFeatured = price?.destaque ?? plan.featured;
            const discount = price && monthlyPrice && monthlyPrice.preco_efetivo > price.preco_efetivo
              ? Math.round((1 - price.preco_efetivo / monthlyPrice.preco_efetivo) * 100)
              : 0;
            const features = [
              { label: plan.contract, muted: false },
              { label: `Frequência garantida: ${painel.aparicoes_hora_min} aparições/hora`, muted: false },
              {
                label: hasExclusivity
                  ? "Exclusividade do seu segmento"
                  : "Sem exclusividade de segmento",
                muted: !hasExclusivity,
              },
              ...plan.creativeSupport.map((feature) => ({ label: feature, muted: false })),
            ];
            const whatsappMessage = price
              ? `Olá! Tenho interesse no plano ${plan.name} por ${formatPrice(price.preco_efetivo)} por mês${
                price.em_campanha && price.rotulo && price.validade
                  ? `, na campanha "${price.rotulo}", válida até ${formatCampaignDate(price.validade)}`
                  : ""
              }.`
              : `Olá! Tenho interesse no plano ${plan.name} do painel da Gênio Visual.`;

            return (
          <div
            key={plan.name}
            className={`rounded-xl p-6 flex flex-col relative min-h-full ${
              isFeatured
                ? "plan-card-featured neon-glow-strong backdrop-blur-xl"
                : "glass-card neon-gradient-border"
            }`}
          >
            {isFeatured && (
              <div className="absolute -top-3 left-1/2 -translate-x-1/2 neon-gradient-bg px-4 py-1 rounded-full text-xs font-bold text-primary-foreground flex items-center gap-1">
                <Star className="w-3 h-3" /> {"badge" in plan ? plan.badge : "Destaque"}
              </div>
            )}
            {isFeatured && (
              <img src={logo} alt="" className="absolute top-4 right-4 h-6 w-6 opacity-40" />
            )}
            <div className="text-center mb-6 mt-2">
              <h3 className="font-heading text-xl font-bold mb-1">{plan.name}</h3>
              {price ? (
                <div className="mt-2">
                  {price.em_campanha && (
                    <span className="mr-2 text-sm text-muted-foreground line-through">
                      {formatPrice(price.preco)}
                    </span>
                  )}
                  <strong className="font-heading text-2xl text-foreground">
                    {formatPrice(price.preco_efetivo)}
                  </strong>
                  <span className="text-sm text-muted-foreground">/mês</span>
                  {discount > 0 && (
                    <p className="mt-1 text-xs font-semibold text-emerald-300">
                      {discount}% abaixo do mensal
                    </p>
                  )}
                  {price.em_campanha && price.rotulo && price.validade && (
                    <p className="mt-2 text-xs font-medium text-amber-300">
                      {price.rotulo} · válido até {formatCampaignDate(price.validade)}
                    </p>
                  )}
                </div>
              ) : (
                <span className="text-muted-foreground text-sm">Valor mensal sob consulta</span>
              )}
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {plan.highlight}
              </p>
            </div>

            <ul className="space-y-3 mb-6 flex-1">
              {features.map((feature) => (
                <li
                  key={feature.label}
                  className={`flex items-start gap-2 text-sm ${
                    feature.muted ? "font-medium text-zinc-500" : "text-muted-foreground"
                  }`}
                >
                  <span className={feature.muted ? "mt-0.5 text-zinc-500" : "neon-gradient-text mt-0.5"}>
                    {feature.muted ? "—" : "✦"}
                  </span>
                  {feature.label}
                </li>
              ))}
            </ul>

            <a
              href={buildWhatsAppLink(
                whatsappMessage,
                "planos",
              )}
              onClick={() => trackWhatsAppClick("planos", plan.name)}
              target="_blank"
              rel="noopener noreferrer"
              className={`flex items-center justify-center gap-2 rounded-lg py-3 font-semibold text-sm transition-all duration-300 ${
                isFeatured
                  ? "btn-neon !px-4 !py-3 !text-sm"
                  : "btn-neon-outline !px-4 !py-3 !text-sm"
              }`}
            >
              <MessageCircle className="w-4 h-4" />
              Falar sobre este plano
            </a>
            <p className="mt-3 text-center text-xs text-muted-foreground">
              Abre o WhatsApp com a mensagem deste plano.
            </p>
          </div>
            );
          })}
        </div>

        <p className="text-center mt-10 text-muted-foreground text-sm font-medium max-w-3xl mx-auto">
          Todos os planos têm a mesma frequência garantida. A partir do trimestral, você também trava o seu segmento: nenhuma empresa concorrente anuncia no painel enquanto o seu contrato estiver ativo.
        </p>
      </div>
    </section>
  );
};

export default Planos;
