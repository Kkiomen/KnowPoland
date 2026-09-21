import { createInertiaApp } from '@inertiajs/vue3';

import { i18n } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
    withApp(app) {
        app.use(i18n);
    },
});
