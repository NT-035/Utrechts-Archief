/* CMS – routebeheer: routepunten toevoegen, verslepen, bewerken en verwijderen. */
(function () {
  'use strict';

  var el = document.getElementById('route-kaart');
  if (!el || !window.L) return;

  var route = JSON.parse(el.dataset.route || '[]');
  var punten = JSON.parse(el.dataset.punten || '[]');
  var tbody = document.getElementById('route-rijen');
  var aantal = document.getElementById('route-aantal');
  var duurVeld = document.getElementById('route-duur');
  var gewijzigd = false;

  var map = L.map(el, { zoomSnap: 0.5 });
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);

  punten.forEach(function (p) {
    L.circleMarker([p.lat, p.lng], { radius: 6, color: '#8e3f1f', weight: 2, fillColor: '#f0c04a', fillOpacity: 0.85 })
      .bindTooltip(p.titel + ' (' + p.t + ' s)').addTo(map);
  });

  var lijn = L.polyline([], { color: '#2c6e85', weight: 5 }).addTo(map);
  var markers = [];

  function icoon(nr) {
    return L.divIcon({ className: 'route-stip', html: String(nr), iconSize: [26, 26] });
  }

  function sorteer() { route.sort(function (a, b) { return a.t - b.t; }); }

  function teken() {
    markers.forEach(function (m) { map.removeLayer(m); });
    markers = [];
    lijn.setLatLngs(route.map(function (r) { return [r.lat, r.lng]; }));

    route.forEach(function (r, i) {
      var m = L.marker([r.lat, r.lng], { draggable: true, icon: icoon(i + 1), title: 'Routepunt ' + (i + 1) }).addTo(map);
      m.on('drag', function () {
        var ll = m.getLatLng();
        r.lat = +ll.lat.toFixed(6); r.lng = +ll.lng.toFixed(6);
        lijn.setLatLngs(route.map(function (x) { return [x.lat, x.lng]; }));
      });
      m.on('dragend', function () { gewijzigd = true; tabel(); });
      markers.push(m);
    });
    tabel();
  }

  function invoer(waarde, stap, opWijzig) {
    var input = document.createElement('input');
    input.type = 'number';
    input.step = stap;
    input.value = waarde;
    input.addEventListener('change', function () { gewijzigd = true; opWijzig(Number(input.value)); });
    return input;
  }

  function tabel() {
    tbody.replaceChildren();
    aantal.textContent = route.length;
    route.forEach(function (r, i) {
      var tr = document.createElement('tr');
      var td = function (inhoud) { var c = document.createElement('td'); c.append(inhoud); tr.append(c); };
      td(String(i + 1));
      td(invoer(r.t, '1', function (v) { r.t = Math.max(0, Math.round(v)); sorteer(); teken(); }));
      td(invoer(r.lat, '0.000001', function (v) { r.lat = v; teken(); }));
      td(invoer(r.lng, '0.000001', function (v) { r.lng = v; teken(); }));
      var weg = document.createElement('button');
      weg.type = 'button';
      weg.className = 'knop knop--klein knop--gevaar';
      weg.textContent = 'Weg';
      weg.addEventListener('click', function () { route.splice(i, 1); gewijzigd = true; teken(); });
      td(weg);
      tr.addEventListener('mouseenter', function () { markers[i] && markers[i].getElement() && markers[i].getElement().classList.add('is-licht'); });
      tr.addEventListener('mouseleave', function () { markers[i] && markers[i].getElement() && markers[i].getElement().classList.remove('is-licht'); });
      tbody.append(tr);
    });
  }

  // Tik op de kaart: nieuw punt aan het einde, of tussen de twee dichtstbijzijnde routepunten
  map.on('click', function (e) {
    var nieuw = { lat: +e.latlng.lat.toFixed(6), lng: +e.latlng.lng.toFixed(6), t: 0 };
    if (route.length < 2) {
      nieuw.t = route.length ? route[route.length - 1].t + 30 : 0;
      route.push(nieuw);
    } else {
      // Zoek het segment waar het punt het dichtst bij ligt
      var beste = -1, besteAfstand = Infinity;
      for (var i = 0; i < route.length - 1; i++) {
        var d = afstandTotSegment(e.latlng, route[i], route[i + 1]);
        if (d < besteAfstand) { besteAfstand = d; beste = i; }
      }
      var eind = map.distance(e.latlng, [route[route.length - 1].lat, route[route.length - 1].lng]);
      if (eind < besteAfstand) {
        nieuw.t = route[route.length - 1].t + 30;
        route.push(nieuw);
      } else {
        nieuw.t = Math.round((route[beste].t + route[beste + 1].t) / 2);
        route.splice(beste + 1, 0, nieuw);
      }
    }
    gewijzigd = true;
    sorteer();
    teken();
  });

  function afstandTotSegment(p, a, b) {
    var A = map.latLngToLayerPoint([a.lat, a.lng]);
    var B = map.latLngToLayerPoint([b.lat, b.lng]);
    var P = map.latLngToLayerPoint(p);
    return L.LineUtil.pointToSegmentDistance(P, A, B);
  }

  // Tijden naar verhouding van de afstand verdelen
  document.getElementById('route-verdeel').addEventListener('click', function () {
    if (route.length < 2) return;
    var duur = Math.max(1, Number(duurVeld.value) || 600);
    var cum = [0];
    for (var i = 1; i < route.length; i++) {
      cum[i] = cum[i - 1] + map.distance([route[i - 1].lat, route[i - 1].lng], [route[i].lat, route[i].lng]);
    }
    var totaal = cum[cum.length - 1] || 1;
    route.forEach(function (r, i) { r.t = Math.round(cum[i] / totaal * duur); });
    gewijzigd = true;
    teken();
  });

  document.getElementById('route-formulier').addEventListener('submit', function () {
    gewijzigd = false;
    document.getElementById('route-json').value = JSON.stringify(route);
  });
  window.addEventListener('beforeunload', function (e) { if (gewijzigd) { e.preventDefault(); e.returnValue = ''; } });

  if (route.length) map.fitBounds(route.map(function (r) { return [r.lat, r.lng]; }), { padding: [30, 30] });
  else if (punten.length) map.fitBounds(punten.map(function (p) { return [p.lat, p.lng]; }), { padding: [30, 30] });
  else map.setView([52.0907, 5.1214], 15);

  teken();
})();
