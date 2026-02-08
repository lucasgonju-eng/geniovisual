import { Headphones, Paintbrush, BarChart3, ShieldCheck } from "lucide-react";

const items = [
  { icon: Headphones, label: "Atendimento rápido" },
  { icon: Paintbrush, label: "Troca de criativo facilitada" },
  { icon: BarChart3, label: "Suporte para campanhas" },
  { icon: ShieldCheck, label: "Transparência desde o primeiro contato" },
];

const Credibilidade = () => (
  <section className="py-16 neon-gradient-bg">
    <div className="container mx-auto px-4">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-6">
        {items.map((i) => (
          <div key={i.label} className="flex flex-col items-center text-center gap-3">
            <i.icon className="w-8 h-8 text-primary-foreground" />
            <span className="font-heading font-semibold text-primary-foreground text-sm sm:text-base">{i.label}</span>
          </div>
        ))}
      </div>
    </div>
  </section>
);

export default Credibilidade;
