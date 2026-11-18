/**
 * Tijdlijn onder de video met de infopunten erop (TO §8).
 * Te bedienen door te tikken of de boot te slepen (pointer events: touch, pen en muis).
 */
import { tijdLabel } from './speler.js';

export class Tijdlijn {
  constructor(el, { punten, duur, onZoek }) {
    this.el = el;
    this.spoor = el.querySelector('.tijdlijn__spoor');
    this.gevaren = el.querySelector('#tijdlijn-gevaren');
    this.knop = el.querySelector('#tijdlijn-knop');
    this.puntenEl = el.querySelector('#tijdlijn-punten');
    this.onZoek = onZoek;
    this.punten = punten;
    this.duur = duur;
    this.tijd = 0;

    this.#tekenPunten();
    this.#luister();
  }

  zetDuur(duur) { this.duur = duur; this.#tekenPunten(); }

  #tekenPunten() {
    this.puntenEl.replaceChildren();
    this.puntEls = new Map();
    for (const p of this.punten) {
      const stip = document.createElement('span');
      stip.className = 'tijdlijn__punt';
      stip.style.left = `${Math.min(100, (p.tijdstip / this.duur) * 100)}%`;
      this.puntenEl.append(stip);
      this.puntEls.set(p.id, stip);
    }
    this.el.setAttribute('aria-valuemax', String(Math.round(this.duur)));
  }

  zet(tijd, actiefId) {
    this.tijd = tijd;
    const pct = this.duur > 0 ? (tijd / this.duur) * 100 : 0;
    this.gevaren.style.width = `${pct}%`;
    this.knop.style.left = `${pct}%`;
    this.el.setAttribute('aria-valuenow', String(Math.round(tijd)));
    this.el.setAttribute('aria-valuetext', tijdLabel(tijd));

    for (const p of this.punten) {
      const el = this.puntEls.get(p.id);
      el.classList.toggle('is-gepasseerd', p.tijdstip < tijd);
      el.classList.toggle('is-actief', p.id === actiefId);
    }
  }

  #tijdVanPointer(e) {
    const r = this.spoor.getBoundingClientRect();
    const f = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width));
    return f * this.duur;
  }

  #luister() {
    let slepen = false;
    this.el.addEventListener('pointerdown', (e) => {
      slepen = true;
      this.el.setPointerCapture(e.pointerId);
      this.el.classList.add('is-slepen');
      this.onZoek(this.#tijdVanPointer(e));
    });
    this.el.addEventListener('pointermove', (e) => {
      if (slepen) this.onZoek(this.#tijdVanPointer(e));
    });
    const stop = () => { slepen = false; this.el.classList.remove('is-slepen'); };
    this.el.addEventListener('pointerup', stop);
    this.el.addEventListener('pointercancel', stop);

    // Toetsenbord (toegankelijkheid)
    this.el.addEventListener('keydown', (e) => {
      const stappen = { ArrowLeft: this.tijd - 5, ArrowRight: this.tijd + 5, Home: 0, End: this.duur };
      if (e.key in stappen) {
        this.onZoek(stappen[e.key]);
        e.preventDefault();
        e.stopPropagation();
      }
    });
  }
}
