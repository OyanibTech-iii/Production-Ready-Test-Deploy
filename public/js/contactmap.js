(function () {
    'use strict';

    let map;
    let markersLayer;
    delete L.Icon.Default.prototype._getIconUrl;

    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
    });
    function mapEl() {
        return document.getElementById('branch-locations-map');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function branchPoints() {
        const out = [];
        document.querySelectorAll('.js-branch-map-trigger').forEach(function (card) {
            const lat = parseFloat(card.dataset.lat);
            const lng = parseFloat(card.dataset.lng);
            if (!isNaN(lat) && !isNaN(lng)) {
                out.push({ lat: lat, lng: lng, name: card.dataset.name || '' });
            }
        });
        return out;
    }

    function ensureMap() {
        const el = mapEl();
        if (!el || typeof L === 'undefined') {
            return false;
        }
        if (map) {
            return true;
        }

        const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        });
        const satellite = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            { attribution: 'Tiles &copy; Esri' }
        );

        map = L.map('branch-locations-map', {
            center: [9.5, 122.5],
            zoom: 8,
            layers: [osm]
        });

        L.control.layers({ 'Street Map': osm, 'Satellite View': satellite }).addTo(map);

        markersLayer = L.layerGroup().addTo(map);

        branchPoints().forEach(function (loc) {
            L.marker([loc.lat, loc.lng])
                .bindPopup('<b>' + escapeHtml(loc.name) + '</b>')
                .addTo(markersLayer);
        });

        const pts = branchPoints();
        if (pts.length === 1) {
            map.setView([pts[0].lat, pts[0].lng], 12);
        } else if (pts.length > 1) {
            map.fitBounds(L.latLngBounds(pts.map(function (p) { return [p.lat, p.lng]; })), { padding: [40, 40] });
        }

        setTimeout(function () { map.invalidateSize(); }, 200);
        return true;
    }

    function showOnMap(lat, lng) {
        if (!ensureMap()) {
            return;
        }
        map.setView([lat, lng], 16);
        markersLayer.eachLayer(function (layer) {
            if (layer instanceof L.Marker) {
                const p = layer.getLatLng();
                if (Math.abs(p.lat - lat) < 1e-7 && Math.abs(p.lng - lng) < 1e-7) {
                    layer.openPopup();
                }
            }
        });
        const el = mapEl();
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function onContactsSectionVisible() {
        const contacts = document.getElementById('contacts-content');
        if (!contacts || !contacts.classList.contains('active')) {
            return;
        }
        if (branchPoints().length === 0) {
            return;
        }
        ensureMap();
        setTimeout(function () {
            if (map) {
                map.invalidateSize();
            }
        }, 400);
    }

    document.addEventListener('click', function (e) {
        const card = e.target.closest('.js-branch-map-trigger');
        if (!card) {
            return;
        }
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);
        if (isNaN(lat) || isNaN(lng)) {
            return;
        }
        showOnMap(lat, lng);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') {
            return;
        }
        const card = e.target.closest('.js-branch-map-trigger');
        if (!card) {
            return;
        }
        e.preventDefault();
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);
        if (isNaN(lat) || isNaN(lng)) {
            return;
        }
        showOnMap(lat, lng);
    });

    window.addEventListener('growfico:section-shown', function (ev) {
        if (ev.detail && ev.detail.id === 'contacts-content') {
            onContactsSectionVisible();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onContactsSectionVisible);
    } else {
        onContactsSectionVisible();
    }
})();