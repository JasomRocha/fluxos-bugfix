import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import Layout from './Componentes/Layout.vue';

createInertiaApp({
    resolve: (nome) => {
        const paginas = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const pagina = paginas[`./Pages/${nome}.vue`];
        pagina.default.layout = pagina.default.layout ?? (nome.startsWith('Auth/') ? undefined : Layout);
        return pagina;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).mount(el);
    },
});
