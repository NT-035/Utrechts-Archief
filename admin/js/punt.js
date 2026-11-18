/* CMS – plek en tijdstip van een infopunt kiezen. */
(function () {
  'use strict';

  // Tekentellers
  document.querySelectorAll('[data-teller]').forEach(function (label) {
    var veld = document.getElementById(label.dataset.teller);
    if (!veld) return;
    var max = veld.maxLength;
    var bijwerken = function () { label.textContent = veld.value.length + ' / ' + max; };
    veld.addEventListener('input', bijwerken);
    bijwerken();
  });

  // Tijdstip-label en ‘gebruik huidige videotijd’
  var tijd = document.getElementById('tijdstip');
  var tijdLabel = document.getElementById('tijdstip-label');
  var fmt = function (s) { s = Math.max(0, Math.round(s)); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); };
  if (tijd && tijdLabel) {
    tijd.addEventListener('input', function () { tijdLabel.textContent = fmt(Number(tijd.value) || 0); });
  }
  var video = document.getElementById('voorbeeld-video');
  var neem = document.getElementById('neem-tijd');
  if (video && neem) {
    if (tijd.value) video.addEventListener('loadedmetadata', function () { video.currentTime = Number(tijd.value) || 0; }, { once: true });
    neem.addEventListener('click', function () {
      tijd.value = Math.floor(video.currentTime);
      tijd.dispatchEvent(new Event('input'));
    });
  }

  // Kaart
  var el = document.getElementById('punt-kaart');
  if (!el || !window.L) return;
  var lat = document.getElementById('lat');
  var lng = document.getElementById('lng');
  var route = JSON.parse(el.dataset.route || '[]');
  var anderen = JSON.parse(el.dataset.anderen || '[]');

  var map = L.map(el, { zoomSnap: 0.5 });
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '© OpenStreetMap'
  }).addTo(map);

  if (route.length) {
    L.polyline(route.map(function (r) { return [r.lat, r.lng]; }), { color: '#2c6e85', weight: 6, opacity: 0.6 }).addTo(map);
  }
  anderen.forEach(function (p) {
    L.circleMarker([p.lat, p.lng], { radius: 6, color: '#8e3f1f', weight: 2, fillColor: '#f0c04a', fillOpacity: 0.8 })
      .bindTooltip(p.titel).addTo(map);
  });

  var marker = null;
  function zet(ll, schuif) {
    if (!marker) {
      marker = L.marker(ll, { draggable: true, autoPan: true }).addTo(map);
      marker.on('dragend', function () { zet(marker.getLatLng(), false); });
    } else {
      marker.setLatLng(ll);
    }
    lat.value = ll.lat.toFixed(6);
    lng.value = ll.lng.toFixed(6);
    if (schuif) map.panTo(ll);
  }

  var startLat = parseFloat(String(lat.value).replace(',', '.'));
  var startLng = parseFloat(String(lng.value).replace(',', '.'));
  if (isFinite(startLat) && isFinite(startLng) && startLat !== 0) {
    map.setView([startLat, startLng], 17);
    zet(L.latLng(startLat, startLng), false);
  } else if (route.length) {
    map.fitBounds(route.map(function (r) { return [r.lat, r.lng]; }), { padding: [20, 20] });
  } else {
    map.setView([52.0907, 5.1214], 15);
  }

  map.on('click', function (e) { zet(e.latlng, false); });

  [lat, lng].forEach(function (veld) {
    veld.addEventListener('change', function () {
      var a = parseFloat(lat.value.replace(',', '.'));
      var b = parseFloat(lng.value.replace(',', '.'));
      if (isFinite(a) && isFinite(b)) zet(L.latLng(a, b), true);
    });
  });
})();
