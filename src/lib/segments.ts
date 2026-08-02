export type SegmentStatus = {
  slug: string;
  nome: string;
  ocupado: boolean;
};

export const DEFAULT_SEGMENTS: SegmentStatus[] = [
  { slug: "automotivo", nome: "Automotivo", ocupado: false },
  { slug: "imobiliario", nome: "Imobiliário", ocupado: false },
  { slug: "saude-odontologia", nome: "Saúde e Odontologia", ocupado: false },
  { slug: "educacao", nome: "Educação", ocupado: false },
  { slug: "alimentacao", nome: "Alimentação e Restaurantes", ocupado: false },
  { slug: "varejo-moda", nome: "Varejo e Moda", ocupado: false },
  { slug: "beleza-estetica", nome: "Beleza e Estética", ocupado: false },
  { slug: "academias", nome: "Academias e Fitness", ocupado: false },
  { slug: "financeiro", nome: "Serviços Financeiros", ocupado: false },
  { slug: "construcao", nome: "Construção e Reforma", ocupado: false },
  { slug: "tecnologia", nome: "Tecnologia", ocupado: false },
  { slug: "advocacia-contabilidade", nome: "Advocacia e Contabilidade", ocupado: false },
  { slug: "pet", nome: "Pet", ocupado: false },
];

export const SEGMENT_SELECT_EVENT = "gv:select-segment";

export const selectSegmentForForm = (slug: string) => {
  window.dispatchEvent(new CustomEvent(SEGMENT_SELECT_EVENT, { detail: { slug } }));
};
