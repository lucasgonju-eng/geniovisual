import { useState } from "react";
import { Menu, X, MessageCircle } from "lucide-react";
import logo from "@/assets/logo.png";

const WHATSAPP_NUMBER = "5562999999999";
const WHATSAPP_MSG = encodeURIComponent("Olá! Quero anunciar no painel da Gênio Visual. Me envie os horários disponíveis e a melhor proposta.");

const navLinks = [
  { label: "Vantagens", href: "#vantagens" },
  { label: "Planos", href: "#planos" },
  { label: "Localização", href: "#localizacao" },
  { label: "Receber Proposta", href: "#proposta" },
];

const Navbar = () => {
  const [open, setOpen] = useState(false);

  return (
    <nav className="fixed top-0 left-0 right-0 z-50 glass-card border-b border-white/5 bg-background/80 backdrop-blur-xl">
      <div className="container mx-auto flex items-center justify-between py-5 md:py-6 px-4">
        <a href="#" className="flex items-center gap-0.5 md:gap-1">
          <img src={logo} alt="Gênio Visual" className="h-[110px] w-[110px] md:h-[140px] md:w-[140px] object-contain flex-shrink-0" style={{ filter: 'drop-shadow(0 0 8px hsl(189 98% 51% / 0.35))' }} />
          <span className="font-heading font-bold text-xl md:text-2xl neon-gradient-text tracking-[0.15em]">GÊNIO VISUAL</span>
        </a>

        <div className="hidden md:flex items-center gap-8">
          {navLinks.map((l) => (
            <a key={l.href} href={l.href} className="text-muted-foreground hover:text-foreground transition-colors text-sm font-medium">
              {l.label}
            </a>
          ))}
          <a
            href={`https://wa.me/${WHATSAPP_NUMBER}?text=${WHATSAPP_MSG}`}
            target="_blank"
            rel="noopener noreferrer"
            className="btn-neon flex items-center gap-2 !px-6 !py-2.5 !text-sm"
          >
            <MessageCircle className="w-4 h-4" />
            Fechar no WhatsApp
          </a>
        </div>

        <button onClick={() => setOpen(!open)} className="md:hidden text-foreground">
          {open ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>
      </div>

      {open && (
        <div className="md:hidden glass-card border-t border-white/5 px-4 pb-4">
          {navLinks.map((l) => (
            <a key={l.href} href={l.href} onClick={() => setOpen(false)} className="block py-3 text-muted-foreground hover:text-foreground transition-colors font-medium">
              {l.label}
            </a>
          ))}
          <a
            href={`https://wa.me/${WHATSAPP_NUMBER}?text=${WHATSAPP_MSG}`}
            target="_blank"
            rel="noopener noreferrer"
            className="btn-neon flex items-center justify-center gap-2 mt-3 !text-sm"
          >
            <MessageCircle className="w-4 h-4" />
            Fechar no WhatsApp
          </a>
        </div>
      )}
    </nav>
  );
};

export default Navbar;
