import { MessageCircle } from "lucide-react";
import { buildWhatsAppLink, trackWhatsAppClick } from "@/lib/whatsapp";

const WHATSAPP_MSG = "Olá! Quero anunciar no painel da Gênio Visual. Me envie os horários disponíveis e a melhor proposta.";

const WhatsAppFloat = () => (
  <a
    href={buildWhatsAppLink(WHATSAPP_MSG, "float")}
    onClick={() => trackWhatsAppClick("float")}
    target="_blank"
    rel="noopener noreferrer"
    className="fixed bottom-4 right-4 z-50 flex h-12 w-12 items-center justify-center rounded-full shadow-lg animate-pulse-neon transition-transform hover:scale-110 sm:bottom-6 sm:right-6 sm:h-14 sm:w-14"
    style={{ background: "#25D366" }}
    aria-label="Falar no WhatsApp em nova aba"
  >
    <MessageCircle className="h-6 w-6 text-primary-foreground sm:h-7 sm:w-7" />
  </a>
);

export default WhatsAppFloat;
