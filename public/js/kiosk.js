/**
 * Kioskgedrag (TO §7): na een periode zonder aanraking terug naar het startscherm,
 * zodat elke nieuwe bezoeker bij het begin start.
 *
 * Zolang de film speelt, telt dat als 'bezig': een familie die rustig kijkt,
 * wordt niet halverwege teruggestuurd. De teller loopt pas als de film stilstaat
 * (gepauzeerd of afgelopen).
 */
export class Kiosk {
  constructor({ seconden, isBezig, isStartscherm, opReset }) {
    this.ms = seconden * 1000;
    this.isBezig = isBezig;
    this.isStartscherm = isStartscherm;
    this.opReset = opReset;
    this.laatste = Date.now();

    const tik = () => { this.laatste = Date.now(); };
    for (const type of ['pointerdown', 'keydown', 'wheel']) {
      document.addEventListener(type, tik, { passive: true, capture: true });
    }
    setInterval(() => this.#controleer(), 1000);
  }

  #controleer() {
    if (this.isStartscherm() || this.isBezig()) {
      this.laatste = Date.now();
      return;
    }
    if (Date.now() - this.laatste >= this.ms) {
      this.laatste = Date.now();
      this.opReset();
    }
  }
}
