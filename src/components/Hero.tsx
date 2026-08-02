import { MessageCircle, ChevronDown } from "lucide-react";
import heroPanel from "@/assets/hero-panel.jpg";
import logo from "@/assets/logo-optimized.png";
import { usePainelStatus } from "@/hooks/usePainelStatus";
import { formatPrice } from "@/lib/pricing";
import { buildWhatsAppLink, trackWhatsAppClick } from "@/lib/whatsapp";

const WHATSAPP_MSG = "Olá! Quero anunciar no painel da Gênio Visual. Gostaria de consultar os valores e a disponibilidade do meu segmento.";

const Hero = () => {
  const { status: painel, isLive } = usePainelStatus();

  return (
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
        {/* Animated logo */}
        <div className="mb-8 flex justify-center sm:mb-10">
          <div className="relative hero-logo-animate">
            <img src={logo} alt="Gênio Visual" className="h-auto w-[150px] sm:w-[200px] md:w-[260px]" />
            <div className="hero-logo-sparkles" />
          </div>
        </div>

        <p className="mb-4 text-xs font-semibold uppercase tracking-[0.22em] text-neon-cyan sm:text-sm">
          Av. T-15 · Setor Bueno · Goiânia
        </p>
        <h1 className="mb-5 font-heading text-4xl font-bold leading-tight sm:text-5xl lg:text-7xl">
          Sua marca vista por quem está <span className="neon-gradient-text">parado.</span>
        </h1>

        <p className="mx-auto mb-4 max-w-2xl text-base leading-relaxed text-muted-foreground sm:text-lg md:text-xl">
          Nosso painel de LED fica em frente ao semáforo da T-15, a menos de 50 metros do Goiânia Shopping. Quem passa por ali não passa voando: para, espera o sinal abrir e tem tempo de ler o seu anúncio.
        </p>

        <p className="mt-7 font-heading text-2xl font-bold text-foreground sm:text-3xl">
          {isLive && painel.preco_a_partir_de !== null
            ? `A partir de ${formatPrice(painel.preco_a_partir_de)} por mês.`
            : "Valor mensal sob consulta."}
        </p>
        <p className="mt-2 text-sm font-medium text-muted-foreground sm:text-base">
          Direto com o proprietário, sem agência e sem comissão.
        </p>

        <div className="mb-4 mt-10 flex flex-col gap-3 sm:mb-5 sm:flex-row sm:justify-center">
          <a
            href={buildWhatsAppLink(WHATSAPP_MSG, "hero")}
            onClick={() => trackWhatsAppClick("hero")}
            target="_blank"
            rel="noopener noreferrer"
            className="btn-neon flex items-center justify-center gap-2"
          >
            <MessageCircle className="w-5 h-5" />
            Falar agora no WhatsApp
          </a>
          <a href="#planos" className="btn-neon-outline flex items-center justify-center gap-2">
            <ChevronDown className="w-5 h-5" />
            Ver planos e valores
          </a>
        </div>

        {/* Status operacional do painel */}
        <div className="mt-12 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:gap-4">
          <div className="glass-card rounded-xl border border-amber-500/60 bg-amber-500/5 p-5 text-center sm:col-span-2 sm:p-6">
            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-amber-400">
              Garantia contratual
            </p>
            <p className="mt-2 font-heading text-xl font-semibold text-foreground sm:text-2xl">
              {painel.aparicoes_hora_min} aparições por hora, garantidas em contrato
            </p>
            <p className="mt-2 text-sm text-muted-foreground sm:text-base">
              {painel.tela_dia_min_minutos} minutos de tela por dia — o mínimo, mesmo com o painel lotado.
            </p>
          </div>

          {isLive && (
            <div className="glass-card rounded-xl border border-cyan-500/60 bg-cyan-500/5 p-5 text-center sm:col-span-2 sm:p-6">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-400">
                  Vantagem de entrar agora
                </p>
                <p className="mt-2 font-heading text-xl font-semibold text-foreground sm:text-2xl">
                  Hoje, com apenas {painel.anunciantes} anunciantes, sua marca receberia {painel.aparicoes_hora} por hora e {painel.tela_dia_minutos} minutos de tela por dia.
                </p>
                <p className="mt-3 text-sm font-medium text-cyan-300 sm:text-base">
                  Restam {painel.vagas_restantes} vagas.
                </p>
            </div>
          )}
        </div>
      </div>
    </div>
  </section>
  );
};

export default Hero;
