import js from "@eslint/js";
import pluginVue from "eslint-plugin-vue";
import eslintConfigPrettier from "eslint-config-prettier";

export default [
    {
        ignores: [
            "vendor/**",
            "public/**",
            "node_modules/**",
            "storage/**",
            "bootstrap/ssr/**",
        ],
    },
    // Configuración base de JS
    js.configs.recommended,

    // Configuración de Vue (Essential -> Recommended es mejor para evitar bugs)
    ...pluginVue.configs["flat/recommended"],

    // Desactivar reglas de formato que chocan con Prettier
    eslintConfigPrettier,

    {
        rules: {
            // Aquí puedes personalizar tus reglas
            "vue/multi-word-component-names": "off",
            "no-undef": "off",
            "no-unused-vars": "warn",
            "vue/no-unused-vars": "warn",
            "vue/attributes-order": "off", // Desactivamos orden estricto de atributos para evitar ruido
            "vue/first-attribute-linebreak": "off",
        },
        languageOptions: {
            globals: {
                // Define globales si es necesario, ejemplo para browser:
                window: "readonly",
                document: "readonly",
                console: "readonly",
                $: "readonly",
                jQuery: "readonly",
                axios: "readonly",
            },
        },
    },
];
