import './bootstrap';
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';

// Make Alpine available globally
window.Alpine = Alpine;

// Register Alpine plugins
Alpine.plugin(persist);

// Start Alpine
Alpine.start();
