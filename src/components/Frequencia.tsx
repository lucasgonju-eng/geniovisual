import { usePainelStatus } from "@/hooks/usePainelStatus";

const Frequencia = () => {
  const { status: painel, isLive } = usePainelStatus();
  const deliveryMultiplier = painel.aparicoes_hora_min > 0
    ? Math.round(painel.aparicoes_hora / painel.aparicoes_hora_min)
    : 1;

  return (
    <section className="relative py-20">
      <div className="container mx-auto px-4">
        <div className="mx-auto max-w-5xl">
          <h2 className="text-center font-heading text-3xl font-bold sm:text-4xl">
            O que está no contrato é o mínimo. Não o melhor caso.
          </h2>
          <div className="mx-auto mt-6 max-w-3xl space-y-4 text-center text-base leading-relaxed text-muted-foreground sm:text-lg">
            <p>
              Cada inserção dura 10 segundos. Quanto mais anunciantes no painel, mais longo fica o rodízio — então a frequência de todo mundo diminui à medida que as vagas são vendidas.
            </p>
            <p>
              Por isso nós {isLive ? `limitamos o painel a ${painel.vagas_totais} anunciantes` : "limitamos a quantidade de anunciantes"} e garantimos o número do painel cheio. Você contrata {painel.aparicoes_hora_min} aparições por hora e {painel.tela_dia_min_minutos} minutos de tela por dia sabendo que esse número vale mesmo no pior cenário — não importa quantos clientes entrarem depois de você.
            </p>
          </div>

          {isLive && (
            <div className="glass-card neon-gradient-border mx-auto mt-10 max-w-3xl rounded-2xl p-6 text-center sm:p-8">
              <p className="font-heading text-xl font-bold text-foreground sm:text-2xl">
                Hoje o painel tem {painel.anunciantes} anunciantes.
              </p>
              <p className="mt-3 leading-relaxed text-muted-foreground">
                Sua marca apareceria {painel.aparicoes_hora} vezes por hora — {painel.tela_dia_minutos} minutos de tela por dia, {deliveryMultiplier} vezes o mínimo contratado.
              </p>
              <p className="mt-3 font-semibold text-neon-cyan">
                Cada vaga vendida reduz essa vantagem. São {painel.vagas_restantes} restantes.
              </p>
            </div>
          )}

          <p className="mt-8 text-center text-sm text-muted-foreground">
            Operação de segunda a domingo, das 6h à meia-noite — até 1h nos fins de semana.
          </p>
        </div>
      </div>
    </section>
  );
};

export default Frequencia;
