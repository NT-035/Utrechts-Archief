/**
 * Interactieve kaart met Leaflet + OpenStreetMap (TO §5).
 *
 * - De route van de boot als gracht-lijn, met daarover de al gevaren route.
 * - Genummerde, aantikbare infopunten.
 * - Een bootje dat meebeweegt en meedraait met de vaarrichting.
 * - De kaart volgt de boot; na zelf schuiven of zoomen stopt volgen even.
 * - Zonder internet (geen kaarttegels) blijft een schematische routekaart over (TO §2, TC-14).
 *
 * De kaartlaag zit achter deze ene klasse, zodat die later vervangen kan worden
 * door bijvoorbeeld Google Maps 3D.
 */
const L = window.L;

const VOLG_HERVAT_MS = 20000;

/** Pixelgrootte die meeschaalt met de lettergrootte (Full HD → 4K). */
const rem = (n) => Math.round(n * parseFloat(getComputedStyle(document.documentElement).fontSize));

export class Kaart {
  constructor(el, { vak, volgKnop, route, punten, onPuntTik, bedekteHoogte }) {
    this.el = el;
    this.bedekteHoogte = bedekteHoogte;   // hoeveel px onderaan de kaart door het infopaneel wordt bedekt
    this.vak = vak;
    this.volgKnop = volgKnop;
    this.route = route;
    this.punten = punten;
    this.onPuntTik = onPuntTik;
    this.volgen = true;
    this._volgTimer = null;
    this._programmatisch = false;
    this._laatsteVolgCheck = 0;
    this._status = new Map();

    this.map = L.map(el, {
      zoomControl: false,
      zoomSnap: 0.25,
      minZoom: 14,
      maxZoom: 19,
      bounceAtZoomLimits: false,
      inertia: true,
      tapTolerance: 25,
    });
    L.control.zoom({ position: 'bottomright', zoomInTitle: 'Inzoomen', zoomOutTitle: 'Uitzoomen' }).addTo(this.map);
    this.map.attributionControl.setPrefix(false);

    this.#tegels();
    this.#tekenRoute();
    this.#tekenPunten();
    this.#tekenBoot();
    this.#luisterNaarGebruiker();
    this.overzicht();

    new ResizeObserver(() => this.map.invalidateSize({ pan: false })).observe(el);
  }

  /* ---------- Opbouw ---------- */

  #tegels() {
    let geladen = 0, fouten = 0;
    const laag = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      crossOrigin: true,
    });
    laag.on('tileload', () => { geladen++; this.vak.classList.remove('is-offline'); });
    laag.on('tileerror', () => {
      fouten++;
      if (geladen === 0 && fouten >= 2) this.vak.classList.add('is-offline');
    });
    laag.addTo(this.map);

    if (!navigator.onLine) this.vak.classList.add('is-offline');
    window.addEventListener('offline', () => this.vak.classList.add('is-offline'));
    window.addEventListener('online', () => { this.vak.classList.remove('is-offline'); laag.redraw(); });
  }

  #tekenRoute() {
    const lijn = this.route.latlngs;
    // Kade + water: twee lijnen over elkaar geven het idee van een gracht.
    L.polyline(lijn, { color: '#a8240f', weight: 30, opacity: 0.25, lineCap: 'round', lineJoin: 'round', interactive: false }).addTo(this.map);
    L.polyline(lijn, { color: '#a9d6e2', weight: 22, opacity: 0.95, lineCap: 'round', lineJoin: 'round', interactive: false }).addTo(this.map);
    this.gevaren = L.polyline([], { color: '#247065', weight: 8, opacity: 0.9, lineCap: 'round', lineJoin: 'round', dashArray: '1 14', interactive: false }).addTo(this.map);
  }

  #tekenPunten() {
    this.markers = new Map();
    this.punten.forEach((p, i) => {
      const nummer = i + 1;
      const icoon = L.divIcon({
        className: 'kaart-punt',
        html: String(nummer),
        iconSize: [rem(2), rem(2)],
      });
      const marker = L.marker([p.lat, p.lng], {
        icon: icoon,
        title: `${nummer}. ${p.titel}`,
        alt: `${nummer}. ${p.titel}`,
        keyboard: true,
        riseOnHover: false,
        zIndexOffset: 100,
      }).addTo(this.map);
      marker.on('click', () => this.onPuntTik?.(p));
      this.markers.set(p.id, marker);
    });
  }

  #tekenBoot() {
    const icoon = L.divIcon({
      className: 'kaart-boot',
      html: '<span class="kaart-boot__golf"></span><span class="kaart-boot__draai"><svg viewBox="0 0 40 40" aria-hidden="true">'
        + '<path d="M20 3c5 5 8 11 8 18v12a3 3 0 0 1-3 3H15a3 3 0 0 1-3-3V21c0-7 3-13 8-18Z" fill="#d72f19" stroke="#fff" stroke-width="2.4"/>'
        + '<rect x="16" y="17" width="8" height="10" rx="1.5" fill="#f5eee0"/>'
        + '<circle cx="20" cy="12" r="2" fill="#f5eee0"/></svg></span>',
      iconSize: [rem(2.4), rem(2.4)],
    });
    this.boot = L.marker(this.route.punten[0] ?? [52.091, 5.12], {
      icon: icoon, interactive: false, keyboard: false, zIndexOffset: 2000,
    }).addTo(this.map);
  }

  #luisterNaarGebruiker() {
    const doorGebruiker = () => { if (!this._programmatisch) this.pauzeerVolgen(); };
    this.map.on('dragstart', doorGebruiker);
    this.map.on('zoomstart', doorGebruiker);
    this.map.on('moveend zoomend', () => { this._programmatisch = false; });
    this.volgKnop.addEventListener('click', () => this.hervatVolgen(true));
  }

  /* ---------- Bijwerken ---------- */

  /** Zet de boot op een positie; richting -1 draait de boot om. */
  zetBoot(pos, richting = 1) {
    if (!pos) return;
    const ll = [pos.lat, pos.lng];
    this.boot.setLatLng(ll);
    const draai = this.boot.getElement()?.querySelector('.kaart-boot__draai');
    if (draai) {
      const hoek = richting === -1 ? pos.hoek + 180 : pos.hoek;
      draai.style.transform = `rotate(${Math.round(hoek)}deg)`;
    }
    this.#volg(ll);
  }

  zetGevaren(latlngs) { this.gevaren.setLatLngs(latlngs); }

  /** Markeer gepasseerde en actieve punten (alleen wijzigingen aanraken). */
  zetStatus(tijd, actiefId) {
    for (const p of this.punten) {
      const status = p.id === actiefId ? 'actief' : (p.tijdstip < tijd ? 'gepasseerd' : '');
      if (this._status.get(p.id) === status) continue;
      this._status.set(p.id, status);
      const el = this.markers.get(p.id)?.getElement();
      if (!el) continue;
      el.classList.toggle('is-actief', status === 'actief');
      el.classList.toggle('is-gepasseerd', status === 'gepasseerd');
      this.markers.get(p.id).setZIndexOffset(status === 'actief' ? 1500 : 100);
    }
  }

  #volg(ll) {
    if (!this.volgen) return;
    const nu = performance.now();
    if (nu - this._laatsteVolgCheck < 250) return;
    this._laatsteVolgCheck = nu;
    // Alleen schuiven als de boot uit het midden van het zichtbare deel raakt
    // (het deel van de kaart dat niet door het infopaneel wordt bedekt).
    const maat = this.map.getSize();
    const onder = Math.min(maat.y * 0.6, this.bedekteHoogte?.() ?? 0);
    const zichtbaar = maat.y - onder;
    const p = this.map.latLngToContainerPoint(ll);
    const mx = maat.x * 0.25, my = zichtbaar * 0.25;
    if (p.x < mx || p.x > maat.x - mx || p.y < my || p.y > zichtbaar - my) {
      this.#centreer(ll, onder, { animate: true, duration: 0.8, easeLinearity: 0.4 });
    }
  }

  /** Zet een punt in het midden van het deel van de kaart boven het infopaneel. */
  #centreer(ll, onder, opties) {
    this._programmatisch = true;
    const p = this.map.latLngToContainerPoint(ll).add([0, onder / 2]);
    this.map.panTo(this.map.containerPointToLatLng(p), opties);
  }

  pauzeerVolgen() {
    this.volgen = false;
    this.volgKnop.hidden = false;
    clearTimeout(this._volgTimer);
    this._volgTimer = setTimeout(() => this.hervatVolgen(false), VOLG_HERVAT_MS);
  }

  hervatVolgen(direct) {
    clearTimeout(this._volgTimer);
    this.volgen = true;
    this.volgKnop.hidden = true;
    if (direct) {
      this._programmatisch = true;
      this.map.setView(this.boot.getLatLng(), Math.max(this.map.getZoom(), 17.5), { animate: true });
    }
  }

  /** Startweergave: ingezoomd op het begin van de route. */
  overzicht() {
    this._programmatisch = true;
    const start = this.route.punten[0];
    if (start) this.map.setView([start.lat, start.lng], 18.25, { animate: false });
    else this.map.setView([52.091, 5.12], 15, { animate: false });
    this.hervatVolgen(false);
  }

  /** Breng een punt in beeld (bij aantikken). */
  toonPunt(p) {
    const onder = Math.min(this.map.getSize().y * 0.6, this.bedekteHoogte?.() ?? 0);
    this.#centreer(L.latLng(p.lat, p.lng), onder, { animate: true, duration: 0.6 });
  }
}
