import panelDay from "@/assets/panel-day.jpg";
import panelNight from "@/assets/panel-night.jpg";
import panelTraffic from "@/assets/panel-traffic.jpg";

const images = [
  { src: panelDay, alt: "Painel de LED durante o dia", label: "De dia" },
  { src: panelNight, alt: "Painel de LED à noite", label: "À noite" },
  { src: panelTraffic, alt: "Fluxo intenso na avenida", label: "Fluxo da via" },
];

const ProvaVisual = () => (
  <section className="py-20 relative">
    <div className="container mx-auto px-4">
      <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-4">
        O painel mais visível da região
      </h2>
      <p className="text-muted-foreground text-center text-lg mb-12 max-w-xl mx-auto">
        Impossível passar e não notar.
      </p>

      <div className="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        {images.map((img) => (
          <div key={img.label} className="glass-card rounded-xl overflow-hidden group">
            <div className="aspect-[3/4] overflow-hidden">
              <img
                src={img.src}
                alt={img.alt}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                loading="lazy"
              />
            </div>
            <div className="p-4 text-center">
              <span className="text-sm font-medium text-muted-foreground">{img.label}</span>
            </div>
          </div>
        ))}
      </div>
    </div>
  </section>
);

export default ProvaVisual;
