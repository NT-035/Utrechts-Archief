/**
 * Data ophalen bij de PHP-API (TO §3). Alle inhoud komt uit de database,
 * zodat wijzigingen in het CMS direct zichtbaar zijn.
 */
const BASIS = '../api/';

async function haal(pad) {
  const antwoord = await fetch(BASIS + pad, { headers: { Accept: 'application/json' }, cache: 'no-store' });
  if (!antwoord.ok) throw new Error(`API ${pad} gaf status ${antwoord.status}`);
  return antwoord.json();
}

export const api = {
  instellingen: () => haal('instellingen.php'),
  punten: () => haal('punten.php'),
  route: () => haal('route.php'),
  punt: (id) => haal(`punt.php?id=${encodeURIComponent(id)}`),
};

/** Paden in de database zijn relatief aan de projectmap; de frontend staat in /public. */
export function bestandUrl(pad) {
  if (!pad) return '';
  if (/^https?:\/\//i.test(pad)) return pad;
  return '../' + String(pad).replace(/^\/+/, '');
}
