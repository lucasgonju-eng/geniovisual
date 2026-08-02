import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { usePainelStatus } from "@/hooks/usePainelStatus";

const FAQ = () => {
  const { status: painel, isLive } = usePainelStatus();
  const faqs = [
    {
      q: "Quantas vezes meu anúncio aparece?",
      a: `No mínimo ${painel.aparicoes_hora_min} vezes por hora, garantido em contrato — o que equivale a ${painel.aparicoes_dia_min} vezes por dia e ${painel.tela_dia_min_minutos} minutos de tela diários. Esse é o número do painel cheio.${isLive ? ` Hoje, com ${painel.anunciantes} anunciantes, sua marca apareceria ${painel.aparicoes_hora} vezes por hora.` : ""}`,
    },
    {
      q: "Quanto tempo dura cada inserção?",
      a: "10 segundos.",
    },
    {
      q: "Em quais horários o painel funciona?",
      a: "De segunda a quarta, das 6h à meia-noite. De quinta a domingo, das 6h à 1h.",
    },
    {
      q: "Por que vocês limitam a quantidade de anunciantes?",
      a: `${isLive ? `O limite de ${painel.vagas_totais} anunciantes existe` : "O limite existe"} porque cada anunciante novo aumenta o rodízio e reduz a frequência de todos os outros. É isso que nos permite garantir por contrato o número que prometemos.`,
    },
    {
      q: "Meu concorrente pode anunciar junto comigo?",
      a: "Não. Enquanto seu contrato estiver ativo, o seu segmento fica bloqueado para outras empresas.",
    },
    {
      q: "Como faço para anunciar?",
      a: "Chame no WhatsApp ou preencha o formulário. Respondemos com os valores e os segmentos disponíveis.",
    },
  ];

  return (
    <section className="py-20 relative">
      <div className="container mx-auto px-4 max-w-3xl">
        <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-12">
          Perguntas <span className="neon-gradient-text">Frequentes</span>
        </h2>

        <Accordion type="single" collapsible className="space-y-3">
          {faqs.map((faq, i) => (
            <AccordionItem key={i} value={`faq-${i}`} className="glass-card neon-gradient-border rounded-xl px-6 border-none">
              <AccordionTrigger className="font-heading font-semibold text-left hover:no-underline py-5">
                {faq.q}
              </AccordionTrigger>
              <AccordionContent className="text-muted-foreground pb-5 leading-relaxed">
                {faq.a}
              </AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>
      </div>
    </section>
  );
};

export default FAQ;
