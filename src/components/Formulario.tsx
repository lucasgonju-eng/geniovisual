import { useState } from "react";
import { MessageCircle, Send, CheckCircle } from "lucide-react";
import { toast } from "sonner";

const WHATSAPP_NUMBER = "5562999999999";
const pitchText = "Olá! Quero anunciar no painel da Gênio Visual. Me envie os horários disponíveis e a melhor proposta para o plano anual.";

const planOptions = ["Bronze (Mensal)", "Prata (Trimestral)", "Ouro (Semestral)", "Diamante (Anual)", "Black (Bienal)"];

const Formulario = () => {
  const [submitted, setSubmitted] = useState(false);
  const [form, setForm] = useState({
    name: "",
    whatsapp: "",
    empresa: "",
    plano: "",
    mensagem: "",
    consent: false,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name || !form.whatsapp || !form.consent) {
      toast.error("Preencha os campos obrigatórios.");
      return;
    }
    // Simulated form submit — ready for backend integration
    console.log("Lead submitted:", form);
    setSubmitted(true);
    toast.success("Proposta enviada com sucesso!");
  };

  if (submitted) {
    return (
      <section id="proposta" className="py-20 relative">
        <div className="container mx-auto px-4 max-w-2xl text-center">
          <div className="glass-card neon-gradient-border rounded-xl p-12">
            <CheckCircle className="w-16 h-16 text-neon-cyan mx-auto mb-6" />
            <h2 className="font-heading text-3xl font-bold mb-4">Proposta enviada!</h2>
            <p className="text-muted-foreground mb-8">Entraremos em contato em breve. Ou se preferir, fale agora pelo WhatsApp:</p>
            <a
              href={`https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(pitchText)}`}
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
    <section id="proposta" className="py-20 relative particles-bg">
      <div className="container mx-auto px-4">
        <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-12">
          Receber <span className="neon-gradient-text">Proposta</span>
        </h2>

        <div className="grid gap-8 max-w-3xl mx-auto">
          {/* Form */}
          <form onSubmit={handleSubmit} className="glass-card neon-gradient-border rounded-xl p-8 space-y-5">
            <div>
              <label className="block text-sm font-medium mb-1.5">Nome *</label>
              <input
                type="text"
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Seu nome"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1.5">WhatsApp *</label>
              <input
                type="tel"
                value={form.whatsapp}
                onChange={(e) => setForm({ ...form, whatsapp: e.target.value })}
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="(62) 99999-9999"
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
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1.5">Plano desejado</label>
              <select
                value={form.plano}
                onChange={(e) => setForm({ ...form, plano: e.target.value })}
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">Selecione um plano</option>
                {planOptions.map((p) => (
                  <option key={p} value={p}>{p}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1.5">Mensagem</label>
              <textarea
                value={form.mensagem}
                onChange={(e) => setForm({ ...form, mensagem: e.target.value })}
                className="w-full rounded-lg bg-muted border border-border px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary h-24 resize-none"
                placeholder="Sua mensagem (opcional)"
              />
            </div>
            <label className="flex items-start gap-3 cursor-pointer">
              <input
                type="checkbox"
                checked={form.consent}
                onChange={(e) => setForm({ ...form, consent: e.target.checked })}
                className="mt-1 accent-neon-cyan"
                required
              />
              <span className="text-xs text-muted-foreground">Autorizo o contato da Gênio Visual para envio de proposta comercial.</span>
            </label>
            <button type="submit" className="btn-neon w-full flex items-center justify-center gap-2">
              <Send className="w-5 h-5" />
              Receber proposta agora
            </button>
          </form>

        </div>
      </div>
    </section>
  );
};

export default Formulario;
