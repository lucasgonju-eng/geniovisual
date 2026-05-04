import { MessageCircle, ChevronDown } from "lucide-react";
import heroPanel from "@/assets/hero-panel.jpg";
import logo from "@/assets/logo-optimized.png";

const WHATSAPP_NUMBER = "+5562995077995";
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
  <section className="relative flex min-h-screen items-center overflow-hidden pt-24 md:pt-28">
    {/* Background image */}
    <div className="absolute inset-0 z-0">
      <img src={heroPanel} alt="" className="h-full w-full object-cover opacity-30" />
      <div className="absolute inset-0 bg-gradient-to-b from-background via-background/80 to-background" />
      <div className="absolute inset-0 particles-bg opacity-40" />
      {/* Watermark logo */}
      <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
        <img src={logo} alt="" className="w-[300px] sm:w-[420px] md:w-[580px] opacity-[0.03]" />
      </div>
    </div>

    <div className="container mx-auto relative z-10 px-4 py-12 sm:py-16 lg:py-24">
      <div className="max-w-4xl mx-auto text-center">
        {/* Tags */}
        <div className="mb-8 flex flex-wrap justify-center gap-2.5 sm:gap-3">
          {tags.map((t) => (
            <span key={t} className="glass-card neon-gradient-border rounded-full px-3 py-1.5 text-[11px] font-medium text-muted-foreground sm:px-4 sm:text-sm">
              {t}
            </span>
          ))}
        </div>

        {/* Animated logo */}
        <div className="mb-8 flex justify-center sm:mb-10">
          <div className="relative hero-logo-animate">
            <img src={logo} alt="Gênio Visual" className="h-auto w-[150px] sm:w-[200px] md:w-[260px]" />
            <div className="hero-logo-sparkles" />
          </div>
        </div>

        <h1 className="mb-5 font-heading text-4xl font-bold leading-tight sm:text-5xl lg:text-7xl">
          Sua marca no maior{" "}
          <span className="neon-gradient-text">palco digital</span>{" "}
          de Goiânia.
        </h1>

        <p className="mx-auto mb-4 max-w-2xl text-base leading-relaxed text-muted-foreground sm:text-lg md:text-xl">
          Painel de LED premium em ponto estratégico, com rodízio limitado e alta frequência. Mais visibilidade, mais lembrança e mais clientes para o seu negócio.
        </p>
        <p className="mx-auto mb-10 max-w-2xl text-sm leading-relaxed text-muted-foreground sm:text-base">
          Quer falar com alguém agora? Abra o WhatsApp. Se preferir receber uma proposta mais completa, siga para o formulário.
        </p>

        <div className="mb-4 flex flex-col gap-3 sm:mb-5 sm:flex-row sm:justify-center">
          <a
            href={`https://wa.me/${WHATSAPP_NUMBER}?text=${WHATSAPP_MSG}`}
            target="_blank"
            rel="noopener noreferrer"
            className="btn-neon flex items-center justify-center gap-2"
          >
            <MessageCircle className="w-5 h-5" />
            Falar no WhatsApp agora
          </a>
          <a href="#proposta" className="btn-neon-outline flex items-center justify-center gap-2">
            <ChevronDown className="w-5 h-5" />
            Receber proposta
          </a>
        </div>
        <a href="#planos" className="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
          <ChevronDown className="h-4 w-4" />
          Ver planos e valores por contrato
        </a>
        <p className="mt-2 text-xs text-muted-foreground">
          O WhatsApp abre em nova aba para acelerar o atendimento.
        </p>

        {/* Stats */}
        <div className="mt-12 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:gap-4">
          {stats.map((s) => (
            <div key={s.label} className="glass-card neon-gradient-border rounded-xl p-5 text-center sm:p-6">
              <div className="mb-1 font-heading text-3xl font-bold neon-gradient-text sm:text-4xl">{s.value}</div>
              <div className="text-xs text-muted-foreground sm:text-sm">{s.label}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  </section>
);

export default Hero;
