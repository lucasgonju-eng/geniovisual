import { getAttribution } from "@/lib/attribution";

export const WHATSAPP_NUMBER = "5562995077995";

type DataLayerEvent = {
  event: "whatsapp_click";
  cta_location: string;
  plan_name: string;
};

const campaignLabel = (): string => {
  const attribution = getAttribution();
  const source = attribution.utm_campaign || attribution.utm_source;

  return source
    .trim()
    .toLowerCase()
    .replace(/\s+/g, "-")
    .replace(/[^\p{L}\p{N}._-]/gu, "")
    .slice(0, 40);
};

export const buildWhatsAppLink = (text: string, ctaLocation: string): string => {
  const label = campaignLabel();
  const suffix = label ? ` [via: ${label}]` : "";
  const message = `${text.trim()}${suffix}`;

  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
};

export const trackWhatsAppClick = (ctaLocation: string, planName = ""): void => {
  if (typeof window === "undefined") {
    return;
  }

  const trackedWindow = window as typeof window & { dataLayer?: DataLayerEvent[] };
  trackedWindow.dataLayer = trackedWindow.dataLayer || [];
  trackedWindow.dataLayer.push({
    event: "whatsapp_click",
    cta_location: ctaLocation,
    plan_name: planName,
  });
};
