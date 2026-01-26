import "./bootstrap";
import { createApp } from "vue";

// Importar Bootstrap
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap/dist/js/bootstrap.bundle.min.js";

// Importar DataTables
import "datatables.net-bs5/css/dataTables.bootstrap5.min.css";
import "datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css";

// Select2 ya se importa en bootstrap.js

// Importar componente principal
import App from "./App.vue";

// Crear aplicación Vue
const app = createApp(App);

// Montar aplicación
app.mount("#app");
