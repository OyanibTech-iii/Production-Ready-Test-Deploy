 (function () {
        'use strict';
        
        let map;
        let markersLayer;
        let userMarker;
        let routingControl;
        let userLocation = null;

        // Get all locations from buttons
        const getLocations = () => {
            const locations = [];
            document.querySelectorAll('.show-on-map').forEach(btn => {
                locations.push({
                    lat: parseFloat(btn.dataset.lat),
                    lng: parseFloat(btn.dataset.lng),
                    name: btn.dataset.name || ''
                });
            });
            return locations;
        };

        // Find nearest location to a coordinate
        const findNearestLocation = (lat, lng) => {
            const locations = getLocations();
            let nearest = null;
            let minDistance = Infinity;

            locations.forEach(loc => {
                if (!isNaN(loc.lat) && !isNaN(loc.lng)) {
                    const distance = Math.sqrt(
                        Math.pow(loc.lat - lat, 2) + Math.pow(loc.lng - lng, 2)
                    );
                    if (distance < minDistance) {
                        minDistance = distance;
                        nearest = loc;
                    }
                }
            });
            return nearest;
        };

        // Initialize map
        const initMap = () => {
            const mapElement = document.getElementById('map');
            if (!mapElement) return;

            if (mapElement._leaflet_id) {
                mapElement._leaflet_id = null;
                mapElement.innerHTML = '';
            }

            const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '© OpenStreetMap'
            });
            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri'
            });

            map = L.map('map', {
                center: [0, 0],
                zoom: 2,
                layers: [osm]
            });

            const baseMaps = { 'Street Map': osm, 'Satellite View': satellite };
            L.control.layers(baseMaps).addTo(map);

            markersLayer = L.layerGroup().addTo(map);

            // Add all location markers
            const locationButtons = document.querySelectorAll('.show-on-map');
            const bounds = [];
            locationButtons.forEach(btn => {
                const lat = parseFloat(btn.dataset.lat);
                const lng = parseFloat(btn.dataset.lng);
                const name = btn.dataset.name || '';
                if (!isNaN(lat) && !isNaN(lng)) {
                    L.marker([lat, lng])
                        .bindPopup(`<b>${name}</b>`)
                        .addTo(markersLayer);
                    bounds.push([lat, lng]);
                }
            });

            // Fit bounds if there are locations
            if (bounds.length > 0) {
                if (bounds.length === 1) {
                    map.setView(bounds[0], 12);
                } else {
                    map.fitBounds(bounds, { padding: [40, 40] });
                }
            }

            setTimeout(function () { map.invalidateSize(); }, 200);
        };

        // Show map
        const showMap = () => {
            const mapContainer = document.getElementById('map-container');
            mapContainer.classList.remove('hidden');
            if (!map) {
                initMap();
            }
            setTimeout(() => {
                if (map) map.invalidateSize();
            }, 100);
            closeDropdown();
        };

        // Handle nearest branch functionality
        const handleNearestBranch = () => {
            closeDropdown();
            showMap();

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        // Add user location marker
                        if (userMarker) {
                            map.removeLayer(userMarker);
                        }
                        userMarker = L.marker([userLocation.lat, userLocation.lng], {
                            icon: L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize: [41, 41]
                            })
                        }).bindPopup('<b>Your Location</b>').addTo(map);

                        // Find nearest branch
                        const nearest = findNearestLocation(userLocation.lat, userLocation.lng);
                        if (nearest && !isNaN(nearest.lat) && !isNaN(nearest.lng)) {
                            // Route from user to nearest branch
                            if (routingControl) {
                                map.removeControl(routingControl);
                            }
                            
                            routingControl = L.Routing.control({
                                waypoints: [
                                    L.latLng(userLocation.lat, userLocation.lng),
                                    L.latLng(nearest.lat, nearest.lng)
                                ],
                                createMarker: function(i, waypoint, n) {
                                    if (i === 0) {
                                        return L.marker(waypoint.latLng, {
                                            icon: L.icon({
                                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                                iconSize: [25, 41],
                                                iconAnchor: [12, 41],
                                                popupAnchor: [1, -34],
                                                shadowSize: [41, 41]
                                            })
                                        });
                                    } else if (i === n - 1) {
                                        return L.marker(waypoint.latLng, {
                                            icon: L.icon({
                                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                                iconSize: [25, 41],
                                                iconAnchor: [12, 41],
                                                popupAnchor: [1, -34],
                                                shadowSize: [41, 41]
                                            })
                                        });
                                    }
                                },
                                routeWhileDragging: true,
                                collapsible: true
                            }).addTo(map);

                            map.fitBounds(routingControl.getBounds().pad(0.1));
                            showAlert(`Nearest branch: ${nearest.name}`, 'success');
                        }
                    },
                    (error) => {
                        console.error('Geolocation error:', error);
                        showAlert('Could not get your location. Please enable location services.', 'error');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                showAlert('Geolocation is not supported by your browser.', 'error');
            }
        };

        // Show/hide dropdown
        const toggleDropdown = () => {
            const dropdown = document.getElementById('map-menu-dropdown');
            dropdown.classList.toggle('hidden');
        };

        const closeDropdown = () => {
            const dropdown = document.getElementById('map-menu-dropdown');
            dropdown.classList.add('hidden');
        };

        // Show alert messages
        const showAlert = (message, type = 'info') => {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 p-4 rounded-2xl shadow-2xl z-[9999] flex items-center gap-3 border transition-all duration-300 transform translate-x-full fixed-alert ${
                type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 
                type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 
                'bg-blue-50 border-blue-200 text-blue-800'
            }`;
            
            const icon = type === 'success' ? 'checkmark-circle-outline' : 
                         type === 'error' ? 'alert-circle-outline' : 
                         'information-circle-outline';
            
            alertDiv.innerHTML = `
                <ion-icon name="${icon}" class="text-xl"></ion-icon>
                <p class="text-sm font-medium">${message}</p>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Animate in
            setTimeout(() => {
                alertDiv.classList.remove('translate-x-full');
            }, 10);

            // Remove after delay
            setTimeout(() => {
                alertDiv.classList.add('translate-x-full');
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 300);
            }, 4000);
        };

        // Hide map
        const hideMap = () => {
            const mapContainer = document.getElementById('map-container');
            mapContainer.classList.add('hidden');
            closeDropdown();
        };

        // Handle pin location
        let pinningMode = false;
        const handlePinLocation = () => {
            closeDropdown();
            showMap();
            pinningMode = true;
            showAlert('Click on the map to pin your location', 'info');
            
            // Add click listener to map
            if (map) {
                map.once('click', (e) => {
                    if (pinningMode) {
                        userLocation = {
                            lat: e.latlng.lat,
                            lng: e.latlng.lng
                        };

                        // Remove old user marker
                        if (userMarker) {
                            map.removeLayer(userMarker);
                        }

                        // Add pinned location marker
                        userMarker = L.marker([userLocation.lat, userLocation.lng], {
                            icon: L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize: [41, 41]
                            })
                        }).bindPopup('<b>Your Pinned Location</b>').addTo(map);

                        userMarker.openPopup();
                        pinningMode = false;
                        showAlert(`Location pinned at ${userLocation.lat.toFixed(4)}, ${userLocation.lng.toFixed(4)}`, 'success');

                        // Automatically calculate shortest route to nearest branch
                        const nearest = findNearestLocation(userLocation.lat, userLocation.lng);
                        if (nearest && !isNaN(nearest.lat) && !isNaN(nearest.lng)) {
                            // Remove existing routing control
                            if (routingControl) {
                                map.removeControl(routingControl);
                            }
                            
                            // Create route from pinned location to nearest branch
                            routingControl = L.Routing.control({
                                waypoints: [
                                    L.latLng(userLocation.lat, userLocation.lng),
                                    L.latLng(nearest.lat, nearest.lng)
                                ],
                                createMarker: function(i, waypoint, n) {
                                    if (i === 0) {
                                        return L.marker(waypoint.latLng, {
                                            icon: L.icon({
                                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                                iconSize: [25, 41],
                                                iconAnchor: [12, 41],
                                                popupAnchor: [1, -34],
                                                shadowSize: [41, 41]
                                            })
                                        });
                                    } else if (i === n - 1) {
                                        return L.marker(waypoint.latLng, {
                                            icon: L.icon({
                                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                                iconSize: [25, 41],
                                                iconAnchor: [12, 41],
                                                popupAnchor: [1, -34],
                                                shadowSize: [41, 41]
                                            })
                                        });
                                    }
                                },
                                routeWhileDragging: true,
                                collapsible: true
                            }).addTo(map);

                            // Fit map bounds to show entire route
                            setTimeout(() => {
                                map.fitBounds(routingControl.getBounds().pad(0.1));
                            }, 500);
                            
                            showAlert(`Shortest route calculated to: ${nearest.name}`, 'success');
                        }
                    }
                });
            }
        };

        // Event listeners
        const mapMenuToggle = document.getElementById('map-menu-toggle');
        const showMapBtn = document.getElementById('show-map-btn');
        const pinLocationBtn = document.getElementById('pin-location-btn');
        const nearestBranchBtn = document.getElementById('nearest-branch-btn');
        const hideMapBtn = document.getElementById('hide-map-btn');

        if (mapMenuToggle) {
            mapMenuToggle.addEventListener('click', toggleDropdown);
        }
        if (showMapBtn) {
            showMapBtn.addEventListener('click', showMap);
        }
        if (pinLocationBtn) {
            pinLocationBtn.addEventListener('click', handlePinLocation);
        }
        if (nearestBranchBtn) {
            nearestBranchBtn.addEventListener('click', handleNearestBranch);
        }
        if (hideMapBtn) {
            hideMapBtn.addEventListener('click', hideMap);
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#map-menu-toggle') && !e.target.closest('#map-menu-dropdown')) {
                closeDropdown();
            }
        });

        // Show map on location button click (existing functionality)
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.show-on-map');
            if (!btn) return;
            const lat = parseFloat(btn.dataset.lat);
            const lng = parseFloat(btn.dataset.lng);
            const name = btn.dataset.name || '';
            if (!isNaN(lat) && !isNaN(lng)) {
                showMap();
                if (map) {
                    map.setView([lat, lng], 16);
                    markersLayer.eachLayer(function (layer) {
                        if (layer instanceof L.Marker) {
                            const p = layer.getLatLng();
                            if (Math.abs(p.lat - lat) < 1e-7 && Math.abs(p.lng - lng) < 1e-7) {
                                layer.openPopup();
                            }
                        }
                    });
                    const mapEl = document.getElementById('map');
                    if (mapEl) {
                        mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }
        });

        document.addEventListener('turbo:load', () => {
            // Reset state on turbo load
            map = null;
            userMarker = null;
            routingControl = null;
            userLocation = null;
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                // Map will be initialized on demand
            });
        }
    })();