<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

vereis_login();
$db = db_schrijven();
$fouten = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();

    $video = tekst('video_bestand', 255, false, $fouten);
    if ($video !== '' && (str_contains($video, '..') || !preg_match('~^[A-Za-z0-9_\-./]+\.(mp4|webm)$~', $video))) {
        $fouten['video_bestand'] = 'Gebruik een pad binnen de projectmap dat eindigt op .mp4 of .webm, bijvoorbeeld media/oudegracht-1929.mp4.';
    }
    $waarden = [
        'video_bestand'         => ltrim($video, '/'),
        'video_duur_seconden'   => (string) geheel_getal('video_duur_seconden', 10, 86400, $fouten),
        'inactiviteit_seconden' => (string) geheel_getal('inactiviteit_seconden', 15, 3600, $fouten),
        'punt_bereik_seconden'  => (string) geheel_getal('punt_bereik_seconden', 2, 600, $fouten),
        'achteruit_snelheid'    => (string) geheel_getal('achteruit_snelheid', 1, 8, $fouten),
        'colofon_makers'        => tekst('colofon_makers', 255, true, $fouten),
    ];

    if (!$fouten) {
        $stmt = $db->prepare('INSERT INTO instellingen (sleutel, waarde) VALUES (:k, :v) ON DUPLICATE KEY UPDATE waarde = VALUES(waarde)');
        foreach ($waarden as $k => $v) {
            $stmt->execute(['k' => $k, 'v' => $v]);
        }
        flash('De instellingen zijn opgeslagen.');
        stuur_door('instellingen.php');
    }
}

$i = $db->query('SELECT sleutel, waarde FROM instellingen')->fetchAll(PDO::FETCH_KEY_PAIR);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $i = array_merge($i, array_intersect_key($_POST, array_flip(['video_bestand', 'video_duur_seconden', 'inactiviteit_seconden', 'punt_bereik_seconden', 'achteruit_snelheid', 'colofon_makers'])));
}
$videoPad = (string) ($i['video_bestand'] ?? '');
$videoBestaat = $videoPad !== '' && is_file(__DIR__ . '/../' . $videoPad);

admin_kop('Instellingen', 'instellingen');
?>
<h1>Instellingen</h1>

<form method="post" class="formulier formulier--smal" novalidate>
  <?= csrf_veld() ?>

  <section class="kaartje">
    <h2>Video</h2>
    <label class="veld">
      <span>Videobestand</span>
      <input type="text" name="video_bestand" value="<?= e($videoPad) ?>" maxlength="255" placeholder="media/oudegracht-1929.mp4">
      <small class="hint">
        Pad vanaf de projectmap. Zet de film (MP4, H.264) in de map <code>media/</code> op de server.
        Status: <strong class="<?= $videoBestaat ? 'ok' : 'niet-ok' ?>"><?= $videoBestaat ? 'bestand gevonden' : 'niet gevonden – website draait in demomodus' ?></strong>
      </small>
      <?= veld_fout($fouten, 'video_bestand') ?>
    </label>
    <label class="veld">
      <span>Duur in demomodus (seconden)</span>
      <input type="number" name="video_duur_seconden" value="<?= e($i['video_duur_seconden'] ?? 600) ?>" min="10">
      <small class="hint">Alleen gebruikt zolang er geen video is. Met een echte video telt de lengte van het bestand.</small>
      <?= veld_fout($fouten, 'video_duur_seconden') ?>
    </label>
    <label class="veld">
      <span>Snelheid bij terug varen</span>
      <select name="achteruit_snelheid">
        <?php foreach ([1 => 'Normaal (1×)', 2 => 'Sneller (2×)', 4 => 'Snel (4×)'] as $w => $l): ?>
          <option value="<?= $w ?>" <?= (int) ($i['achteruit_snelheid'] ?? 1) === $w ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <?= veld_fout($fouten, 'achteruit_snelheid') ?>
    </label>
  </section>

  <section class="kaartje">
    <h2>Infopunten en kiosk</h2>
    <label class="veld">
      <span>Hoe lang blijft een infopunt in beeld? (seconden)</span>
      <input type="number" name="punt_bereik_seconden" value="<?= e($i['punt_bereik_seconden'] ?? 12) ?>" min="2" max="600">
      <small class="hint">Het infopaneel verschijnt op het tijdstip van het punt en sluit daarna vanzelf, tenzij een bezoeker het aanraakt.</small>
      <?= veld_fout($fouten, 'punt_bereik_seconden') ?>
    </label>
    <label class="veld">
      <span>Terug naar het startscherm na (seconden zonder aanraking)</span>
      <input type="number" name="inactiviteit_seconden" value="<?= e($i['inactiviteit_seconden'] ?? 120) ?>" min="15" max="3600">
      <small class="hint">De teller loopt als de film stilstaat (gepauzeerd of afgelopen).</small>
      <?= veld_fout($fouten, 'inactiviteit_seconden') ?>
    </label>
  </section>

  <section class="kaartje">
    <h2>Colofon</h2>
    <label class="veld">
      <span>Makers van de applicatie</span>
      <input type="text" name="colofon_makers" value="<?= e($i['colofon_makers'] ?? '') ?>" maxlength="255" required>
      <small class="hint">Wordt getoond in de colofon-pop-up (het logo of de knop ‘Colofon’ op de website).</small>
      <?= veld_fout($fouten, 'colofon_makers') ?>
    </label>
  </section>

  <div class="knoppenrij knoppenrij--rechts">
    <button type="submit" class="knop">Opslaan</button>
  </div>
</form>
<?php admin_voet(); ?>
