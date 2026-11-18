/**
 * Infopaneel (TO §7, §8): verschijnt als de boot een punt passeert, met titel,
 * korte tekst voor kinderen, een 'wist je dat'-regel, uitleg en bronnen.
 *
 * Regels:
 * - Automatisch geopend paneel sluit vanzelf als de boot het punt voorbij is.
 * - Heeft de bezoeker iets aangeraakt (meer weten, een bron, of het punt zelf
 *   aangetikt), dan blijft het paneel staan tot de sluitknop of het volgende punt.
 * - Weggeklikt? Dan komt hetzelfde punt niet meteen terug.
 */
import { api, bestandUrl } from './api.js';

const TYPE_ICOON = { foto: 'i-foto', kaart: 'i-kaart', film: 'i-film', document: 'i-document' };
const TYPE_LABEL = { foto: 'Foto', kaart: 'Kaart', film: 'Film', document: 'Document' };
const AFBEELDING = /\.(jpe?g|png|webp|gif|svg)$/i;

export class Infopaneel {
  constructor(el, { nummerVan, onBron }) {
    this.el = el;
    this.nummerVan = nummerVan;
    this.onBron = onBron;
    this.huidig = null;
    this.vastgezet = false;
    this.weggeklikt = null;
    this._laatsteActief = null;
    this._cache = new Map();

    this.$ = (id) => el.querySelector(id);
    this.$('#infopaneel-sluiten').addEventListener('click', () => {
      this.weggeklikt = this.huidig?.id ?? null;
      this.sluit();
    });
    this.$('#info-meer-knop').addEventListener('click', () => {
      const open = this.$('#info-meer').hidden;
      this.$('#info-meer').hidden = !open;
      this.$('#info-meer-knop').textContent = open ? 'Minder tekst' : 'Meer weten';
      this.$('#info-meer-knop').setAttribute('aria-expanded', String(open));
      this.vastgezet = true;
    });
    // Scrollen of tikken in het paneel = de bezoeker leest mee
    el.addEventListener('pointerdown', () => { this.vastgezet = true; });
  }

  /** Aanroepen bij elke tijdsupdate met het actieve punt (of null). */
  volg(actief) {
    const actiefId = actief?.id ?? null;
    if (actiefId !== this._laatsteActief) {
      this._laatsteActief = actiefId;
      if (actiefId !== this.weggeklikt) this.weggeklikt = null;
    }

    if (actief && actief.id !== this.huidig?.id && actief.id !== this.weggeklikt) {
      this.toon(actief, { doorGebruiker: false });
    } else if (!actief && this.huidig && !this.vastgezet) {
      this.sluit();
    }
  }

  toon(punt, { doorGebruiker }) {
    this.huidig = punt;
    this.vastgezet = doorGebruiker;
    this.weggeklikt = null;

    this.$('#info-nummer').textContent = String(this.nummerVan(punt));
    this.$('#info-titel').textContent = punt.titel;
    this.$('#info-kort').textContent = punt.korte_tekst;

    const wist = this.$('#info-wist');
    wist.hidden = !punt.wist_je_dat;
    wist.querySelector('span').textContent = punt.wist_je_dat ?? '';

    this.$('#info-uitleg').textContent = punt.uitleg ?? '';
    this.$('#info-meer').hidden = true;
    this.$('#info-meer-knop').hidden = !punt.uitleg;
    this.$('#info-meer-knop').textContent = 'Meer weten';
    this.$('#info-meer-knop').setAttribute('aria-expanded', 'false');
    this.$('.infopaneel__inhoud').scrollTop = 0;

    // Opnieuw afspelen van de animatie
    this.el.hidden = true;
    void this.el.offsetWidth;
    this.el.hidden = false;

    this.#laadBronnen(punt);
  }

  sluit() {
    this.el.hidden = true;
    this.huidig = null;
    this.vastgezet = false;
  }

  async #laadBronnen(punt) {
    const vak = this.$('#info-bronnen');
    vak.replaceChildren();
    const token = (this._laadToken = Symbol('laden'));
    if (!punt.aantal_bronnen) return;

    if (!this._cache.has(punt.id)) {
      this._cache.set(punt.id, api.punt(punt.id).then((p) => p.bronnen).catch(() => {
        this._cache.delete(punt.id);
        return [];
      }));
    }
    const bronnen = await this._cache.get(punt.id);
    if (token !== this._laadToken) return;   // intussen opnieuw geladen of een ander punt

    bronnen.forEach((b, i) => vak.append(this.#tegel(b, () => {
      this.vastgezet = true;
      this.onBron(bronnen, i);
    })));
  }

  #tegel(bron, opTik) {
    const knop = document.createElement('button');
    knop.type = 'button';
    knop.className = 'bron-tegel';
    knop.setAttribute('aria-label', `${TYPE_LABEL[bron.type] ?? 'Bron'}: ${bron.titel}`);

    if (AFBEELDING.test(bron.bestand)) {
      const img = document.createElement('img');
      img.src = bestandUrl(bron.bestand);
      img.alt = '';
      img.loading = 'lazy';
      knop.append(img);
    } else {
      const icoon = document.createElement('span');
      icoon.className = 'bron-tegel__icoon';
      icoon.append(svgIcoon(TYPE_ICOON[bron.type] ?? 'i-document'));
      knop.append(icoon);
    }

    const label = document.createElement('span');
    label.className = 'bron-tegel__label';
    label.append(svgIcoon(TYPE_ICOON[bron.type] ?? 'i-document'), document.createTextNode(bron.jaar ? `${TYPE_LABEL[bron.type]} ${bron.jaar}` : TYPE_LABEL[bron.type] ?? 'Bron'));
    knop.append(label);

    knop.addEventListener('click', opTik);
    return knop;
  }
}

function svgIcoon(id) {
  const ns = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(ns, 'svg');
  svg.setAttribute('aria-hidden', 'true');
  const use = document.createElementNS(ns, 'use');
  use.setAttribute('href', `#${id}`);
  svg.append(use);
  return svg;
}
