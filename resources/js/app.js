import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import i18n from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'WizFlussi';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n);
        
        // Set locale from user preference
        if (props.initialPage.props.auth?.user?.language) {
            i18n.global.locale.value = props.initialPage.props.auth.user.language;
        }
        
        return app.mount(el);
    },
    progress: {
        color: '#3b82f6',
    },
});
