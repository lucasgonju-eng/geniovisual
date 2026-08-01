export const ATTRIBUTION_STORAGE_KEY = "gv_attr";

const ATTRIBUTION_PARAMS = [
  "utm_source",
  "utm_medium",
  "utm_campaign",
  "utm_content",
  "utm_term",
  "gclid",
  "fbclid",
  "li_fat_id",
] as const;

export type Attribution = Record<(typeof ATTRIBUTION_PARAMS)[number], string> & {
  landing_path: string;
  referrer: string;
};

const emptyAttribution = (): Attribution => ({
  utm_source: "",
  utm_medium: "",
  utm_campaign: "",
  utm_content: "",
  utm_term: "",
  gclid: "",
  fbclid: "",
  li_fat_id: "",
  landing_path: "",
  referrer: "",
});

const readStoredAttribution = (): Attribution | null => {
  if (typeof window === "undefined") {
    return null;
  }

  try {
    const stored = window.sessionStorage.getItem(ATTRIBUTION_STORAGE_KEY);
    if (!stored) {
      return null;
    }

    const parsed = JSON.parse(stored) as Partial<Attribution>;
    const attribution = emptyAttribution();
    for (const key of [...ATTRIBUTION_PARAMS, "landing_path", "referrer"] as const) {
      attribution[key] = typeof parsed[key] === "string" ? parsed[key] : "";
    }
    return attribution;
  } catch {
    return null;
  }
};

const captureAttribution = (): Attribution => {
  const existing = readStoredAttribution();
  if (existing) {
    return existing;
  }

  const attribution = emptyAttribution();
  if (typeof window === "undefined") {
    return attribution;
  }

  const params = new URLSearchParams(window.location.search);
  for (const key of ATTRIBUTION_PARAMS) {
    attribution[key] = params.get(key)?.trim() ?? "";
  }
  attribution.landing_path = `${window.location.pathname}${window.location.search}${window.location.hash}`;
  attribution.referrer = document.referrer;

  try {
    window.sessionStorage.setItem(ATTRIBUTION_STORAGE_KEY, JSON.stringify(attribution));
  } catch {
    // A atribuição continua disponível nesta leitura quando o storage estiver bloqueado.
  }

  return attribution;
};

export const getAttribution = (): Attribution => captureAttribution();

if (typeof window !== "undefined") {
  captureAttribution();
}
