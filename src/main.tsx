import { createRoot } from "react-dom/client";
import App from "./App.tsx";
import "./index.css";

document.documentElement.dataset.build = import.meta.env.VITE_BUILD_SHA || "local";

createRoot(document.getElementById("root")!).render(<App />);
