import { MessageCircle, Star } from "lucide-react";
import logo from "@/assets/logo-optimized.png";
import { usePainelStatus } from "@/hooks/usePainelStatus";
import { PLANS } from "@/lib/plans";
import { buildWhatsAppLink, trackWhatsAppClick } from "@/lib/whatsapp";

const Planos = () => {
  const { status: painel, isLive } = usePainelStatus();

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
          Valores sob consulta. Quanto maior o compromisso, menor o valor mensal.
          {isLive && ` Restam ${painel.vagas_restantes} vagas, respeitando um anunciante por segmento.`}
        </div>

        <div className="grid gap-5 max-w-5xl mx-auto items-stretch md:grid-cols-3">
          {PLANS.map((plan) => {
            const features = [
              plan.contract,
              `Frequência garantida: ${painel.aparicoes_hora_min} aparições/hora`,
              "Exclusividade do seu segmento",
              ...plan.creativeSupport,
            ];

            return (
          <div
            key={plan.name}
            className={`rounded-xl p-6 flex flex-col relative min-h-full ${
              plan.featured
                ? "plan-card-featured neon-glow-strong backdrop-blur-xl"
                : "glass-card neon-gradient-border"
            }`}
          >
            {"badge" in plan && plan.badge && (
              <div className="absolute -top-3 left-1/2 -translate-x-1/2 neon-gradient-bg px-4 py-1 rounded-full text-xs font-bold text-primary-foreground flex items-center gap-1">
                <Star className="w-3 h-3" /> {plan.badge}
              </div>
            )}
            {plan.featured && (
              <img src={logo} alt="" className="absolute top-4 right-4 h-6 w-6 opacity-40" />
            )}
            <div className="text-center mb-6 mt-2">
              <h3 className="font-heading text-xl font-bold mb-1">{plan.name}</h3>
              <span className="text-muted-foreground text-sm">Valor mensal sob consulta</span>
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {plan.highlight}
              </p>
            </div>

            <ul className="space-y-3 mb-6 flex-1">
              {features.map((feature) => (
                <li key={feature} className="flex items-start gap-2 text-sm text-muted-foreground">
                  <span className="neon-gradient-text mt-0.5">✦</span>
                  {feature}
                </li>
              ))}
            </ul>

            <a
              href={buildWhatsAppLink(
                `Olá! Tenho interesse no plano ${plan.name} do painel da Gênio Visual.`,
                "planos",
              )}
              onClick={() => trackWhatsAppClick("planos", plan.name)}
              target="_blank"
              rel="noopener noreferrer"
              className={`flex items-center justify-center gap-2 rounded-lg py-3 font-semibold text-sm transition-all duration-300 ${
                plan.featured
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
          Todos os planos têm a mesma frequência garantida e a mesma exclusividade de segmento. O que muda é o valor mensal e o apoio criativo.
        </p>
      </div>
    </section>
  );
};

export default Planos;
