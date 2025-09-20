/// <reference types="vite/client" />
import './bootstrap';
import '../css/app.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';

// Load BOTH TSX and JSX pages
const pages = {
  ...import.meta.glob('./Pages/**/*.tsx'),
  ...import.meta.glob('./Pages/**/*.jsx'),
};

const appName = import.meta.env.VITE_APP_NAME || 'PreConstruct';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => pages[`./Pages/${name}.tsx`] ?? pages[`./Pages/${name}.jsx`],
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#3b82f6',
    },
});
