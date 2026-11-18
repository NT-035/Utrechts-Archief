<?php
/**
 * Voorbeeldinhoud – Ontmoet de Oudegracht
 *
 * LET OP: dit is VOORBEELDINHOUD (Plan van Aanpak §4: "data-gedreven met voorbeeldinhoud").
 * - De route volgt de Oudegracht van de Gaardbrug (bij de Domtoren) noordwaarts
 *   tot de Bezembrug (± 375 m), op basis van de grachtlijn in OpenStreetMap.
 * - De tijdstippen gaan uit van de film van 3 minuten en een boot die met een
 *   gelijke snelheid vaart. Pas ze aan zodra de echte film van 1929 is bekeken
 *   (CMS → Route en CMS → Infopunten).
 * - De teksten zijn neutrale kijkopdrachten voor families. Vervang ze door de
 *   informatie uit het bronmateriaal van Het Utrechts Archief.
 */

declare(strict_types=1);

const SEED_VIDEO_DUUR = 182; // seconden (lengte van de film van HUA)

/**
 * Grachtlijn van de Gaardbrug naar de Bezembrug: [lat, lng].
 * Bron geometrie: © OpenStreetMap-bijdragers (ODbL).
 */
const SEED_ROUTE = [
    [52.089204, 5.121169], // Gaardbrug
    [52.089293, 5.121127],
    [52.089413, 5.121071],
    [52.089529, 5.121005],
    [52.089645, 5.120916],
    [52.089819, 5.120765],
    [52.090019, 5.120593],
    [52.090328, 5.120420],
    [52.090393, 5.120397], // Maartensbrug
    [52.090691, 5.120275],
    [52.090939, 5.120220],
    [52.091017, 5.120190],
    [52.091152, 5.120127],
    [52.091289, 5.120012],
    [52.091365, 5.119929],
    [52.091409, 5.119858],
    [52.091646, 5.119462], // Stadhuisbrug
    [52.091657, 5.119408],
    [52.091691, 5.119241],
    [52.091704, 5.119090],
    [52.091675, 5.118765],
    [52.091658, 5.118420], // Bezembrug
];

/**
 * Infopunten: [positie op de route (0–1), titel, korte tekst, uitleg, wist je dat]
 * Minimaal 30 punten (briefing).
 */
const SEED_PUNTEN = [
    [0.00, 'Start bij de Gaardbrug', 'Hier begint onze vaartocht! We varen in een boot uit 1929 over de Oudegracht.', 'De film is bijna honderd jaar geleden gemaakt vanaf een varende boot. Jij kijkt nu precies mee met wat de cameraman toen zag.', 'Wist je dat de Oudegracht in 2027 al 900 jaar bestaat?'],
    [0.03, 'Water in de stad', 'Kijk naar het water. Hoe ziet het eruit? Zie je andere boten?', 'Vroeger was de gracht een belangrijke weg door de stad. Goederen gingen niet met vrachtwagens, maar met boten.', 'Wist je dat je in deze film geen enkele motorboot met toeristen ziet?'],
    [0.07, 'Zoek de Domtoren', 'Zie jij de Domtoren boven de huizen uitsteken? Hij staat hier heel dichtbij.', 'De Domtoren is het hoogste gebouw van de oude stad en was ook in 1929 al overal te zien.', 'Wist je dat de Domtoren veel ouder is dan de film? Hij staat er al honderden jaren.'],
    [0.10, 'De werven', 'Zie je de lage kades vlak bij het water? Dat zijn de werven.', 'De werven liggen lager dan de straat. Hier werden spullen uit de boten gehaald.', 'Wist je dat zulke lage werven heel bijzonder zijn? Je ziet ze bijna nergens anders.'],
    [0.13, 'Deuren in de kademuur', 'Kijk naar de muur langs het water. Zie je de deuren en boogjes?', 'Achter die deuren zitten werfkelders: ruimtes onder de straat, waar vroeger spullen werden bewaard.', 'Wist je dat mensen in de werfkelders werkten, onder de voeten van de voetgangers?'],
    [0.16, 'Mensen op de kade', 'Zie je mensen lopen boven op de straat? Wat doen ze?', 'Kijk goed: lopen ze snel of rustig? Kijken ze naar de boot?', 'Wist je dat veel mensen in 1929 nog nooit gefilmd waren?'],
    [0.20, 'Boten aan het werk', 'Zie je een boot liggen? Wat zou erin zitten?', 'Schuiten brachten goederen naar winkels en pakhuizen langs de gracht.', 'Wist je dat een boot veel meer kon vervoeren dan een paard met kar?'],
    [0.23, 'Kinderen in 1929', 'Zie je kinderen in de film? Wat doen ze?', 'Kinderen speelden vaak buiten op straat en bij het water.', 'Wist je dat de kinderen in deze film nu bijna honderd jaar oud zouden zijn?'],
    [0.26, 'Takels en haken', 'Kijk naar de gevels. Zie je haken of balken bovenin?', 'Met een takel trokken mensen zware spullen omhoog naar de zolder.', 'Wist je dat je die haken nog steeds aan veel oude huizen kunt zien?'],
    [0.30, 'Winkels aan de gracht', 'Langs de gracht zaten veel winkels. Zie je borden met letters?', 'Winkeliers konden hun spullen via de werfkelder rechtstreeks uit de boot halen.', 'Wist je dat er nu nog steeds winkels en restaurants aan de Oudegracht zitten?'],
    [0.34, 'Spullen verplaatsen', 'Hoe brachten mensen de spullen van de boot naar binnen?', 'Op de werf stonden karren en kruiwagens om goederen te verplaatsen.', 'Wist je dat sommige werfkelders heel diep onder de straat doorlopen?'],
    [0.38, 'Maartensbrug', 'Daar is de Maartensbrug. Wie staat er op de brug?', 'De bruggen waren mooie plekken om naar de boten te kijken.', 'Wist je dat Sint Maarten de beschermheilige van Utrecht is?'],
    [0.41, 'Bruggen van steen', 'Kijk naar de boog van de brug. Past onze boot er goed onder?', 'De bruggen hebben een boog, zodat boten eronderdoor kunnen varen.', 'Wist je dat je onder sommige bruggen bijna de onderkant kunt aanraken?'],
    [0.44, 'Bomen langs de gracht', 'Zie je bomen langs het water? Hoe groot zijn ze?', 'Bomen geven schaduw en maken de gracht groen. Vergelijk de bomen met nu.', 'Wist je dat bomen heel oud kunnen worden? Misschien staan sommige er nog steeds.'],
    [0.48, 'Pakhuizen', 'Sommige gebouwen zijn hoog en hebben weinig ramen. Wat zou daar in zitten?', 'In pakhuizen werden goederen opgeslagen, zoals graan, hout of stoffen.', 'Wist je dat veel oude pakhuizen nu woningen of kantoren zijn?'],
    [0.51, 'Lantaarns', 'Zie je lantaarnpalen langs de kade?', 'Straatverlichting zorgde ervoor dat het \'s avonds veiliger was langs het water.', 'Wist je dat lantaarns vroeger met de hand werden aangestoken?'],
    [0.54, 'Trappen naar de werf', 'Zie je trappen van de straat naar beneden?', 'Via trappen konden mensen van de straat naar de werf en de boten lopen.', 'Wist je dat je nu ook nog over de werven kunt wandelen?'],
    [0.58, 'Het water zelf', 'Is het water glad of zijn er golfjes?', 'Boten, wind en mensen zorgen voor beweging op het water.', 'Wist je dat de Oudegracht ooit door mensen is gegraven?'],
    [0.61, 'Kijk omhoog', 'Kijk eens naar de daken. Welke vormen zie je?', 'Vanaf het water zie je de stad van onderaf. Zo zie je dingen die je vanaf de straat mist.', 'Wist je dat de huizen vanaf de boot extra hoog lijken?'],
    [0.64, 'Hoeden en petten', 'Wat dragen de mensen op hun hoofd?', 'In 1929 zagen kleding en hoeden er anders uit dan nu. Vergelijk het eens met jouw kleding.', 'Wist je dat bijna iedereen toen een hoed of pet droeg als hij naar buiten ging?'],
    [0.68, 'Fietsen in de stad', 'Zie je ergens een fiets?', 'Fietsen, karren en voetgangers deelden de straat. Auto\'s waren er nog maar weinig.', 'Wist je dat Utrecht nu een van de drukste fietssteden ter wereld is?'],
    [0.71, 'Een stille film', 'Hoor je iets? Deze film heeft geen geluid.', 'Wat denk je dat je zou horen? Een bel, paarden, roepende mensen, klotsend water?', 'Wist je dat de meeste films toen nog zonder geluid werden gemaakt?'],
    [0.75, 'Bijna bij het stadhuis', 'We komen in het hart van de stad. Wordt het drukker?', 'Rond het stadhuis kwamen veel wegen en het water samen.', 'Wist je dat het archief veel papieren van het stadhuis bewaart?'],
    [0.78, 'Stadhuisbrug', 'We varen onder de Stadhuisbrug door, midden in de stad.', 'Op deze brug was het altijd druk: lopend, op de fiets of met een kar.', 'Wist je dat de Stadhuisbrug zo breed is dat het bijna een plein lijkt?'],
    [0.81, 'Het stadhuis', 'Hier staat het stadhuis van Utrecht. Kun je het zien?', 'In het stadhuis werden beslissingen over de stad genomen, ook over de gracht en de bruggen.', 'Wist je dat je oude stadsbesluiten nog steeds kunt opzoeken in het archief?'],
    [0.85, 'Druk op de kade', 'Tel eens hoeveel mensen je ziet!', 'In het centrum woonden en werkten veel mensen dicht bij elkaar.', 'Wist je dat er vroeger veel meer mensen boven de winkels woonden?'],
    [0.89, 'Toen en nu', 'Wat is er anders dan nu? Noem er drie!', 'Denk aan kleding, vervoer, winkels en geluiden. Wat is hetzelfde gebleven?', 'Wist je dat veel gevels er nog bijna net zo uitzien als in 1929?'],
    [0.92, 'De bocht', 'Voel je het? De boot maakt hier een bocht naar links.', 'De Oudegracht loopt niet kaarsrecht. Hier buigt hij af in de richting van de Bakkerbrug.', 'Wist je dat de schipper goed moest sturen om de kademuur niet te raken?'],
    [0.96, 'Bijna bij de Bezembrug', 'We zijn bijna aan het einde. Kijk nog één keer goed rond!', 'Aan het einde van de film zie je de Bezembrug.', 'Wist je dat je deze tocht in het echt ook kunt maken met een rondvaartboot?'],
    [1.00, 'Einde bij de Bezembrug', 'Einde van de tocht! Wil je terugvaren of een punt opnieuw bekijken?', 'Tik op een nummer op de kaart om terug te gaan naar een plek die je nog eens wilt zien.', 'Wist je dat je nu een stukje van 900 jaar Oudegracht hebt gezien?'],
];

/** Afstand (m) waarmee punten om en om naar de linker- en rechterkade schuiven, zodat de nummers niet overlappen. */
const SEED_KADE_VERSCHUIVING = 9;

/** Instellingen (sleutel => waarde). */
const SEED_INSTELLINGEN = [
    'video_bestand'          => 'media/oudegracht-1929.mp4',
    'video_duur_seconden'    => '183',   // gebruikt in de demomodus zolang er geen video is
    'inactiviteit_seconden'  => '120',   // terug naar het startscherm na 2 minuten (TO §7)
    'punt_bereik_seconden'   => '6',     // hoe lang een infopunt actief blijft na het tijdstip
    'achteruit_snelheid'     => '1',     // snelheid bij terug varen (1 = normaal)
    'colofon_makers'         => 'Schaakmat – Natan', // naam van de makers in het colofon
];

/** Afstand in meters tussen twee coördinaten (haversine). */
function seed_afstand(array $a, array $b): float
{
    $r = 6371000;
    [$lat1, $lng1] = array_map('deg2rad', $a);
    [$lat2, $lng2] = array_map('deg2rad', $b);
    $h = sin(($lat2 - $lat1) / 2) ** 2 + cos($lat1) * cos($lat2) * sin(($lng2 - $lng1) / 2) ** 2;
    return 2 * $r * asin(sqrt($h));
}

/**
 * Bouwt de route met tijdstippen (evenredig met de afstand) en de punten
 * met coördinaten langs de route.
 */
function seed_opbouwen(): array
{
    $cum = [0.0];
    for ($i = 1; $i < count(SEED_ROUTE); $i++) {
        $cum[$i] = $cum[$i - 1] + seed_afstand(SEED_ROUTE[$i - 1], SEED_ROUTE[$i]);
    }
    $totaal = end($cum);

    $route = [];
    foreach (SEED_ROUTE as $i => [$lat, $lng]) {
        $route[] = [
            'tijdstip_seconden' => (int) round($cum[$i] / $totaal * SEED_VIDEO_DUUR),
            'latitude' => $lat, 'longitude' => $lng, 'volgorde' => $i + 1,
        ];
    }

    $mPerLat = 111320;
    $mPerLng = 111320 * cos(deg2rad(SEED_ROUTE[0][0]));

    $punten = [];
    $laatste = count(SEED_PUNTEN) - 1;
    foreach (SEED_PUNTEN as $i => [$fractie, $titel, $kort, $uitleg, $wist]) {
        $afstand = $fractie * $totaal;
        $j = 1;
        while ($j < count($cum) - 1 && $cum[$j] < $afstand) {
            $j++;
        }
        [$aLat, $aLng] = SEED_ROUTE[$j - 1];
        [$bLat, $bLng] = SEED_ROUTE[$j];
        $segment = $cum[$j] - $cum[$j - 1];
        $t = $segment > 0 ? ($afstand - $cum[$j - 1]) / $segment : 0;
        $lat = $aLat + ($bLat - $aLat) * $t;
        $lng = $aLng + ($bLng - $aLng) * $t;

        // Om en om naar links/rechts verschuiven (loodrecht op de vaarrichting); begin en eind op de gracht.
        if ($i !== 0 && $i !== $laatste) {
            $dy = ($bLat - $aLat) * $mPerLat;
            $dx = ($bLng - $aLng) * $mPerLng;
            $len = hypot($dx, $dy) ?: 1;
            $kant = $i % 2 === 0 ? 1 : -1;
            $lat += (-$dx / $len) * SEED_KADE_VERSCHUIVING * $kant / $mPerLat;
            $lng += ($dy / $len) * SEED_KADE_VERSCHUIVING * $kant / $mPerLng;
        }

        $punten[] = [
            'titel' => $titel,
            // net vóór het passeren verschijnen, zodat kijkers het op tijd zien
            'tijdstip_seconden' => max(0, min(SEED_VIDEO_DUUR - 5, (int) round($fractie * SEED_VIDEO_DUUR) - 2)),
            'latitude' => round($lat, 6), 'longitude' => round($lng, 6),
            'volgorde' => $i + 1,
            'korte_tekst' => $kort, 'uitleg' => $uitleg, 'wist_je_dat' => $wist, 'actief' => 1,
        ];
    }

    // Voorbeeldbronnen (placeholder) bij een paar punten, zodat de brontoning te testen is.
    $bronnen = [];
    foreach ([1 => ['foto'], 3 => ['foto', 'kaart'], 5 => ['foto'], 12 => ['kaart'], 24 => ['foto', 'document'], 25 => ['document']] as $volgorde => $types) {
        foreach ($types as $type) {
            $bronnen[] = [
                'punt_volgorde' => $volgorde,
                'type' => $type,
                'titel' => 'Voorbeeldbron (' . $type . ')',
                'bestandspad' => 'public/img/voorbeeld-bron.svg',
                'bijschrift' => 'Hier komt een ' . $type . ' uit het bronmateriaal van Het Utrechts Archief.',
                'bronvermelding' => 'Voorbeeld – vervang door bronvermelding HUA',
                'jaar' => 1929,
            ];
        }
    }

    return ['route' => $route, 'punten' => $punten, 'bronnen' => $bronnen];
}
