import Navbar from "@/components/Navbar";
import Hero from "@/components/Hero";
import ProvaVisual from "@/components/ProvaVisual";
import Vantagens from "@/components/Vantagens";
import Credibilidade from "@/components/Credibilidade";
import Planos from "@/components/Planos";
import PainelFoto from "@/components/PainelFoto";
import Localizacao from "@/components/Localizacao";
import FAQ from "@/components/FAQ";
import PainelAnuncie from "@/components/PainelAnuncie";
import Formulario from "@/components/Formulario";
import Footer from "@/components/Footer";
import WhatsAppFloat from "@/components/WhatsAppFloat";

const Index = () => (
  <main className="min-h-screen">
    <Navbar />
    <Hero />
    <ProvaVisual />
    <Vantagens />
    <Credibilidade />
    <Planos />
    <PainelFoto />
    <Localizacao />
    <FAQ />
    <PainelAnuncie />
    <Formulario />
    <Footer />
    <WhatsAppFloat />
  </main>
);

export default Index;
