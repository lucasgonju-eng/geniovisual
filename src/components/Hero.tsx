import { MessageCircle, ChevronDown } from "lucide-react";
import heroPanel from "@/assets/hero-panel.jpg";
import logo from "@/assets/logo-optimized.png";
import { usePainelStatus } from "@/hooks/usePainelStatus";
import { buildWhatsAppLink, trackWhatsAppClick } from "@/lib/whatsapp";

const WHATSAPP_MSG = "Olá! Quero anunciar no painel da Gênio Visual. Me envie os horários disponíveis e a melhor proposta.";

const tags = [
  "Painel LED vertical 8m × 4m",
  "Operação de até 19h por dia",
];

const formatNumber = (value: number) => new Intl.NumberFormat("pt-BR").format(value);

const formatScreenTime = (minutes: number) => {
  if (minutes === 60) return "1 hora";
  if (minutes > 60 && minutes % 60 === 0) return `${minutes / 60} horas`;
  return `${minutes} minutos`;
};

const Hero = () => {
  const { status: painel, isLive } = usePainelStatus();
  const currentScreenTime = formatScreenTime(painel.tela_dia_minutos);
  const guaranteedScreenTime = formatScreenTime(painel.tela_dia_min_minutos);
  const deliveryMultiplier = painel.aparicoes_hora_min > 0
    ? Math.round(painel.aparicoes_hora / painel.aparicoes_hora_min)
    : 1;

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
        {/* Tags */}
        <div className="mb-8 flex flex-wrap justify-center gap-2.5 sm:gap-3">
          {tags.map((t) => (
            <span key={t} className="glass-card neon-gradient-border rounded-full px-3 py-1.5 text-[11px] font-medium text-muted-foreground sm:px-4 sm:text-sm">
              {t}
            </span>
          ))}
          {isLive && (
            <span className="glass-card neon-gradient-border rounded-full px-3 py-1.5 text-[11px] font-medium text-muted-foreground sm:px-4 sm:text-sm">
              Teto operacional de {painel.vagas_totais} anunciantes
            </span>
          )}
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
            href={buildWhatsAppLink(WHATSAPP_MSG, "hero")}
            onClick={() => trackWhatsAppClick("hero")}
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

        {/* Status operacional do painel */}
        <div className="mt-12 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:gap-4">
          <div className="glass-card rounded-xl border border-amber-500/60 bg-amber-500/5 p-5 text-center sm:col-span-2 sm:p-6">
            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-amber-400">
              Garantia contratual
            </p>
            <p className="mt-2 font-heading text-xl font-semibold text-foreground sm:text-2xl">
              No mínimo {painel.aparicoes_hora_min} aparições por hora — {guaranteedScreenTime} de tela por dia.
            </p>
          </div>

          {isLive && (
            <>
              <div className="glass-card neon-gradient-border rounded-xl p-5 text-center sm:p-6">
                <div className="mb-1 font-heading text-3xl font-bold neon-gradient-text sm:text-4xl">
                  {painel.anunciantes}
                </div>
                <div className="text-xs text-muted-foreground sm:text-sm">anunciantes hoje</div>
              </div>
              <div className="glass-card neon-gradient-border rounded-xl p-5 text-center sm:p-6">
                <div className="mb-1 font-heading text-3xl font-bold neon-gradient-text sm:text-4xl">
                  {painel.vagas_restantes}
                </div>
                <div className="text-xs text-muted-foreground sm:text-sm">vagas restantes</div>
              </div>
              <div className="glass-card rounded-xl border border-cyan-500/60 bg-cyan-500/5 p-5 text-center sm:col-span-2 sm:p-6">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-400">
                  Vantagem de entrar agora
                </p>
                <p className="mt-2 font-heading text-xl font-semibold text-foreground sm:text-2xl">
                  Hoje, com apenas {painel.anunciantes} anunciantes, sua marca recebe {painel.aparicoes_hora} por hora e {currentScreenTime} de tela por dia — {deliveryMultiplier}× o garantido.
                </p>
                <p className="mt-2 text-sm text-muted-foreground">
                  {formatNumber(painel.aparicoes_dia)} aparições por dia na configuração atual.
                </p>
              </div>
            </>
          )}

          <div className="glass-card rounded-xl border border-border/60 p-5 text-center sm:col-span-2 sm:p-6">
            <p className="text-sm font-medium text-foreground sm:text-base">
              Em frente ao semáforo: vista por quem está parado.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
  );
};

export default Hero;
