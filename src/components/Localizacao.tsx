import { MapPin, Navigation } from "lucide-react";

const Localizacao = () => {
  return (
    <section id="localizacao" className="scroll-mt-28 py-20 relative">
      <div className="container mx-auto px-4">
        <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-4">
          <span className="neon-gradient-text">Av. T-15</span>, Setor Bueno.
        </h2>
        <p className="text-muted-foreground text-center text-lg mb-12 max-w-2xl mx-auto">
          Em frente ao Colégio Einstein e a menos de 50 metros do Goiânia Shopping, num ponto onde o trânsito para no sinal. É a diferença entre ser visto de relance a 60 km/h e ser lido com calma.
        </p>

        <div className="mx-auto max-w-2xl">
          <div className="glass-card neon-gradient-border rounded-xl p-8 flex flex-col justify-between">
            <div>
              <MapPin className="w-8 h-8 text-neon-cyan mb-4 mx-auto" />
              <h3 className="font-heading text-xl font-semibold mb-2 text-center">Onde sua marca aparece</h3>
              <p className="text-muted-foreground mb-6">
                O mapa abaixo abre a rota para o ponto do painel na Av. T-15, em frente ao Colégio Einstein.
              </p>
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
