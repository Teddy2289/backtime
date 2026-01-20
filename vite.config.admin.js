// vite.config.admin.js (version finale)
import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path";

export default defineConfig({
    plugins: [vue()],
    root: ".",
    publicDir: false, // IMPORTANT : désactive le dossier public
    resolve: {
        alias: {
            "@": resolve(__dirname, "resources/js"),
            vue: "vue/dist/vue.esm-bundler.js",
        },
    },
    base: "/administrateur/",
    build: {
        outDir: "dist-admin",
        emptyOutDir: true,
        rollupOptions: {
            input: resolve(__dirname, "admin-spa.html"), // Votre fichier HTML
        },
        assetsInlineLimit: 0,
        copyPublicDir: false,
    },
});
