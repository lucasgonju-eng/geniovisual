import { ShieldCheck } from "lucide-react";
import { usePainelStatus } from "@/hooks/usePainelStatus";
import { DEFAULT_SEGMENTS, selectSegmentForForm } from "@/lib/segments";

const Exclusividade = () => {
  const { status: painel, isLive } = usePainelStatus();
  const hasConfiguredSegments = isLive && painel.segmentos.length > 0;
  const showAvailability = hasConfiguredSegments && painel.segmentos_consistente;
  const segments = hasConfiguredSegments ? painel.segmentos : DEFAULT_SEGMENTS;

  return (
    <section className="relative py-20 particles-bg">
      <div className="container mx-auto px-4">
        <div className="mx-auto max-w-5xl">
          <div className="mx-auto max-w-3xl text-center">
            <ShieldCheck className="mx-auto mb-5 h-10 w-10 text-neon-cyan" />
            <h2 className="font-heading text-3xl font-bold sm:text-4xl">
              A partir do trimestral, um anunciante por segmento.
            </h2>
            <div className="mt-6 space-y-4 text-base leading-relaxed text-muted-foreground sm:text-lg">
              <p>
                Enquanto o seu contrato trimestral, semestral ou anual estiver ativo, nenhuma empresa do seu segmento anuncia neste painel. Você não está comprando só espaço — está comprando a ausência do seu concorrente na esquina mais movimentada do Bueno.
              </p>
              <p>
                Quando um segmento é ocupado, ele sai da lista. Quem chega depois entra na fila de espera.
              </p>
              {showAvailability && (
                <p className="font-semibold text-neon-cyan">
                  {painel.segmentos_livres} segmentos ainda livres. Quando o seu é ocupado, ele sai da lista — e só volta se o contrato não for renovado.
                </p>
              )}
            </div>
          </div>

          <div className="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {segments.map((segment) => {
              if (!showAvailability) {
                return (
                  <span
                    key={segment.slug}
                    className="glass-card rounded-xl border border-white/10 px-4 py-3 text-sm text-muted-foreground"
                  >
                    {segment.nome}
                  </span>
                );
              }

              return (
                <a
                  key={segment.slug}
                  href="#proposta"
                  onClick={() => selectSegmentForForm(segment.slug)}
                  className={`glass-card rounded-xl border px-4 py-3 text-sm transition ${
                    segment.ocupado
                      ? "border-zinc-700 text-zinc-500 opacity-75 hover:opacity-100"
                      : "border-emerald-500/60 text-emerald-200 hover:bg-emerald-500/10"
                  }`}
                >
                  <span className="flex items-center justify-between gap-3">
                    <strong>{segment.nome}</strong>
                    <span className={`rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${
                      segment.ocupado
                        ? "bg-zinc-800 text-zinc-400"
                        : "bg-emerald-500/15 text-emerald-300"
                    }`}>
                      {segment.ocupado ? "Ocupado" : "Livre"}
                    </span>
                  </span>
                  {segment.ocupado && (
                    <span className="mt-2 block text-xs text-amber-300">
                      Entrar na lista de espera
                    </span>
                  )}
                </a>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
};

export default Exclusividade;
