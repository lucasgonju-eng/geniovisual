import { MessageCircle } from "lucide-react";

const WHATSAPP_NUMBER = "+5562995077995";
const WHATSAPP_MSG = encodeURIComponent("Olá! Quero anunciar no painel da Gênio Visual. Me envie os horários disponíveis e a melhor proposta.");

const WhatsAppFloat = () => (
  <a
    href={`https://wa.me/${WHATSAPP_NUMBER}?text=${WHATSAPP_MSG}`}
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
