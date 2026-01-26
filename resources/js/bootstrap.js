import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.headers.common["Accept"] = "application/json";
window.axios.defaults.headers.common["Content-Type"] = "application/json";

// Token CSRF
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
} else {
    console.error("CSRF token not found");
}

// Interceptor para errores globales
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response) {
            console.error("API Error:", error.response.data);
        }
        return Promise.reject(error);
    }
);

// Importar jQuery PRIMERO y exponerlo globalmente
import $ from "jquery";
window.$ = window.jQuery = $;

// Importar Select2 como factory y ejecutarlo
import select2 from "select2";

// Ejecutar select2 factory si es necesario
if (typeof select2 === "function") {
    // Es una factory, necesita ejecutarse
    const jQueryWithSelect2 = select2(window, $);
    // Asegurarse de que select2 esté en el prototype de jQuery
    if (jQueryWithSelect2 && typeof jQueryWithSelect2.fn.select2 !== "undefined") {
        $.fn.select2 = jQueryWithSelect2.fn.select2;
    }
}

// Importar CSS de Select2
import "select2/dist/css/select2.min.css";
import "select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css";

// Verificar que Select2 esté disponible
console.log("Verificando Select2...");
console.log("$ existe?", typeof $ !== "undefined");
console.log("$.fn existe?", typeof $.fn !== "undefined");
console.log("$.fn.select2 existe?", typeof $.fn.select2 !== "undefined");

if (typeof $.fn.select2 !== "undefined") {
    console.log("✓ Select2 cargado correctamente");
} else {
    console.error("✗ Error: Select2 no se cargó correctamente");
}
