import { MessageCircle, ChevronDown } from "lucide-react";
import heroPanel from "@/assets/hero-panel.jpg";
import logo from "@/assets/logo.png";

const WHATSAPP_NUMBER = "+5521995952526";
const WHATSAPP_MSG = encodeURIComponent("Olá! Quero anunciar no painel da Gênio Visual. Me envie os horários disponíveis e a melhor proposta.");

const stats = [
  { value: "1,7 mi", label: "impactos/mês" },
  { value: "115 mil", label: "impactos/mês por anunciante" },
  { value: "13 mil", label: "exibições/mês por anunciante" },
  { value: "Mais de 400", label: "aparições por dia" },
];

const tags = [
  "Rodízio premium com apenas 15 marcas",
  "Painel LED vertical 8m × 4m",
  "Operação de até 19h por dia",
];

const Hero = () => (
  <section className="relative min-h-screen flex items-center overflow-hidden pt-28 md:pt-32">
    {/* Background image */}
    <div className="absolute inset-0 z-0">
      <img src={heroPanel} alt="" className="w-full h-full object-cover opacity-30" />
      <div className="absolute inset-0 bg-gradient-to-b from-background via-background/80 to-background" />
      <div className="absolute inset-0 particles-bg opacity-40" />
      {/* Watermark logo */}
      <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
        <img src={logo} alt="" className="w-[400px] md:w-[600px] opacity-[0.03]" />
      </div>
    </div>

    <div className="container mx-auto relative z-10 px-4 py-16 lg:py-24">
      <div className="max-w-4xl mx-auto text-center">
        {/* Tags */}
        <div className="flex flex-wrap justify-center gap-3 mb-8">
          {tags.map((t) => (
            <span key={t} className="glass-card neon-gradient-border px-4 py-1.5 text-xs sm:text-sm font-medium text-muted-foreground rounded-full">
              {t}
            </span>
          ))}
        </div>

        {/* Animated logo */}
        <div className="flex justify-center mb-10">
          <div className="relative hero-logo-animate">
            <img src={logo} alt="Gênio Visual" className="w-[170px] sm:w-[220px] md:w-[260px] h-auto" />
            <div className="hero-logo-sparkles" />
          </div>
        </div>

        <h1 className="font-heading text-4xl sm:text-5xl lg:text-7xl font-bold leading-tight mb-6">
          Sua marca no maior{" "}
          <span className="neon-gradient-text">palco digital</span>{" "}
          de Goiânia.
        </h1>

        <p className="text-muted-foreground text-lg sm:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
          Painel de LED premium em ponto estratégico, com rodízio limitado e alta frequência. Mais visibilidade, mais lembrança e mais clientes para o seu negócio.
        </p>

        <div className="flex flex-col sm:flex-row gap-4 justify-center mb-16">
          <a
            href={`https://wa.me/${WHATSAPP_NUMBER}?text=${WHATSAPP_MSG}`}
            target="_blank"
            rel="noopener noreferrer"
            className="btn-neon flex items-center justify-center gap-2"
          >
            <MessageCircle className="w-5 h-5" />
            Quero anunciar agora
          </a>
          <a href="#planos" className="btn-neon-outline flex items-center justify-center gap-2">
            <ChevronDown className="w-5 h-5" />
            Ver planos disponíveis
          </a>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          {stats.map((s) => (
            <div key={s.label} className="glass-card neon-gradient-border p-6 rounded-xl text-center">
              <div className="font-heading text-3xl sm:text-4xl font-bold neon-gradient-text mb-1">{s.value}</div>
              <div className="text-muted-foreground text-xs sm:text-sm">{s.label}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  </section>
);

export default Hero;
