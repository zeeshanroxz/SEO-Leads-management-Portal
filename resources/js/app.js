import '@fontsource-variable/inter';
import '../css/app.css';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

window.Alpine = Alpine;
Alpine.plugin(focus);

// Page-level Alpine components register themselves in ./components/*.js
import './core';
import.meta.glob('./components/*.js', { eager: true });

Alpine.start();
