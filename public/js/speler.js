/**
 * Videospeler met eigen klok.
 *
 * - Vooruit varen: de HTML5-video speelt normaal af.
 * - Achteruit varen: browsers kunnen video niet achteruit afspelen, dus de speler
 *   pauzeert de video en zet de tijd steeds een stukje terug.
 * - Demomodus: zolang er geen videobestand is (of het laden mislukt) loopt er een
 *   virtuele klok, zodat kaart, tijdlijn en infopunten getest kunnen worden.
 *
 * Gebeurtenissen: 'tijd' (detail: seconden), 'status', 'einde'.
 */
export class Speler extends EventTarget {
  constructor(video, { duur = 600, achteruitSnelheid = 1 } = {}) {
    super();
    this.video = video;
    this.duur = duur;
    this.achteruitSnelheid = achteruitSnelheid;
    this.demo = false;
    this.speelt = false;
    this.richting = 1;           // 1 = vooruit, -1 = achteruit
    this._t = 0;
    this._vorige = 0;
    this._lus = this._lus.bind(this);

    video.addEventListener('ended', () => {
      if (this.richting === 1) this.#einde(this.duur);
    });
  }

  get tijd() { return this._t; }

  /** Laad de video. Lukt dat niet, dan schakelt de speler naar demomodus. */
  laad(src) {
    return new Promise((klaar) => {
      if (!src) { this.demo = true; return klaar({ demo: true }); }

      let afgehandeld = false;
      const afronden = (demo) => {
        if (afgehandeld) return;
        afgehandeld = true;
        clearTimeout(timer);
        this.demo = demo;
        if (!demo && Number.isFinite(this.video.duration) && this.video.duration > 0) {
          this.duur = this.video.duration;
        }
        klaar({ demo });
      };
      const timer = setTimeout(() => afronden(true), 10000);

      this.video.addEventListener('loadedmetadata', () => afronden(false), { once: true });
      this.video.addEventListener('error', () => afronden(true), { once: true });
      this.video.src = src;
      this.video.load();
    });
  }

  speel() {
    if (this.richting === 1 && this._t >= this.duur - 0.25) this.zoek(0);
    if (this.richting === -1 && this._t <= 0.25) this.zoek(this.duur);
    const liepAl = this.speelt;
    this.speelt = true;
    this.#synchroniseerVideo();
    if (!liepAl) {          // voorkom dat er twee animatielussen tegelijk lopen
      this._vorige = performance.now();
      requestAnimationFrame(this._lus);
    }
    this.#meld('status');
  }

  pauzeer() {
    this.speelt = false;
    if (!this.demo) this.video.pause();
    this.#meld('status');
  }

  wissel() { this.speelt ? this.pauzeer() : this.speel(); }

  zoek(tijd) {
    this._t = Math.min(this.duur, Math.max(0, tijd));
    if (!this.demo) {
      try { this.video.currentTime = this._t; } catch { /* nog niet geladen */ }
    }
    this.#meld('tijd', this._t);
  }

  verspring(seconden) { this.zoek(this._t + seconden); }

  zetRichting(richting) {
    if (richting === this.richting) return;
    this.richting = richting;
    if (this.speelt) this.#synchroniseerVideo();
    this.#meld('status');
  }

  #synchroniseerVideo() {
    if (this.demo) return;
    if (this.speelt && this.richting === 1) {
      this.video.play().catch(() => { /* autoplay geblokkeerd: blijft gepauzeerd */ });
    } else {
      this.video.pause();
    }
  }

  _lus(nu) {
    if (!this.speelt) return;
    const dt = Math.min(0.25, Math.max(0, (nu - this._vorige) / 1000));
    this._vorige = nu;

    if (this.demo) {
      this._t += dt * this.richting * (this.richting === -1 ? this.achteruitSnelheid : 1);
    } else if (this.richting === 1) {
      this._t = this.video.currentTime;
    } else {
      this._t -= dt * this.achteruitSnelheid;
      // Pas een nieuwe sprong maken als de vorige klaar is, anders loopt de video vast.
      if (!this.video.seeking) {
        try { this.video.currentTime = Math.max(0, this._t); } catch { /* negeren */ }
      }
    }

    if (this._t >= this.duur) return this.#einde(this.duur);
    if (this._t <= 0 && this.richting === -1) return this.#einde(0);

    this.#meld('tijd', this._t);
    requestAnimationFrame(this._lus);
  }

  #einde(tijd) {
    this._t = tijd;
    this.#meld('tijd', tijd);
    this.pauzeer();
    this.#meld('einde');
  }

  #meld(type, detail) {
    this.dispatchEvent(new CustomEvent(type, { detail }));
  }
}

export function tijdLabel(seconden) {
  const s = Math.max(0, Math.round(seconden));
  return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
}
