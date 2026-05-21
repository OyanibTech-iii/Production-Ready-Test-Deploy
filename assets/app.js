
import './styles/app.css';
import './bootstrap.js';
import 'leaflet/dist/leaflet.css';
import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';
import 'leaflet-routing-machine';
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css';

Alpine.plugin(intersect);
window.Alpine = Alpine;
Alpine.start();
