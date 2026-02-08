import { MessageCircle } from "lucide-react";

const WHATSAPP_NUMBER = "5562999999999";
const WHATSAPP_MSG = encodeURIComponent("Olá! Quero anunciar no painel da Gênio Visual. Me envie os horários disponíveis e a melhor proposta.");

const WhatsAppFloat = () => (
  <a
    href={`https://wa.me/${WHATSAPP_NUMBER}?text=${WHATSAPP_MSG}`}
    target="_blank"
    rel="noopener noreferrer"
    className="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full flex items-center justify-center animate-pulse-neon transition-transform hover:scale-110"
    style={{ background: "#25D366" }}
    aria-label="Falar no WhatsApp"
  >
    <MessageCircle className="w-7 h-7 text-primary-foreground" />
  </a>
);

export default WhatsAppFloat;
