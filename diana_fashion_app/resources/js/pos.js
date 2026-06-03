import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';

// Import POS Terminal View
import PosTerminal from './Pages/POS/Views/PosTerminal.vue';

const routes = [
    { path: '/pos', component: PosTerminal, name: 'pos.terminal' },
    { path: '/pos/:pathMatch(.*)*', redirect: '/pos' }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

const app = createApp({});
app.use(createPinia());
app.use(router);
app.mount('#pos-app');
