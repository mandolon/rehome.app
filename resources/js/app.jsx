import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

// Import pages directly to avoid dynamic import issues
import Home from './Pages/Home.jsx'
import SimpleTaskBoard from './Pages/SimpleTaskBoard.jsx'

const pages = {
  'Home': Home,
  'SimpleTaskBoard': SimpleTaskBoard,
}

createInertiaApp({
  resolve: name => {
    console.log('Resolving page:', name);
    const page = pages[name];
    if (!page) {
      console.error('Page not found:', name);
      return () => <div>Page not found: {name}</div>;
    }
    return page;
  },
  setup({ el, App, props }) {
    console.log('Setting up Inertia app with props:', props);
    createRoot(el).render(<App {...props} />)
  },
})