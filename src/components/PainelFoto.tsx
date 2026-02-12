const PainelFoto = () => (
  <section className="py-12 relative overflow-hidden">
    <div className="container mx-auto px-4">
      <div
        className="relative mx-auto overflow-hidden rounded-3xl max-w-3xl"
        style={{
          borderRadius: "2rem",
          clipPath: "inset(0 round 2rem)",
        }}
      >
        <img
          src="/painel-einstein.png"
          alt="Painel LED - Colégio Einstein"
          className="w-full h-auto object-cover"
          loading="lazy"
        />
      </div>
    </div>
  </section>
);

export default PainelFoto;
