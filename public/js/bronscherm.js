/**
 * Brontoning: een foto, kaart, film of document over het hele scherm,
 * met bijschrift en bronvermelding (TO §7, §8).
 */
import { bestandUrl } from './api.js';

const TYPE_LABEL = { foto: 'Foto', kaart: 'Kaart', film: 'Film', document: 'Document' };

export class Bronscherm {
  constructor(el, { onOpen, onSluit } = {}) {
    this.el = el;
    this.media = el.querySelector('#bron-media');
    this.vorige = el.querySelector('#bron-vorige');
    this.volgende = el.querySelector('#bron-volgende');
    this.onOpen = onOpen;
    this.onSluit = onSluit;
    this.bronnen = [];
    this.index = 0;

    el.querySelector('[data-sluit]').addEventListener('click', () => this.sluit());
    this.vorige.addEventListener('click', () => this.#ga(-1));
    this.volgende.addEventListener('click', () => this.#ga(1));
    // Tikken naast de bron sluit ook
    el.addEventListener('click', (e) => { if (e.target === el) this.sluit(); });
    document.addEventListener('keydown', (e) => {
      if (this.el.hidden) return;
      if (e.key === 'Escape') this.sluit();
      if (e.key === 'ArrowLeft') this.#ga(-1);
      if (e.key === 'ArrowRight') this.#ga(1);
    });
    this.#veeg();
  }

  get open() { return !this.el.hidden; }

  toon(bronnen, index = 0) {
    this.bronnen = bronnen;
    this.index = index;
    this.el.hidden = false;
    this.#render();
    this.onOpen?.();
    this.el.querySelector('[data-sluit]').focus({ preventScroll: true });
  }

  sluit() {
    if (this.el.hidden) return;
    this.media.replaceChildren();   // stopt ook een eventuele film
    this.el.hidden = true;
    this.onSluit?.();
  }

  #ga(stap) {
    const nieuw = this.index + stap;
    if (nieuw < 0 || nieuw >= this.bronnen.length) return;
    this.index = nieuw;
    this.#render();
  }

  /** Vegen naar links of rechts bladert door de bronnen. */
  #veeg() {
    let startX = null;
    this.media.addEventListener('pointerdown', (e) => { startX = e.clientX; });
    this.media.addEventListener('pointerup', (e) => {
      if (startX === null) return;
      const dx = e.clientX - startX;
      startX = null;
      if (Math.abs(dx) > 80) this.#ga(dx < 0 ? 1 : -1);
    });
  }

  #render() {
    const b = this.bronnen[this.index];
    const url = bestandUrl(b.bestand);
    const ext = (b.bestand.split('.').pop() || '').toLowerCase();
    let el;

    if (['mp4', 'webm'].includes(ext)) {
      el = document.createElement('video');
      el.src = url;
      el.controls = true;
      el.autoplay = true;
      el.playsInline = true;
    } else if (ext === 'pdf') {
      el = document.createElement('iframe');
      el.src = `${url}#toolbar=0&navpanes=0`;
      el.title = b.titel;
    } else {
      el = document.createElement('img');
      el.src = url;
      el.alt = b.bijschrift || b.titel;
      el.decoding = 'async';
    }
    this.media.replaceChildren(el);

    const label = TYPE_LABEL[b.type] ?? 'Bron';
    this.el.querySelector('#bron-type').textContent = b.jaar ? `${label} · ${b.jaar}` : label;
    this.el.querySelector('#bron-titel').textContent = b.titel;
    this.el.querySelector('#bron-bijschrift').textContent = b.bijschrift ?? '';
    this.el.querySelector('#bron-vermelding').textContent = b.bronvermelding ? `Bron: ${b.bronvermelding}` : '';
    this.el.querySelector('#bron-teller').textContent = this.bronnen.length > 1 ? `${this.index + 1} van ${this.bronnen.length}` : '';

    const meer = this.bronnen.length > 1;
    this.vorige.hidden = !meer;
    this.volgende.hidden = !meer;
    this.vorige.disabled = this.index === 0;
    this.volgende.disabled = this.index === this.bronnen.length - 1;
  }
}
