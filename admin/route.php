<?php
/**
 * Routebeheer: de tijdstip-coördinaatparen waarmee de boot over de kaart vaart (TO §4, §8).
 */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

vereis_login();
$db = db_schrijven();
$fout = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $ruw = json_decode((string) ($_POST['route'] ?? ''), true);

    $rijen = [];
    if (!is_array($ruw) || count($ruw) > 2000) {
        $fout = 'De route kon niet worden gelezen.';
    } else {
        foreach ($ruw as $i => $r) {
            $t   = filter_var($r['t'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 86400]]);
            $lat = filter_var($r['lat'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($r['lng'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($t === false || $lat === false || $lng === false || abs($lat) > 90 || abs($lng) > 180) {
                $fout = 'Routepunt ' . ($i + 1) . ' heeft een ongeldig tijdstip of ongeldige coördinaten.';
                break;
            }
            $rijen[] = ['t' => $t, 'lat' => round($lat, 6), 'lng' => round($lng, 6)];
        }
    }

    if (!$fout) {
        usort($rijen, fn ($a, $b) => $a['t'] <=> $b['t']);
        $db->beginTransaction();
        try {
            $db->exec('DELETE FROM route_punten');
            $stmt = $db->prepare('INSERT INTO route_punten (tijdstip_seconden, latitude, longitude, volgorde) VALUES (:t, :lat, :lng, :v)');
            foreach ($rijen as $i => $r) {
                $stmt->execute($r + ['v' => $i + 1]);
            }
            $db->commit();
        } catch (Throwable $t) {
            $db->rollBack();
            throw $t;
        }
        flash('De route is opgeslagen (' . count($rijen) . ' punten).');
        stuur_door('route.php');
    }
}

$route = $db->query('SELECT tijdstip_seconden AS t, latitude AS lat, longitude AS lng FROM route_punten ORDER BY tijdstip_seconden, volgorde')->fetchAll();
$route = array_map(fn ($r) => ['t' => (int) $r['t'], 'lat' => (float) $r['lat'], 'lng' => (float) $r['lng']], $route);
$punten = $db->query('SELECT titel, tijdstip_seconden AS t, latitude AS lat, longitude AS lng FROM punten WHERE actief = 1 ORDER BY tijdstip_seconden')->fetchAll();

admin_kop('Route', 'route', ['leaflet']);
?>
<div class="paginakop">
  <div>
    <h1>Route van de boot</h1>
    <p class="intro">De boot vaart in een rechte lijn van routepunt naar routepunt. Hoe meer punten in de bochten, hoe nauwkeuriger hij de gracht volgt.</p>
  </div>
</div>

<?php if ($fout): ?>
  <div class="melding melding--fout" role="alert"><?= e($fout) ?></div>
<?php endif; ?>

<div class="route-editor">
  <section class="kaartje">
    <h2>Kaart</h2>
    <p class="hint"><strong>Tik op de kaart</strong> om een routepunt toe te voegen. <strong>Sleep</strong> een punt om het te verplaatsen. De oranje stippen zijn de infopunten.</p>
    <div class="cms-kaart cms-kaart--groot" id="route-kaart"
         data-route="<?= e(json_encode($route)) ?>"
         data-punten="<?= e(json_encode($punten)) ?>"></div>
  </section>

  <section class="kaartje">
    <h2>Routepunten (<span id="route-aantal"><?= count($route) ?></span>)</h2>
    <div class="knoppenrij">
      <button type="button" class="knop knop--klein knop--licht" id="route-verdeel">Tijden gelijkmatig verdelen</button>
      <label class="veld veld--inline"><span>Duur video (s)</span><input type="number" id="route-duur" min="1" value="<?= $route ? max(1, $route[count($route) - 1]['t']) : 600 ?>"></label>
    </div>
    <p class="hint">‘Gelijkmatig verdelen’ zet de tijden naar verhouding van de afstand: handig als de boot met een constante snelheid vaart. Controleer daarna een paar punten met de video.</p>
    <div class="tabel-wrap tabel-wrap--hoog">
      <table class="tabel tabel--compact">
        <thead><tr><th>#</th><th>Tijd (s)</th><th>Latitude</th><th>Longitude</th><th></th></tr></thead>
        <tbody id="route-rijen"></tbody>
      </table>
    </div>
    <form method="post" id="route-formulier">
      <?= csrf_veld() ?>
      <input type="hidden" name="route" id="route-json">
      <div class="knoppenrij knoppenrij--rechts">
        <a class="knop knop--licht" href="route.php">Wijzigingen ongedaan maken</a>
        <button type="submit" class="knop">Route opslaan</button>
      </div>
    </form>
  </section>
</div>

<?php admin_voet(['../public/vendor/leaflet/leaflet.js', 'js/route.js']); ?>
