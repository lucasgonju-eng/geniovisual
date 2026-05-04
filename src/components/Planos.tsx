import { MessageCircle, Star } from "lucide-react";
import logo from "@/assets/logo-optimized.png";

const WHATSAPP_NUMBER = "+5562995077995";

const plans = [
  {
    name: "Bronze",
    period: "Mensal",
    highlight: "Entrada rápida para testar presença no painel",
    features: ["Contrato 30 dias", "Rodízio 15 marcas", "Inserção 10 a 15 segundos", "Relatório sob demanda"],
    featured: false,
  },
  {
    name: "Prata",
    period: "Trimestral",
    highlight: "Mais constância para campanhas sazonais",
    features: ["Contrato 90 dias", "Prioridade de renovação", "Calendário de trocas de criativo", "Suporte criativo básico"],
    featured: false,
  },
  {
    name: "Ouro",
    period: "Semestral",
    highlight: "Equilíbrio entre frequência, suporte e custo",
    features: ["Contrato 6 meses", "Mais fixação de marca", "Apoio estratégico sazonal", "Revisão criativa a cada 60 dias"],
    featured: false,
  },
  {
    name: "Diamante",
    period: "Anual",
    badge: "Mais vendido",
    highlight: "Plano recomendado para construir lembrança de marca",
    features: ["Contrato 12 meses", "Melhor custo-benefício e ROI", "Prioridade máxima no rodízio", "Planejamento anual de campanhas e datas especiais"],
    featured: true,
  },
  {
    name: "Black",
    period: "Bienal",
    highlight: "Máxima previsibilidade para marcas de longo prazo",
    features: ["Contrato 24 meses", "Valor travado", "Bônus em datas estratégicas", "Gestão premium e prioridade total"],
    featured: false,
  },
];

const Planos = () => (
  <section id="planos" className="scroll-mt-28 py-20 relative particles-bg">
    <div className="container mx-auto px-4">
      <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-4">
        Planos <span className="neon-gradient-text">Premium</span>
      </h2>
      <p className="text-muted-foreground text-center text-lg mb-12 max-w-xl mx-auto">
        Escolha o plano ideal para sua marca e comece a impactar milhares de pessoas.
      </p>
      <div className="mx-auto mb-10 max-w-3xl rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-center text-sm text-muted-foreground">
        Condições comerciais sob consulta. Fale com a equipe no WhatsApp para receber a melhor proposta para o prazo do seu contrato.
      </div>

      <div className="grid gap-5 max-w-7xl mx-auto items-stretch md:grid-cols-2 xl:grid-cols-5">
        {plans.map((p) => (
          <div
            key={p.name}
            className={`rounded-xl p-6 flex flex-col relative min-h-full ${
              p.featured
                ? "plan-card-featured neon-glow-strong backdrop-blur-xl"
                : "glass-card neon-gradient-border"
            }`}
          >
            {p.badge && (
              <div className="absolute -top-3 left-1/2 -translate-x-1/2 neon-gradient-bg px-4 py-1 rounded-full text-xs font-bold text-primary-foreground flex items-center gap-1">
                <Star className="w-3 h-3" /> {p.badge}
              </div>
            )}
            {p.featured && (
              <img src={logo} alt="" className="absolute top-4 right-4 h-6 w-6 opacity-40" />
            )}
            <div className="text-center mb-6 mt-2">
              <h3 className="font-heading text-xl font-bold mb-1">{p.name}</h3>
              <span className="text-muted-foreground text-sm">{p.period}</span>
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {p.highlight}
              </p>
            </div>

            <ul className="space-y-3 mb-6 flex-1">
              {p.features.map((f) => (
                <li key={f} className="flex items-start gap-2 text-sm text-muted-foreground">
                  <span className="neon-gradient-text mt-0.5">✦</span>
                  {f}
                </li>
              ))}
            </ul>

            <a
              href={`https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(`Olá! Tenho interesse no plano ${p.name} (${p.period}) do painel da Gênio Visual.`)}`}
              target="_blank"
              rel="noopener noreferrer"
              className={`flex items-center justify-center gap-2 rounded-lg py-3 font-semibold text-sm transition-all duration-300 ${
                p.featured
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
        ))}
      </div>

      <p className="text-center mt-10 text-muted-foreground text-sm font-medium max-w-2xl mx-auto">
        ⚡ Vagas limitadas. Apenas 15 marcas podem rodar simultaneamente no painel.
      </p>
    </div>
  </section>
);

export default Planos;
