/**
 * Route van de boot: zet een videotijd om in een positie op de kaart (TO §7).
 * Zoekt de twee dichtstbijzijnde routepunten en interpoleert lineair.
 */
export class Route {
  /** @param {{t:number, lat:number, lng:number}[]} punten */
  constructor(punten) {
    this.punten = [...punten].sort((a, b) => a.t - b.t);
  }

  get leeg() { return this.punten.length === 0; }
  get latlngs() { return this.punten.map((p) => [p.lat, p.lng]); }

  /** Index van het laatste routepunt met t <= tijd (binair zoeken). */
  #index(tijd) {
    const p = this.punten;
    let lo = 0, hi = p.length - 1;
    if (tijd <= p[0].t) return 0;
    if (tijd >= p[hi].t) return hi;
    while (lo < hi) {
      const mid = (lo + hi + 1) >> 1;
      if (p[mid].t <= tijd) lo = mid; else hi = mid - 1;
    }
    return lo;
  }

  /** Positie en vaarrichting (graden, 0 = noord) op een tijdstip. */
  positie(tijd) {
    const p = this.punten;
    if (p.length === 0) return null;
    if (p.length === 1) return { lat: p[0].lat, lng: p[0].lng, hoek: 0, index: 0, fractie: 0 };

    const i = Math.min(this.#index(tijd), p.length - 2);
    const a = p[i], b = p[i + 1];
    const span = b.t - a.t;
    const f = span > 0 ? Math.min(1, Math.max(0, (tijd - a.t) / span)) : 0;

    return {
      lat: a.lat + (b.lat - a.lat) * f,
      lng: a.lng + (b.lng - a.lng) * f,
      hoek: koers(a, b),
      index: i,
      fractie: f,
    };
  }

  /** De al gevaren route tot een tijdstip (voor de ‘gevaren’-lijn op de kaart). */
  gevarenTot(tijd) {
    const pos = this.positie(tijd);
    if (!pos) return [];
    const lijn = this.punten.slice(0, pos.index + 1).map((p) => [p.lat, p.lng]);
    lijn.push([pos.lat, pos.lng]);
    return lijn;
  }
}

/** Kompaskoers van a naar b in graden. */
function koers(a, b) {
  const rad = Math.PI / 180;
  const y = Math.sin((b.lng - a.lng) * rad) * Math.cos(b.lat * rad);
  const x = Math.cos(a.lat * rad) * Math.sin(b.lat * rad)
    - Math.sin(a.lat * rad) * Math.cos(b.lat * rad) * Math.cos((b.lng - a.lng) * rad);
  return (Math.atan2(y, x) / rad + 360) % 360;
}
