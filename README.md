# Ontmoet de Oudegracht

Interactieve website voor de tentoonstelling *Ontmoet de Oudegracht* van Het Utrechts Archief (maart 2027).
De film uit 1929 van de vaartocht van de Gaardbrug tot de Bezembrug speelt naast een kaart die live meebeweegt.
Langs de route verschijnen infopunten met bronnen uit het archief. Gebouwd volgens het Technisch Ontwerp in `documenten/`.

## Wat zit erin

| Onderdeel | Map | Wat het doet |
|---|---|---|
| Website (digibord) | `public/` | Startscherm, video + kaart, tijdlijn, infopaneel, brontoning, kioskmodus |
| API | `api/` | `punten.php`, `punt.php?id=`, `route.php`, `instellingen.php` (JSON, alleen lezen) |
| CMS | `admin/` | Inloggen, infopunten + bronnen, route van de boot, instellingen, beheerders |
| Gedeeld | `includes/` | PDO-verbinding, beveiliging (sessies, CSRF, inlogpogingen), uploads |
| Database | `database/` | `schema.sql`, `seed.sql`, `install.php` |
| Video | `media/` | Plaats hier `oudegracht-1929.mp4` |
| Uploads | `uploads/` | Bronbestanden uit het CMS (scripts zijn hier geblokkeerd) |

## Installeren (XAMPP of server met PHP 8 + MySQL)

1. **Config**: kopieer `config/config.example.php` naar `config/config.php` en vul de databasegegevens in.
   Gebruik voor lokaal testen eventueel twee keer dezelfde gebruiker; op de server een lees- en een schrijfgebruiker (TO §6).
2. **Database**, kies één van beide:
   - Opdrachtregel (maakt ook de databasegebruikers en een CMS-beheerder aan):
     ```
     php database/install.php --root-wachtwoord=JOUW_ROOT_WW --admin-email=jij@example.nl --admin-wachtwoord=MinstensTienTekens
     ```
   - phpMyAdmin: importeer `database/schema.sql` en daarna `database/seed.sql`. Maak de beheerder dan aan met
     `php database/install.php --admin-email=… --admin-wachtwoord=…`.
3. **Video**: zet de film als `media/oudegracht-1929.mp4` (H.264). Zolang die ontbreekt draait de site in **demomodus**:
   kaart, tijdlijn en infopunten lopen dan mee op een virtuele klok.
4. Open `http://localhost/…/Utrechts-Archief/` voor de website en `…/admin/` voor het CMS.

Op de echte server: zet HTTPS aan (de redirect staat klaar in `.htaccess`). De sessiecookie wordt dan automatisch `Secure`.

## Voorbeeldinhoud

De seed bevat **30 infopunten** en een route langs de echte Oudegracht van de Gaardbrug (bij de Domtoren) tot de Bezembrug
(± 375 m, grachtlijn uit OpenStreetMap). De teksten zijn neutrale kijkopdrachten en de bronnen zijn een placeholder-afbeelding.
Vervang ze via het CMS door het bronmateriaal van HUA. Controleer ook de tijdstippen zodra de echte film er is:
- **CMS → Infopunten → punt bewerken**: met "Gebruik huidige videotijd" neem je het tijdstip direct uit de film.
- **CMS → Route**: tik op de kaart om routepunten toe te voegen, sleep ze, of verdeel de tijden gelijkmatig.

## Gedrag op het digibord

- Tik op een nummer op de kaart: de video vaart (vooruit of achteruit) naar die plek.
- *Vooruit varen / Terug varen*: browsers kunnen video niet achteruit afspelen, dus bij terug varen springt de speler steeds een stukje terug.
- Een infopaneel gaat vanzelf weer dicht als de boot verder vaart, tenzij een bezoeker het heeft aangeraakt.
- Na 2 minuten zonder aanraking (instelbaar) gaat de site terug naar het startscherm. Bewuste keuze: de teller loopt alleen als de film **stilstaat**,
  zodat een familie die rustig kijkt niet halverwege wordt teruggestuurd.
- Zonder internet ontbreken de kaarttegels; de gracht, de punten en de boot blijven als schematische kaart zichtbaar.
- Kioskmodus: start de browser bijvoorbeeld met `chrome --kiosk --autoplay-policy=no-user-gesture-required http://…/public/`.

## Afwijkingen van het Technisch Ontwerp

- Extra kolom `punten.wist_je_dat` voor de 'wist je dat'-regel uit TO §7.
- Extra tabel `inlogpogingen` voor de beperking van mislukte inlogpogingen (TO §6).
- Extra endpoint `api/instellingen.php` zodat videobestand en tijden uit het CMS komen.
