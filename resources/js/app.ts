import { createApp } from "vue";
import { createPinia } from "pinia";
import router from "./router";
import App from "./App.vue";

// Créer l'application Vue
const app = createApp(App);

// Utiliser Pinia
const pinia = createPinia();
app.use(pinia);

// Utiliser le router
app.use(router);

// Monter l'application
app.mount("#app");
