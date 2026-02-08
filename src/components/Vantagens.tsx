import { Monitor, RefreshCw, Clock } from "lucide-react";

const items = [
  {
    icon: Monitor,
    title: "Formato gigante e premium",
    desc: "Painel vertical 8m × 4m com alta resolução e visibilidade impactante.",
  },
  {
    icon: RefreshCw,
    title: "Frequência que fixa sua marca",
    desc: "Rodízio limitado para apenas 15 anunciantes, garantindo repetição e memorização.",
  },
  {
    icon: Clock,
    title: "Operação estendida todos os dias",
    desc: "Até 19 horas por dia no ar, com presença constante no melhor horário do público.",
  },
];

const Vantagens = () => (
  <section id="vantagens" className="py-20 relative">
    <div className="container mx-auto px-4">
      <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-4">
        Por que anunciar com a <span className="neon-gradient-text">Gênio Visual</span>?
      </h2>
      <p className="text-muted-foreground text-center text-lg mb-12 max-w-xl mx-auto">
        Diferenciais que fazem sua marca brilhar.
      </p>

      <div className="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        {items.map((item) => (
          <div key={item.title} className="glass-card neon-gradient-border p-8 rounded-xl text-center hover:neon-glow transition-shadow duration-300 group">
            <div className="w-16 h-16 mx-auto mb-6 rounded-xl neon-gradient-bg flex items-center justify-center group-hover:animate-float">
              <item.icon className="w-8 h-8 text-primary-foreground" />
            </div>
            <h3 className="font-heading text-xl font-semibold mb-3">{item.title}</h3>
            <p className="text-muted-foreground leading-relaxed">{item.desc}</p>
          </div>
        ))}
      </div>
    </div>
  </section>
);

export default Vantagens;
