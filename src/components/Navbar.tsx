import { useState } from "react";
import { Menu, X, MessageCircle } from "lucide-react";
import logo from "@/assets/logo-optimized.png";

const WHATSAPP_NUMBER = "+5562995077995";
const WHATSAPP_MSG = encodeURIComponent("Olá! Quero anunciar no painel da Gênio Visual. Me envie os horários disponíveis e a melhor proposta.");

const navLinks = [
  { label: "Vantagens", href: "#vantagens" },
  { label: "Planos", href: "#planos" },
  { label: "Localização", href: "#localizacao" },
  { label: "Receber Proposta", href: "#proposta" },
];

const Navbar = () => {
  const [open, setOpen] = useState(false);
  const scrollToSection = (href: string) => (event: React.MouseEvent<HTMLAnchorElement>) => {
    const targetId = href.replace("#", "");
    const target = document.getElementById(targetId);

    if (!target) {
      return;
    }

    event.preventDefault();
    const offset = window.innerWidth < 768 ? 92 : 108;
    const top = target.getBoundingClientRect().top + window.scrollY - offset;

    window.history.replaceState(null, "", href);
    window.scrollTo({ top, behavior: "smooth" });
    setOpen(false);
  };

  const handleTopClick = (event: React.MouseEvent<HTMLAnchorElement>) => {
    event.preventDefault();
    window.history.replaceState(null, "", "#");
    window.scrollTo({ top: 0, behavior: "smooth" });
    setOpen(false);
  };

  return (
    <nav className="fixed top-0 left-0 right-0 z-50 glass-card border-b border-white/5 bg-background/80 backdrop-blur-xl">
      <div className="container mx-auto flex items-center justify-between px-4 py-3 md:py-4">
        <a href="#" onClick={handleTopClick} className="flex items-center gap-2 min-w-0">
          <img
            src={logo}
            alt="Gênio Visual"
            className="h-16 w-16 sm:h-20 sm:w-20 md:h-24 md:w-24 object-contain flex-shrink-0"
            style={{ filter: "drop-shadow(0 0 8px hsl(189 98% 51% / 0.35))" }}
          />
          <span className="font-heading font-bold text-lg sm:text-2xl md:text-[1.75rem] neon-gradient-text tracking-[0.12em] leading-none">
            GÊNIO VISUAL
          </span>
        </a>

        <div className="hidden md:flex items-center gap-8">
          {navLinks.map((l) => (
            <a
              key={l.href}
              href={l.href}
              onClick={scrollToSection(l.href)}
              className="text-muted-foreground hover:text-foreground transition-colors text-sm font-medium"
            >
              {l.label}
            </a>
          ))}
          <a
            href={`https://wa.me/${WHATSAPP_NUMBER}?text=${WHATSAPP_MSG}`}
            target="_blank"
            rel="noopener noreferrer"
            className="btn-neon flex items-center gap-2 !px-6 !py-2.5 !text-sm"
            aria-label="Abrir WhatsApp para falar com a Gênio Visual"
          >
            <MessageCircle className="w-4 h-4" />
            Falar no WhatsApp
          </a>
        </div>

        <button
          onClick={() => setOpen(!open)}
          className="md:hidden text-foreground rounded-lg p-2 transition-colors hover:bg-white/5"
          aria-expanded={open}
          aria-label={open ? "Fechar menu" : "Abrir menu"}
        >
          {open ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>
      </div>

      {open && (
        <div className="md:hidden glass-card border-t border-white/5 px-4 pb-4 pt-3">
          {navLinks.map((l) => (
            <a
              key={l.href}
              href={l.href}
              onClick={scrollToSection(l.href)}
              className="block rounded-lg px-2 py-3 text-base text-muted-foreground hover:bg-white/5 hover:text-foreground transition-colors font-medium"
            >
              {l.label}
            </a>
          ))}
          <a
            href={`https://wa.me/${WHATSAPP_NUMBER}?text=${WHATSAPP_MSG}`}
            target="_blank"
            rel="noopener noreferrer"
            className="btn-neon mt-3 flex items-center justify-center gap-2 !px-4 !py-3 !text-sm"
            aria-label="Abrir WhatsApp para falar com a Gênio Visual"
          >
            <MessageCircle className="w-4 h-4" />
            Falar no WhatsApp
          </a>
          <p className="mt-2 text-center text-xs text-muted-foreground">
            Atendimento imediato em nova aba.
          </p>
        </div>
      )}
    </nav>
  );
};

export default Navbar;
