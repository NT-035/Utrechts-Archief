/**
 * Ontmoet de Oudegracht – hoofdmodule.
 * Koppelt de videospeler aan de kaart, de tijdlijn en het infopaneel (TO §7).
 *
 * Navigatie bezoeker: Startscherm → Vaartocht → Infopaneel → Bron (TO §8).
 */
import { api, bestandUrl } from './api.js';
import { Route } from './route.js';
import { Speler, tijdLabel } from './speler.js';
import { Kaart } from './kaart.js';
import { Tijdlijn } from './tijdlijn.js';
import { Infopaneel } from './infopaneel.js';
import { Bronscherm } from './bronscherm.js';
import { Kiosk } from './kiosk.js';

const $ = (id) => document.getElementById(id);

async function start() {
  // ---------- Data laden ----------
  let instellingen, punten, routeData;
  try {
    [instellingen, punten, routeData] = await Promise.all([api.instellingen(), api.punten(), api.route()]);
  } catch (fout) {
    console.error(fout);
    return toonFout('De informatie over de vaartocht kon niet worden geladen. Controleer de verbinding met de server.');
  }

  punten.sort((a, b) => a.tijdstip - b.tijdstip || a.volgorde - b.volgorde);
  const nummers = new Map(punten.map((p, i) => [p.id, i + 1]));
  const nummerVan = (p) => nummers.get(p.id);

  // Geen aparte route ingevoerd? Dan varen we van punt naar punt.
  if (!routeData.length) routeData = punten.map((p) => ({ t: p.tijdstip, lat: p.lat, lng: p.lng }));
  const route = new Route(routeData);
  const bereik = Number(instellingen.punt_bereik_seconden) || 12;
  if (instellingen.colofon_makers) $('colofon-makers').textContent = instellingen.colofon_makers;

  // ---------- Speler ----------
  const app = $('app');
  const video = $('video');
  const speler = new Speler(video, {
    duur: Number(instellingen.video_duur_seconden) || 600,
    achteruitSnelheid: Number(instellingen.achteruit_snelheid) || 1,
  });
  const { demo } = await speler.laad(bestandUrl(instellingen.video_bestand));
  $('film-demo').hidden = !demo;
  video.hidden = demo;

  // ---------- Onderdelen ----------
  const bronscherm = new Bronscherm($('bronscherm'), {
    onOpen: () => { bronscherm.speeldeAl = speler.speelt; speler.pauzeer(); },
    onSluit: () => { if (bronscherm.speeldeAl) speler.speel(); },
  });

  const paneel = new Infopaneel($('infopaneel'), {
    nummerVan,
    onBron: (bronnen, i) => bronscherm.toon(bronnen, i),
  });

  const kaart = new Kaart($('kaart'), {
    vak: document.querySelector('.kaartvak'),
    volgKnop: $('knop-volg'),
    route,
    punten,
    onPuntTik: (p) => vaarNaar(p),
    bedekteHoogte: () => {
      const el = $('infopaneel');
      return el.hidden ? 0 : el.offsetHeight + 16;
    },
  });

  const tijdlijn = new Tijdlijn($('tijdlijn'), {
    punten,
    duur: speler.duur,
    onZoek: (t) => speler.zoek(t),
  });

  // ---------- Synchronisatie video ↔ kaart ----------
  function actiefOp(t) {
    let kandidaat = null;
    for (const p of punten) {
      if (p.tijdstip <= t) kandidaat = p; else break;
    }
    return kandidaat && t < kandidaat.tijdstip + bereik ? kandidaat : null;
  }

  const tijdEl = $('tijd');
  const filmLabel = $('film-label');
  let vorigLabel = null;

  function bijwerken(t) {
    const pos = route.positie(t);
    kaart.zetBoot(pos, speler.richting);
    kaart.zetGevaren(route.gevarenTot(t));

    const actief = actiefOp(t);
    kaart.zetStatus(t, actief?.id ?? null);
    tijdlijn.zet(t, actief?.id ?? null);
    paneel.volg(actief);

    if ((actief?.id ?? null) !== vorigLabel) {
      vorigLabel = actief?.id ?? null;
      filmLabel.hidden = !actief;
      if (actief) {
        const nr = document.createElement('b');
        nr.textContent = String(nummerVan(actief));
        filmLabel.replaceChildren(nr, document.createTextNode(actief.titel));
      }
    }
    tijdEl.textContent = `${tijdLabel(t)} / ${tijdLabel(speler.duur)}`;
  }

  function statusBijwerken() {
    app.classList.toggle('speelt', speler.speelt);
    app.classList.toggle('achteruit', speler.richting === -1);
    $('knop-afspelen').setAttribute('aria-label', speler.speelt ? 'Pauzeren' : 'Afspelen');
    $('knop-vooruit-varen').classList.toggle('is-actief', speler.richting === 1);
    $('knop-achteruit-varen').classList.toggle('is-actief', speler.richting === -1);
    $('knop-vooruit-varen').setAttribute('aria-pressed', String(speler.richting === 1));
    $('knop-achteruit-varen').setAttribute('aria-pressed', String(speler.richting === -1));
    kaart.zetBoot(route.positie(speler.tijd), speler.richting);
  }

  speler.addEventListener('tijd', (e) => bijwerken(e.detail));
  speler.addEventListener('status', statusBijwerken);

  // ---------- Klikken om te varen (TO §7) ----------
  const tip = $('kaart-tip');
  const verbergTip = () => tip.classList.add('is-weg');

  function vaarNaar(p) {
    verbergTip();
    paneel.toon(p, { doorGebruiker: true });   // eerst tonen, dan springen: zo opent het paneel maar één keer
    speler.zoek(p.tijdstip);
    kaart.hervatVolgen(false);
    kaart.toonPunt(p);
    if (!speler.speelt) speler.speel();
  }

  // ---------- Bediening ----------
  $('knop-afspelen').addEventListener('click', () => speler.wissel());
  $('knop-groot-afspelen').addEventListener('click', () => speler.speel());
  $('film-beeld').addEventListener('click', (e) => { if (e.target.closest('button') === null) speler.wissel(); });
  $('knop-terug').addEventListener('click', () => speler.verspring(-10));
  $('knop-vooruit').addEventListener('click', () => speler.verspring(10));
  $('knop-vooruit-varen').addEventListener('click', () => { speler.zetRichting(1); speler.speel(); });
  $('knop-achteruit-varen').addEventListener('click', () => { speler.zetRichting(-1); speler.speel(); });

  document.addEventListener('keydown', (e) => {
    if (e.target !== document.body || bronscherm.open) return;
    if (e.key === ' ') { speler.wissel(); e.preventDefault(); }
    if (e.key === 'ArrowLeft') speler.verspring(-10);
    if (e.key === 'ArrowRight') speler.verspring(10);
  });

  // ---------- Hulp ----------
  const hulp = $('hulp');
  $('knop-help').addEventListener('click', () => { hulp.hidden = false; hulp.querySelector('[data-sluit]').focus(); });
  hulp.addEventListener('click', (e) => { if (e.target === hulp || e.target.closest('[data-sluit]')) hulp.hidden = true; });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hulp.hidden = true; });

  // ---------- Volledig scherm ----------
  const wisselScherm = () => {
    if (document.fullscreenElement) document.exitFullscreen?.();
    else document.documentElement.requestFullscreen?.().catch(() => {});
  };
  $('knop-scherm').addEventListener('click', wisselScherm);
  if (!document.documentElement.requestFullscreen) $('knop-scherm').hidden = true;

  // ---------- Startscherm en kiosk ----------
  const startscherm = $('startscherm');
  const isGrootTouchscherm = () => navigator.maxTouchPoints > 0 && Math.min(screen.width, screen.height) >= 1000;

  $('knop-start').addEventListener('click', () => {
    startscherm.classList.add('is-weg');
    if (isGrootTouchscherm() && !document.fullscreenElement) {
      document.documentElement.requestFullscreen?.().catch(() => {});
    }
    tip.classList.remove('is-weg');
    setTimeout(verbergTip, 15000);
    speler.zoek(0);
    speler.speel();
  });

  function naarStart() {
    sluitColofon();
    bronscherm.sluit();
    hulp.hidden = true;
    paneel.sluit();
    speler.pauzeer();
    speler.zetRichting(1);
    speler.zoek(0);
    kaart.overzicht();
    startscherm.classList.remove('is-weg');
    $('knop-start').focus({ preventScroll: true });
  }
  $('knop-opnieuw').addEventListener('click', naarStart);

  new Kiosk({
    seconden: Number(instellingen.inactiviteit_seconden) || 120,
    isBezig: () => speler.speelt,
    isStartscherm: () => !startscherm.classList.contains('is-weg'),
    opReset: naarStart,
  });

  // Lang indrukken op een touchscreen opent geen menu (kiosk)
  document.addEventListener('contextmenu', (e) => e.preventDefault());

  bijwerken(0);
  statusBijwerken();
  $('knop-start').disabled = false;
}

function toonFout(tekst) {
  $('fout-tekst').textContent = tekst;
  $('foutscherm').hidden = false;
  $('startscherm').classList.add('is-weg');
}

$('knop-fout-opnieuw').addEventListener('click', () => location.reload());

// ---------- Colofon (logo en knop): werkt ook al op het startscherm ----------
const colofon = $('colofon');
let colofonTerugFocus = null;
export function sluitColofon() {
  if (colofon.hidden) return;
  colofon.hidden = true;
  colofonTerugFocus?.focus({ preventScroll: true });
}
document.querySelectorAll('[data-colofon]').forEach((knop) => knop.addEventListener('click', () => {
  colofonTerugFocus = knop;
  colofon.hidden = false;
  colofon.querySelector('[data-sluit]').focus();
}));
colofon.addEventListener('click', (e) => { if (e.target === colofon || e.target.closest('[data-sluit]')) sluitColofon(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') sluitColofon(); });


start();
