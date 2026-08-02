import { useEffect, useState } from "react";
import { MessageCircle, Send, CheckCircle } from "lucide-react";
import { toast } from "sonner";
import { usePainelStatus } from "@/hooks/usePainelStatus";
import { getAttribution } from "@/lib/attribution";
import { PLAN_OPTIONS } from "@/lib/plans";
import { findPlanPrice, formatCampaignDate, formatPrice } from "@/lib/pricing";
import { DEFAULT_SEGMENTS, SEGMENT_SELECT_EVENT } from "@/lib/segments";
import { buildWhatsAppLink, trackWhatsAppClick } from "@/lib/whatsapp";

const pitchText = "Olá! Quero saber se o meu segmento está livre para anunciar no painel da Gênio Visual.";
const FORM_ENDPOINT = "/send.php";
const CONSENT_TEXT = "Autorizo o contato da Gênio Visual para envio de proposta comercial.";

const Formulario = () => {
  const { status: painel, isLive } = usePainelStatus();
  const [submitted, setSubmitted] = useState(false);
  const [form, setForm] = useState({
    name: "",
    email: "",
    whatsapp: "",
    empresa: "",
    segmento: "",
    plano: "",
    mensagem: "",
    website: "",
    consent: false,
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const configuredSegments = painel.segmentos.length > 0 ? painel.segmentos : DEFAULT_SEGMENTS;
  const showSegmentAvailability = isLive
    && painel.segmentos.length > 0
    && painel.segmentos_consistente;
  const selectedSegment = configuredSegments.find((segment) => segment.slug === form.segmento);
  const isOccupiedSegment = showSegmentAvailability && selectedSegment?.ocupado === true;
  const isMonthlyPlan = form.plano === "Mensal";
  const isWaitlist = isOccupiedSegment && !isMonthlyPlan;
  const selectedPlanPrice = findPlanPrice(
    painel.planos,
    form.plano.toLocaleLowerCase("pt-BR"),
  );
  const formWhatsAppMessage = selectedPlanPrice
    ? `Olá! Tenho interesse no plano ${selectedPlanPrice.nome} por ${formatPrice(selectedPlanPrice.preco_efetivo)} por mês${
      selectedPlanPrice.em_campanha && selectedPlanPrice.rotulo && selectedPlanPrice.validade
        ? `, na campanha "${selectedPlanPrice.rotulo}", válida até ${formatCampaignDate(selectedPlanPrice.validade)}`
        : ""
    }.`
    : pitchText;

  useEffect(() => {
    const handleSegmentSelection = (event: Event) => {
      const slug = (event as CustomEvent<{ slug?: string }>).detail?.slug;
      if (slug) {
        setForm((current) => ({ ...current, segmento: slug }));
      }
    };
    window.addEventListener(SEGMENT_SELECT_EVENT, handleSegmentSelection);
    return () => window.removeEventListener(SEGMENT_SELECT_EVENT, handleSegmentSelection);
  }, []);

  const setFieldMessage = (event: React.InvalidEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const field = event.currentTarget;
    const label = field.getAttribute("data-label") ?? "Este campo";

    if (field.validity.valueMissing) {
      field.setCustomValidity(`${label} é obrigatório.`);
    } else if (field.validity.typeMismatch) {
      field.setCustomValidity(`Informe um valor válido para ${label.toLowerCase()}.`);
    } else {
      field.setCustomValidity("Revise este campo.");
    }
  };

  const clearFieldMessage = (event: React.FormEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    event.currentTarget.setCustomValidity("");
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name || !form.email || !form.whatsapp || !form.segmento || !form.consent) {
      toast.error("Preencha os campos obrigatórios.");
      return;
    }
    setIsSubmitting(true);
    try {
      const attribution = getAttribution();
      const response = await fetch(FORM_ENDPOINT, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          nome: form.name,
          email: form.email,
          whatsapp: form.whatsapp,
          empresa: form.empresa || "Não informado",
          segmento: selectedSegment?.nome || form.segmento,
          segmento_slug: form.segmento,
          lista_espera: isWaitlist,
          plano: form.plano || "Não informado",
          mensagem: form.mensagem || "Não informado",
          subject: `Solicitação de proposta - ${form.name}`,
          ...attribution,
          page_url: window.location.href,
          consent: true,
          consent_text: CONSENT_TEXT,
          consent_at: new Date().toISOString(),
          website: form.website,
        }),
      });

      if (!response.ok) {
        throw new Error("Erro ao enviar proposta");
      }

      const trackedWindow = window as typeof window & { dataLayer?: Record<string, string>[] };
      trackedWindow.dataLayer = trackedWindow.dataLayer || [];
      trackedWindow.dataLayer.push({
        event: "proposal_form_success",
        plan_name: form.plano || "(nenhum)",
        utm_source: attribution.utm_source || "(direto)",
        utm_campaign: attribution.utm_campaign || "(nenhuma)",
      });

      setSubmitted(true);
      toast.success("Proposta enviada com sucesso!");
    } catch (error) {
      toast.error("Não foi possível enviar agora. Tente novamente.");
    } finally {
      setIsSubmitting(false);
    }
  };

  if (submitted) {
    return (
      <section id="proposta" className="scroll-mt-28 py-20 relative">
        <div className="container mx-auto px-4 max-w-2xl text-center">
          <div className="glass-card neon-gradient-border rounded-xl p-12">
            <CheckCircle className="w-16 h-16 text-neon-cyan mx-auto mb-6" />
            <h2 className="font-heading text-3xl font-bold mb-4">Proposta enviada!</h2>
            <p className="text-muted-foreground mb-3">
              Entraremos em contato em breve com uma proposta personalizada.
            </p>
            <p className="text-sm text-muted-foreground mb-8">
              Se quiser acelerar o atendimento, abra o WhatsApp agora mesmo.
            </p>
            <a
              href={buildWhatsAppLink(formWhatsAppMessage, "formulario")}
              onClick={() => trackWhatsAppClick("formulario", form.plano)}
              target="_blank"
              rel="noopener noreferrer"
              className="btn-neon inline-flex items-center gap-2"
            >
              <MessageCircle className="w-5 h-5" />
              Falar no WhatsApp
            </a>
          </div>
        </div>
      </section>
    );
  }

  return (
    <section id="proposta" className="scroll-mt-28 py-20 relative particles-bg">
      <div className="container mx-auto px-4">
        <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-12">
          Escolha seu plano e <span className="neon-gradient-text">consulte seu segmento</span>.
        </h2>

        <div className="grid gap-8 max-w-3xl mx-auto">
          {/* Form */}
          <form onSubmit={handleSubmit} className="glass-card neon-gradient-border rounded-xl p-6 sm:p-8 space-y-5">
            <div className="absolute -left-[9999px]" aria-hidden="true">
              <label htmlFor="website">Website</label>
              <input
                id="website"
                name="website"
                type="text"
                value={form.website}
                onChange={(e) => setForm({ ...form, website: e.target.value })}
                autoComplete="off"
                tabIndex={-1}
                maxLength={255}
              />
            </div>
            <div className="text-center">
              <p className="text-sm text-muted-foreground">
                {isLive ? `São ${painel.vagas_restantes} vagas no rodízio. ` : ""}
                A exclusividade de segmento começa no plano trimestral. Me diga o seu ramo e eu retorno com a disponibilidade e o valor.
              </p>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1.5">Nome *</label>
              <input
                type="text"
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                onInvalid={setFieldMessage}
                onInput={clearFieldMessage}
                data-label="Nome"
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Seu nome"
                maxLength={120}
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1.5">E-mail *</label>
              <input
                type="email"
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                onInvalid={setFieldMessage}
                onInput={clearFieldMessage}
                data-label="E-mail"
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="seu@email.com"
                maxLength={255}
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1.5">WhatsApp *</label>
              <input
                type="tel"
                value={form.whatsapp}
                onChange={(e) => setForm({ ...form, whatsapp: e.target.value })}
                onInvalid={setFieldMessage}
                onInput={clearFieldMessage}
                data-label="WhatsApp"
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="(62) 99999-9999"
                maxLength={255}
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1.5">Empresa</label>
              <input
                type="text"
                value={form.empresa}
                onChange={(e) => setForm({ ...form, empresa: e.target.value })}
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Nome da empresa (opcional)"
                maxLength={120}
              />
            </div>
            <div>
              <label htmlFor="segmento" className="block text-sm font-medium mb-1.5">Segmento *</label>
              <select
                id="segmento"
                value={form.segmento}
                onChange={(e) => setForm({ ...form, segmento: e.target.value })}
                onInvalid={setFieldMessage}
                onInput={clearFieldMessage}
                data-label="Segmento"
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                required
              >
                <option value="">Selecione o seu segmento</option>
                {configuredSegments.map((segment) => (
                  <option key={segment.slug} value={segment.slug}>
                    {segment.nome}{showSegmentAvailability && segment.ocupado ? " — ocupado" : ""}
                  </option>
                ))}
              </select>
              {isOccupiedSegment && (
                <p className="mt-2 rounded-lg border border-amber-700/60 bg-amber-950/30 px-3 py-2 text-sm text-amber-200">
                  {isMonthlyPlan
                    ? "Seu segmento já tem um anunciante com exclusividade. No plano mensal isso não impede sua entrada, mas a categoria não fica travada para você."
                    : "Este segmento já está ocupado. Posso avisar assim que vagar — seu contato será incluído na lista de espera."}
                </p>
              )}
            </div>
            <div>
              <label htmlFor="plano" className="block text-sm font-medium mb-1.5">Plano desejado</label>
              <select
                id="plano"
                value={form.plano}
                onChange={(e) => setForm({ ...form, plano: e.target.value })}
                onInvalid={setFieldMessage}
                onInput={clearFieldMessage}
                data-label="Plano desejado"
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">Selecione um plano</option>
                {PLAN_OPTIONS.map((p) => (
                  <option key={p} value={p}>{p}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1.5">Mensagem</label>
              <textarea
                value={form.mensagem}
                onChange={(e) => setForm({ ...form, mensagem: e.target.value })}
                onInvalid={setFieldMessage}
                onInput={clearFieldMessage}
                data-label="Mensagem"
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary h-24 resize-none"
                placeholder="Sua mensagem (opcional)"
                maxLength={2000}
              />
            </div>
            <label className="flex items-start gap-3 cursor-pointer">
              <input
                type="checkbox"
                checked={form.consent}
                onChange={(e) => setForm({ ...form, consent: e.target.checked })}
                className="mt-1 accent-neon-cyan"
              />
              <span className="text-xs text-muted-foreground">{CONSENT_TEXT}</span>
            </label>
            <button
              type="submit"
              className="btn-neon w-full flex items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-70"
              disabled={isSubmitting}
            >
              <Send className="w-5 h-5" />
              {isSubmitting ? "Enviando..." : "Quero receber uma proposta"}
            </button>
            <a
              href={buildWhatsAppLink(formWhatsAppMessage, "formulario_alternativa")}
              onClick={() => trackWhatsAppClick("formulario_alternativa", form.plano)}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center justify-center gap-2 text-center text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
              <MessageCircle className="h-4 w-4" />
              Prefiro falar no WhatsApp
            </a>
          </form>

        </div>
      </div>
    </section>
  );
};

export default Formulario;
