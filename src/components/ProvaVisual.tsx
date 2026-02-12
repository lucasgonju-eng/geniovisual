const ProvaVisual = () => (
  <section className="py-20 relative overflow-hidden">
    <div className="container mx-auto px-4">
      {/* Título acima do vídeo */}
      <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-6 text-white drop-shadow-lg">
        O painel mais visível da região
      </h2>

      {/* Vídeo vertical com recortes curvos nas laterais */}
      <div
        className="relative mx-auto overflow-hidden rounded-3xl"
        style={{
          maxWidth: "calc(100% - 2rem)",
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
          className="w-full h-auto object-contain"
        />
        {/* Overlay escuro para legibilidade */}
        <div className="absolute inset-0 bg-black/30" aria-hidden />
        {/* Subtítulo sobre o vídeo */}
        <div className="absolute inset-0 flex items-center justify-center">
          <p className="text-white/90 text-center text-lg font-medium drop-shadow-md">
            Impossível passar e não notar.
          </p>
        </div>
      </div>
    </div>
  </section>
);

export default ProvaVisual;
