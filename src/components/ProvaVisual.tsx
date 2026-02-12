const ProvaVisual = () => (
  <section className="py-20 relative overflow-hidden min-h-[70vh] flex flex-col justify-center">
    {/* Vídeo de fundo com recortes curvos nas laterais */}
    <div
      className="absolute inset-0 flex items-center justify-center"
      style={{
        clipPath: "ellipse(88% 100% at 50% 50%)",
      }}
    >
      <video
        src="/led1.mp4"
        autoPlay
        loop
        muted
        playsInline
        className="w-full h-full object-cover min-w-full min-h-full"
        aria-hidden
      />
      {/* Overlay escuro para legibilidade do texto */}
      <div
        className="absolute inset-0 bg-black/50"
        aria-hidden
      />
    </div>

    {/* Conteúdo em cima do vídeo */}
    <div className="container mx-auto px-4 relative z-10">
      <h2 className="font-heading text-3xl sm:text-4xl font-bold text-center mb-4 text-white drop-shadow-lg">
        O painel mais visível da região
      </h2>
      <p className="text-muted-foreground text-center text-lg max-w-xl mx-auto text-white/90 drop-shadow-md">
        Impossível passar e não notar.
      </p>
    </div>
  </section>
);

export default ProvaVisual;
