import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";

const faqs = [
  {
    q: "Quantas vezes minha marca aparece por hora?",
    a: "Sua marca aparece 24 vezes por hora, garantindo alta frequência e fixação junto ao público que transita pela via.",
  },
  {
    q: "Posso trocar meu vídeo/arte durante o contrato?",
    a: "Sim! Dependendo do plano, você pode trocar seu criativo com facilidade. Nos planos mais completos, oferecemos calendário de trocas e suporte criativo.",
  },
  {
    q: "Preciso entregar o vídeo pronto?",
    a: "Não necessariamente. Nos planos Ouro, Diamante e Black oferecemos suporte criativo para ajudar você a ter o melhor material possível.",
  },
  {
    q: "Quanto tempo leva para começar a rodar?",
    a: "Após a aprovação do material, sua campanha pode começar a rodar em até 48 horas úteis.",
  },
];

const FAQ = () => (
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

export default FAQ;
