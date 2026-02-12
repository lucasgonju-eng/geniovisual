import { MessageCircle, Star } from "lucide-react";
import logo from "@/assets/logo.png";

const WHATSAPP_NUMBER = "+5562995077995";

const plans = [
  {
    name: "Bronze",
    period: "Mensal",
    price: "7.000",
    features: ["Contrato 30 dias", "Rodízio 15 marcas", "Inserção 10 a 15 segundos", "Relatório sob demanda"],
    featured: false,
  },
  {
    name: "Prata",
    period: "Trimestral",
    price: "6.000",
    features: ["Contrato 90 dias", "Prioridade de renovação", "Calendário de trocas de criativo", "Suporte criativo básico"],
    featured: false,
  },
  {
    name: "Ouro",
    period: "Semestral",
    price: "5.000",
    features: ["Contrato 6 meses", "Mais fixação de marca", "Apoio estratégico sazonal", "Revisão criativa a cada 60 dias"],
    featured: false,
  },
  {
    name: "Diamante",
    period: "Anual",
    price: "4.000",
    badge: "Mais vendido",
    features: ["Contrato 12 meses", "Melhor custo-benefício e ROI", "Prioridade máxima no rodízio", "Planejamento anual de campanhas e datas especiais"],
    featured: true,
  },
  {
    name: "Black",
    period: "Bienal",
    price: "3.500",
    features: ["Contrato 24 meses", "Valor travado", "Bônus em datas estratégicas", "Gestão premium e prioridade total"],
    featured: false,
  },
];

const Planos = () => (
  <section id="planos" className="py-20 relative particles-bg">
    <div className="container mx-auto px-4">
      <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-4">
        Planos <span className="neon-gradient-text">Premium</span>
      </h2>
      <p className="text-muted-foreground text-center text-lg mb-12 max-w-xl mx-auto">
        Escolha o plano ideal para sua marca e comece a impactar milhares de pessoas.
      </p>

      <div className="grid sm:grid-cols-2 lg:grid-cols-5 gap-5 max-w-7xl mx-auto items-stretch">
        {plans.map((p) => (
          <div
            key={p.name}
            className={`rounded-xl p-6 flex flex-col relative ${
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
              <div className="mt-3">
                <span className="text-muted-foreground text-sm">R$ </span>
                <span className="font-heading text-4xl font-bold neon-gradient-text">{p.price}</span>
                <span className="text-muted-foreground text-sm">/mês</span>
              </div>
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
              Quero este plano
            </a>
          </div>
        ))}
      </div>

      <p className="text-center mt-10 text-muted-foreground text-sm font-medium">
        ⚡ Vagas limitadas. Apenas 15 marcas podem rodar simultaneamente no painel.
      </p>
    </div>
  </section>
);

export default Planos;
