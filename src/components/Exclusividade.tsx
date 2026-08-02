import { ShieldCheck } from "lucide-react";

const segments = [
  "Automotivo",
  "Imobiliário",
  "Saúde e odontologia",
  "Educação",
  "Alimentação e restaurantes",
  "Varejo e moda",
  "Beleza e estética",
  "Academias e fitness",
  "Serviços financeiros",
  "Construção e reforma",
  "Tecnologia",
  "Advocacia e contabilidade",
  "Pet",
];

const Exclusividade = () => (
  <section className="relative py-20 particles-bg">
    <div className="container mx-auto px-4">
      <div className="mx-auto max-w-5xl">
        <div className="mx-auto max-w-3xl text-center">
          <ShieldCheck className="mx-auto mb-5 h-10 w-10 text-neon-cyan" />
          <h2 className="font-heading text-3xl font-bold sm:text-4xl">
            Um anunciante por segmento. Seu concorrente não entra.
          </h2>
          <div className="mt-6 space-y-4 text-base leading-relaxed text-muted-foreground sm:text-lg">
            <p>
              Enquanto o seu contrato estiver ativo, nenhuma empresa do seu segmento anuncia neste painel. Você não está comprando só espaço — está comprando a ausência do seu concorrente na esquina mais movimentada do Bueno.
            </p>
            <p>
              Quando um segmento é ocupado, ele sai da lista. Quem chega depois entra na fila de espera.
            </p>
          </div>
        </div>

        <div className="mt-10 flex flex-wrap justify-center gap-2.5">
          {segments.map((segment) => (
            <span
              key={segment}
              className="glass-card rounded-full border border-white/10 px-4 py-2 text-sm text-muted-foreground"
            >
              {segment}
            </span>
          ))}
        </div>
      </div>
    </div>
  </section>
);

export default Exclusividade;
