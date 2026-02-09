import { MapPin, Navigation, Copy } from "lucide-react";
import { toast } from "sonner";

const stats = [
  { value: "1,7 mi", label: "impactos/mês" },
  { value: "19h", label: "de operação/dia" },
  { value: "15", label: "marcas no rodízio" },
];

const pitchText = "Olá! Quero anunciar no painel da Gênio Visual. Me envie os horários disponíveis e a melhor proposta para o plano anual.";

const Localizacao = () => {
  const copyPitch = () => {
    navigator.clipboard.writeText(pitchText);
    toast.success("Mensagem copiada!");
  };

  return (
    <section id="localizacao" className="py-20 relative">
      <div className="container mx-auto px-4">
        <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-4">
          <span className="neon-gradient-text">Localização</span> Estratégica
        </h2>
        <p className="text-muted-foreground text-center text-lg mb-12 max-w-2xl mx-auto">
          Em frente ao Colégio Einstein, a 40 m do Goiânia Shopping. Fluxo intenso e visibilidade máxima todos os dias.
        </p>

        <div className="grid grid-cols-3 gap-4 max-w-lg mx-auto mb-12">
          {stats.map((s) => (
            <div key={s.label} className="text-center">
              <div className="font-heading text-2xl sm:text-3xl font-bold neon-gradient-text">{s.value}</div>
              <div className="text-muted-foreground text-xs sm:text-sm">{s.label}</div>
            </div>
          ))}
        </div>

        <div className="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
          <div className="glass-card neon-gradient-border rounded-xl p-8 flex flex-col justify-between">
            <div>
              <MapPin className="w-8 h-8 text-neon-cyan mb-4" />
              <h3 className="font-heading text-xl font-semibold mb-2">Endereço do Painel</h3>
              <p className="text-muted-foreground mb-6">Em frente ao Colégio Einstein, a 40 m do Goiânia Shopping. Fluxo intenso e visibilidade máxima todos os dias.</p>
            </div>
            <a
              href="https://maps.app.goo.gl/ddU4i4T63wLnjseX8"
              target="_blank"
              rel="noopener noreferrer"
              className="btn-neon-outline flex items-center justify-center gap-2 !text-sm !py-3"
            >
              <Navigation className="w-4 h-4" />
              Abrir rota no Google Maps
            </a>
          </div>

        </div>
      </div>
    </section>
  );
};

export default Localizacao;
