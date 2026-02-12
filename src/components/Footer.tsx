import logo from "@/assets/logo.png";

const links = [
  { label: "Vantagens", href: "#vantagens" },
  { label: "Planos", href: "#planos" },
  { label: "Localização", href: "#localizacao" },
  { label: "Receber Proposta", href: "#proposta" },
];

const Footer = () => (
  <footer className="border-t border-border py-12">
    <div className="container mx-auto px-4">
      <div className="flex flex-col md:flex-row items-center justify-between gap-6 mb-8">
        <div className="flex flex-col items-center md:items-start gap-3">
          <img src={logo} alt="Gênio Visual" className="h-[55px] md:h-[70px] w-auto object-contain" style={{ filter: 'drop-shadow(0 0 8px hsl(189 98% 51% / 0.35))' }} />
          <span className="font-heading font-bold text-xl neon-gradient-text tracking-[0.15em]">GÊNIO VISUAL</span>
          <span className="text-muted-foreground text-xs tracking-widest">OOH Premium • Goiânia/GO</span>
        </div>
        <div className="flex flex-wrap gap-6">
          {links.map((l) => (
            <a key={l.href} href={l.href} className="text-muted-foreground hover:text-foreground transition-colors text-sm">
              {l.label}
            </a>
          ))}
        </div>
      </div>
      <div className="text-center">
        <p className="text-muted-foreground text-sm mb-2">Gênio Visual • Painéis de LED em Goiânia/GO</p>
        <p className="text-muted-foreground text-xs max-w-2xl mx-auto">
          Desenvolvido por Lucas Gonçalves Júnior - 2026
        </p>
        <a href="/admin.php" className="text-muted-foreground/30 hover:text-muted-foreground/60 text-[10px] mt-2 inline-block transition-colors">
          admin
        </a>
      </div>
    </div>
  </footer>
);

export default Footer;
