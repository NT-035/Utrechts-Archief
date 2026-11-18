-- Voorbeeldinhoud Ontmoet de Oudegracht (gegenereerd door database/install.php --sql)
-- Voer eerst schema.sql uit.

INSERT INTO instellingen (sleutel, waarde) VALUES
  ('video_bestand', 'media/oudegracht-1929.mp4'),
  ('video_duur_seconden', '183'),
  ('inactiviteit_seconden', '120'),
  ('punt_bereik_seconden', '6'),
  ('achteruit_snelheid', '1'),
  ('colofon_makers', 'Schaakmat – Natan')
ON DUPLICATE KEY UPDATE waarde = waarde;

INSERT INTO route_punten (tijdstip_seconden, latitude, longitude, volgorde) VALUES
  (0, 52.089204, 5.121169, 1),
  (5, 52.089293, 5.121127, 2),
  (12, 52.089413, 5.121071, 3),
  (18, 52.089529, 5.121005, 4),
  (25, 52.089645, 5.120916, 5),
  (36, 52.089819, 5.120765, 6),
  (48, 52.090019, 5.120593, 7),
  (66, 52.090328, 5.12042, 8),
  (70, 52.090393, 5.120397, 9),
  (86, 52.090691, 5.120275, 10),
  (100, 52.090939, 5.12022, 11),
  (104, 52.091017, 5.12019, 12),
  (112, 52.091152, 5.120127, 13),
  (120, 52.091289, 5.120012, 14),
  (125, 52.091365, 5.119929, 15),
  (128, 52.091409, 5.119858, 16),
  (147, 52.091646, 5.119462, 17),
  (149, 52.091657, 5.119408, 18),
  (154, 52.091691, 5.119241, 19),
  (160, 52.091704, 5.11909, 20),
  (170, 52.091675, 5.118765, 21),
  (182, 52.091658, 5.11842, 22);

INSERT INTO punten (titel, tijdstip_seconden, latitude, longitude, volgorde, korte_tekst, uitleg, wist_je_dat, actief) VALUES
  ('Start bij de Gaardbrug', 0, 52.089204, 5.121169, 1, 'Hier begint onze vaartocht! We varen in een boot uit 1929 over de Oudegracht.', 'De film is bijna honderd jaar geleden gemaakt vanaf een varende boot. Jij kijkt nu precies mee met wat de cameraman toen zag.', 'Wist je dat de Oudegracht in 2027 al 900 jaar bestaat?', 1),
  ('Water in de stad', 3, 52.089279, 5.120997, 2, 'Kijk naar het water. Hoe ziet het eruit? Zie je andere boten?', 'Vroeger was de gracht een belangrijke weg door de stad. Goederen gingen niet met vrachtwagens, maar met boten.', 'Wist je dat je in deze film geen enkele motorboot met toeristen ziet?', 1),
  ('Zoek de Domtoren', 11, 52.089457, 5.121186, 3, 'Zie jij de Domtoren boven de huizen uitsteken? Hij staat hier heel dichtbij.', 'De Domtoren is het hoogste gebouw van de oude stad en was ook in 1929 al overal te zien.', 'Wist je dat de Domtoren veel ouder is dan de film? Hij staat er al honderden jaren.', 1),
  ('De werven', 16, 52.089498, 5.120883, 4, 'Zie je de lage kades vlak bij het water? Dat zijn de werven.', 'De werven liggen lager dan de straat. Hier werden spullen uit de boten gehaald.', 'Wist je dat zulke lage werven heel bijzonder zijn? Je ziet ze bijna nergens anders.', 1),
  ('Deuren in de kademuur', 22, 52.089651, 5.121057, 5, 'Kijk naar de muur langs het water. Zie je de deuren en boogjes?', 'Achter die deuren zitten werfkelders: ruimtes onder de straat, waar vroeger spullen werden bewaard.', 'Wist je dat mensen in de werfkelders werkten, onder de voeten van de voetgangers?', 1),
  ('Mensen op de kade', 27, 52.089668, 5.120747, 6, 'Zie je mensen lopen boven op de straat? Wat doen ze?', 'Kijk goed: lopen ze snel of rustig? Kijken ze naar de boot?', 'Wist je dat veel mensen in 1929 nog nooit gefilmd waren?', 1),
  ('Boten aan het werk', 34, 52.089863, 5.120876, 7, 'Zie je een boot liggen? Wat zou erin zitten?', 'Schuiten brachten goederen naar winkels en pakhuizen langs de gracht.', 'Wist je dat een boot veel meer kon vervoeren dan een paard met kar?', 1),
  ('Kinderen in 1929', 40, 52.089876, 5.120567, 8, 'Zie je kinderen in de film? Wat doen ze?', 'Kinderen speelden vaak buiten op straat en bij het water.', 'Wist je dat de kinderen in deze film nu bijna honderd jaar oud zouden zijn?', 1),
  ('Takels en haken', 45, 52.090041, 5.120723, 9, 'Kijk naar de gevels. Zie je haken of balken bovenin?', 'Met een takel trokken mensen zware spullen omhoog naar de zolder.', 'Wist je dat je die haken nog steeds aan veel oude huizen kunt zien?', 1),
  ('Winkels aan de gracht', 53, 52.090103, 5.120407, 10, 'Langs de gracht zaten veel winkels. Zie je borden met letters?', 'Winkeliers konden hun spullen via de werfkelder rechtstreeks uit de boot halen.', 'Wist je dat er nu nog steeds winkels en restaurants aan de Oudegracht zitten?', 1),
  ('Spullen verplaatsen', 60, 52.090283, 5.120585, 11, 'Hoe brachten mensen de spullen van de boot naar binnen?', 'Op de werf stonden karren en kruiwagens om goederen te verplaatsen.', 'Wist je dat sommige werfkelders heel diep onder de straat doorlopen?', 1),
  ('Maartensbrug', 67, 52.090368, 5.120271, 12, 'Daar is de Maartensbrug. Wie staat er op de brug?', 'De bruggen waren mooie plekken om naar de boten te kijken.', 'Wist je dat Sint Maarten de beschermheilige van Utrecht is?', 1),
  ('Bruggen van steen', 73, 52.090503, 5.120488, 13, 'Kijk naar de boog van de brug. Past onze boot er goed onder?', 'De bruggen hebben een boog, zodat boten eronderdoor kunnen varen.', 'Wist je dat je onder sommige bruggen bijna de onderkant kunt aanraken?', 1),
  ('Bomen langs de gracht', 78, 52.090561, 5.120192, 14, 'Zie je bomen langs het water? Hoe groot zijn ze?', 'Bomen geven schaduw en maken de gracht groen. Vergelijk de bomen met nu.', 'Wist je dat bomen heel oud kunnen worden? Misschien staan sommige er nog steeds.', 1),
  ('Pakhuizen', 85, 52.090723, 5.120401, 15, 'Sommige gebouwen zijn hoog en hebben weinig ramen. Wat zou daar in zitten?', 'In pakhuizen werden goederen opgeslagen, zoals graan, hout of stoffen.', 'Wist je dat veel oude pakhuizen nu woningen of kantoren zijn?', 1),
  ('Lantaarns', 91, 52.090801, 5.120118, 16, 'Zie je lantaarnpalen langs de kade?', 'Straatverlichting zorgde ervoor dat het ''s avonds veiliger was langs het water.', 'Wist je dat lantaarns vroeger met de hand werden aangestoken?', 1),
  ('Trappen naar de werf', 96, 52.090923, 5.120356, 17, 'Zie je trappen van de straat naar beneden?', 'Via trappen konden mensen van de straat naar de werf en de boten lopen.', 'Wist je dat je nu ook nog over de werven kunt wandelen?', 1),
  ('Het water zelf', 104, 52.091021, 5.120051, 18, 'Is het water glad of zijn er golfjes?', 'Boten, wind en mensen zorgen voor beweging op het water.', 'Wist je dat de Oudegracht ooit door mensen is gegraven?', 1),
  ('Kijk omhoog', 109, 52.091162, 5.120259, 19, 'Kijk eens naar de daken. Welke vormen zie je?', 'Vanaf het water zie je de stad van onderaf. Zo zie je dingen die je vanaf de straat mist.', 'Wist je dat de huizen vanaf de boot extra hoog lijken?', 1),
  ('Hoeden en petten', 114, 52.091193, 5.119944, 20, 'Wat dragen de mensen op hun hoofd?', 'In 1929 zagen kleding en hoeden er anders uit dan nu. Vergelijk het eens met jouw kleding.', 'Wist je dat bijna iedereen toen een hoed of pet droeg als hij naar buiten ging?', 1),
  ('Fietsen in de stad', 122, 52.091391, 5.120059, 21, 'Zie je ergens een fiets?', 'Fietsen, karren en voetgangers deelden de straat. Auto''s waren er nog maar weinig.', 'Wist je dat Utrecht nu een van de drukste fietssteden ter wereld is?', 1),
  ('Een stille film', 127, 52.091362, 5.119748, 22, 'Hoor je iets? Deze film heeft geen geluid.', 'Wat denk je dat je zou horen? Een bel, paarden, roepende mensen, klotsend water?', 'Wist je dat de meeste films toen nog zonder geluid werden gemaakt?', 1),
  ('Bijna bij het stadhuis', 135, 52.091572, 5.119775, 23, 'We komen in het hart van de stad. Wordt het drukker?', 'Rond het stadhuis kwamen veel wegen en het water samen.', 'Wist je dat het archief veel papieren van het stadhuis bewaart?', 1),
  ('Stadhuisbrug', 140, 52.091526, 5.119473, 24, 'We varen onder de Stadhuisbrug door, midden in de stad.', 'Op deze brug was het altijd druk: lopend, op de fiets of met een kar.', 'Wist je dat de Stadhuisbrug zo breed is dat het bijna een plein lijkt?', 1),
  ('Het stadhuis', 145, 52.091727, 5.119484, 25, 'Hier staat het stadhuis van Utrecht. Kun je het zien?', 'In het stadhuis werden beslissingen over de stad genomen, ook over de gracht en de bruggen.', 'Wist je dat je oude stadsbesluiten nog steeds kunt opzoeken in het archief?', 1),
  ('Druk op de kade', 153, 52.091611, 5.119217, 26, 'Tel eens hoeveel mensen je ziet!', 'In het centrum woonden en werkten veel mensen dicht bij elkaar.', 'Wist je dat er vroeger veel meer mensen boven de winkels woonden?', 1),
  ('Toen en nu', 160, 52.091778, 5.118999, 27, 'Wat is er anders dan nu? Noem er drie!', 'Denk aan kleding, vervoer, winkels en geluiden. Wat is hetzelfde gebleven?', 'Wist je dat veel gevels er nog bijna net zo uitzien als in 1929?', 1),
  ('De bocht', 165, 52.091603, 5.118875, 28, 'Voel je het? De boot maakt hier een bocht naar links.', 'De Oudegracht loopt niet kaarsrecht. Hier buigt hij af in de richting van de Bakkerbrug.', 'Wist je dat de schipper goed moest sturen om de kademuur niet te raken?', 1),
  ('Bijna bij de Bezembrug', 173, 52.091749, 5.118628, 29, 'We zijn bijna aan het einde. Kijk nog één keer goed rond!', 'Aan het einde van de film zie je de Bezembrug.', 'Wist je dat je deze tocht in het echt ook kunt maken met een rondvaartboot?', 1),
  ('Einde bij de Bezembrug', 177, 52.091658, 5.11842, 30, 'Einde van de tocht! Wil je terugvaren of een punt opnieuw bekijken?', 'Tik op een nummer op de kaart om terug te gaan naar een plek die je nog eens wilt zien.', 'Wist je dat je nu een stukje van 900 jaar Oudegracht hebt gezien?', 1);

INSERT INTO bronnen (punt_id, type, titel, bestandspad, bijschrift, bronvermelding, jaar) SELECT id, 'foto', 'Voorbeeldbron (foto)', 'public/img/voorbeeld-bron.svg', 'Hier komt een foto uit het bronmateriaal van Het Utrechts Archief.', 'Voorbeeld – vervang door bronvermelding HUA', 1929 FROM punten WHERE volgorde = 1 ORDER BY id LIMIT 1;
INSERT INTO bronnen (punt_id, type, titel, bestandspad, bijschrift, bronvermelding, jaar) SELECT id, 'foto', 'Voorbeeldbron (foto)', 'public/img/voorbeeld-bron.svg', 'Hier komt een foto uit het bronmateriaal van Het Utrechts Archief.', 'Voorbeeld – vervang door bronvermelding HUA', 1929 FROM punten WHERE volgorde = 3 ORDER BY id LIMIT 1;
INSERT INTO bronnen (punt_id, type, titel, bestandspad, bijschrift, bronvermelding, jaar) SELECT id, 'kaart', 'Voorbeeldbron (kaart)', 'public/img/voorbeeld-bron.svg', 'Hier komt een kaart uit het bronmateriaal van Het Utrechts Archief.', 'Voorbeeld – vervang door bronvermelding HUA', 1929 FROM punten WHERE volgorde = 3 ORDER BY id LIMIT 1;
INSERT INTO bronnen (punt_id, type, titel, bestandspad, bijschrift, bronvermelding, jaar) SELECT id, 'foto', 'Voorbeeldbron (foto)', 'public/img/voorbeeld-bron.svg', 'Hier komt een foto uit het bronmateriaal van Het Utrechts Archief.', 'Voorbeeld – vervang door bronvermelding HUA', 1929 FROM punten WHERE volgorde = 5 ORDER BY id LIMIT 1;
INSERT INTO bronnen (punt_id, type, titel, bestandspad, bijschrift, bronvermelding, jaar) SELECT id, 'kaart', 'Voorbeeldbron (kaart)', 'public/img/voorbeeld-bron.svg', 'Hier komt een kaart uit het bronmateriaal van Het Utrechts Archief.', 'Voorbeeld – vervang door bronvermelding HUA', 1929 FROM punten WHERE volgorde = 12 ORDER BY id LIMIT 1;
INSERT INTO bronnen (punt_id, type, titel, bestandspad, bijschrift, bronvermelding, jaar) SELECT id, 'foto', 'Voorbeeldbron (foto)', 'public/img/voorbeeld-bron.svg', 'Hier komt een foto uit het bronmateriaal van Het Utrechts Archief.', 'Voorbeeld – vervang door bronvermelding HUA', 1929 FROM punten WHERE volgorde = 24 ORDER BY id LIMIT 1;
INSERT INTO bronnen (punt_id, type, titel, bestandspad, bijschrift, bronvermelding, jaar) SELECT id, 'document', 'Voorbeeldbron (document)', 'public/img/voorbeeld-bron.svg', 'Hier komt een document uit het bronmateriaal van Het Utrechts Archief.', 'Voorbeeld – vervang door bronvermelding HUA', 1929 FROM punten WHERE volgorde = 24 ORDER BY id LIMIT 1;
INSERT INTO bronnen (punt_id, type, titel, bestandspad, bijschrift, bronvermelding, jaar) SELECT id, 'document', 'Voorbeeldbron (document)', 'public/img/voorbeeld-bron.svg', 'Hier komt een document uit het bronmateriaal van Het Utrechts Archief.', 'Voorbeeld – vervang door bronvermelding HUA', 1929 FROM punten WHERE volgorde = 25 ORDER BY id LIMIT 1;
