const ProvaVisual = () => (
  <section className="py-20 relative overflow-hidden">
    <div className="container mx-auto px-4">
      {/* Título acima do vídeo */}
      <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-6 text-white drop-shadow-lg">
        O painel mais visível da região
      </h2>

      {/* Grid com vídeo e foto lado a lado */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl mx-auto">
        {/* Card do vídeo */}
        <div
          className="relative overflow-hidden rounded-3xl"
          style={{
            borderRadius: "2rem",
            clipPath: "inset(0 round 2rem)",
          }}
        >
          <video
            src="/led1.mp4"
            autoPlay
            loop
            muted
            playsInline
            className="w-full h-full object-cover"
          />
          <div className="absolute inset-0 bg-black/30" aria-hidden />
          <div className="absolute inset-0 flex items-end justify-center pb-6">
            <p className="text-white/90 text-center text-lg font-medium drop-shadow-md">
              Impossível passar e não notar.
            </p>
          </div>
        </div>

        {/* Card da foto */}
        <div
          className="relative overflow-hidden rounded-3xl"
          style={{
            borderRadius: "2rem",
            clipPath: "inset(0 round 2rem)",
          }}
        >
          <img
            src="/painel-einstein.png"
            alt="Painel LED - Colégio Einstein"
            className="w-full h-full object-cover"
            loading="lazy"
          />
        </div>
      </div>
    </div>
  </section>
);

export default ProvaVisual;
