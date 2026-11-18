<?php
/**
 * Infopunt toevoegen of bewerken, inclusief de bronnen bij dat punt.
 */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../includes/upload.php';

vereis_login();
$db = db_schrijven();

const BRON_TYPES = ['foto', 'kaart', 'film', 'document'];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$punt = [
    'titel' => '', 'tijdstip_seconden' => 0, 'latitude' => '', 'longitude' => '', 'volgorde' => 0,
    'korte_tekst' => '', 'uitleg' => '', 'wist_je_dat' => '', 'actief' => 1,
];
if ($id) {
    $stmt = $db->prepare('SELECT * FROM punten WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $punt = $stmt->fetch() ?: null;
    if (!$punt) {
        flash('Dit punt bestaat niet (meer).', 'fout');
        stuur_door('punten.php');
    }
}

$fouten = [];
$bronFouten = [];

/* ---------- Opslaan ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $actie = (string) ($_POST['actie'] ?? 'opslaan');

    if ($actie === 'opslaan') {
        $data = [
            'titel'             => tekst('titel', 150, true, $fouten),
            'tijdstip_seconden' => geheel_getal('tijdstip_seconden', 0, 86400, $fouten),
            'latitude'          => coordinaat('latitude', -90, 90, $fouten),
            'longitude'         => coordinaat('longitude', -180, 180, $fouten),
            'volgorde'          => geheel_getal('volgorde', 0, 10000, $fouten, 0),
            'korte_tekst'       => tekst('korte_tekst', 300, true, $fouten),
            'uitleg'            => tekst('uitleg', 5000, false, $fouten),
            'wist_je_dat'       => tekst('wist_je_dat', 300, false, $fouten),
            'actief'            => isset($_POST['actief']) ? 1 : 0,
        ];

        if (!$fouten) {
            if ($id) {
                $db->prepare(
                    'UPDATE punten SET titel = :titel, tijdstip_seconden = :tijdstip_seconden, latitude = :latitude,
                     longitude = :longitude, volgorde = :volgorde, korte_tekst = :korte_tekst, uitleg = :uitleg,
                     wist_je_dat = :wist_je_dat, actief = :actief WHERE id = :id'
                )->execute($data + ['id' => $id]);
                flash('Het punt is opgeslagen.');
            } else {
                $db->prepare(
                    'INSERT INTO punten (titel, tijdstip_seconden, latitude, longitude, volgorde, korte_tekst, uitleg, wist_je_dat, actief)
                     VALUES (:titel, :tijdstip_seconden, :latitude, :longitude, :volgorde, :korte_tekst, :uitleg, :wist_je_dat, :actief)'
                )->execute($data);
                $id = (int) $db->lastInsertId();
                flash('Het punt is toegevoegd. Je kunt nu bronnen toevoegen.');
            }
            stuur_door('punt.php?id=' . $id);
        }
        $punt = array_merge($punt, $data, array_intersect_key($_POST, ['latitude' => 1, 'longitude' => 1]));
    }

    if ($id && $actie === 'bron_toevoegen') {
        $bron = [
            'type'           => keuze('type', BRON_TYPES, $bronFouten),
            'titel'          => tekst('bron_titel', 150, true, $bronFouten),
            'bijschrift'     => tekst('bijschrift', 300, false, $bronFouten),
            'bronvermelding' => tekst('bronvermelding', 200, false, $bronFouten),
            'jaar'           => ($_POST['jaar'] ?? '') === '' ? null : geheel_getal('jaar', 1000, 2100, $bronFouten),
        ];
        if (!$bronFouten) {
            $upload = verwerk_upload('bestand');
            if (isset($upload['fout'])) {
                $bronFouten['bestand'] = $upload['fout'];
            } else {
                $db->prepare(
                    'INSERT INTO bronnen (punt_id, type, titel, bestandspad, bijschrift, bronvermelding, jaar)
                     VALUES (:punt_id, :type, :titel, :pad, :bijschrift, :bronvermelding, :jaar)'
                )->execute($bron + ['punt_id' => $id, 'pad' => $upload['pad']]);
                flash('De bron is toegevoegd.');
                stuur_door('punt.php?id=' . $id . '#bronnen');
            }
        }
    }

    if ($id && $actie === 'bron_verwijderen') {
        $bronId = filter_input(INPUT_POST, 'bron_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $stmt = $db->prepare('SELECT bestandspad FROM bronnen WHERE id = :b AND punt_id = :p');
        $stmt->execute(['b' => $bronId, 'p' => $id]);
        $pad = $stmt->fetchColumn();
        if ($pad !== false) {
            $db->prepare('DELETE FROM bronnen WHERE id = :b AND punt_id = :p')->execute(['b' => $bronId, 'p' => $id]);
            verwijder_upload($pad);
            flash('De bron is verwijderd.');
        }
        stuur_door('punt.php?id=' . $id . '#bronnen');
    }

    if ($id && $actie === 'bron_bijwerken') {
        $bronId = filter_input(INPUT_POST, 'bron_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $f = [];
        $upd = [
            'type'           => keuze('type', BRON_TYPES, $f),
            'titel'          => tekst('bron_titel', 150, true, $f),
            'bijschrift'     => tekst('bijschrift', 300, false, $f),
            'bronvermelding' => tekst('bronvermelding', 200, false, $f),
            'jaar'           => ($_POST['jaar'] ?? '') === '' ? null : geheel_getal('jaar', 1000, 2100, $f),
        ];
        if ($f || !$bronId) {
            flash('De bron is niet opgeslagen: ' . implode(' ', array_unique($f)), 'fout');
        } else {
            $db->prepare(
                'UPDATE bronnen SET type = :type, titel = :titel, bijschrift = :bijschrift, bronvermelding = :bronvermelding, jaar = :jaar
                 WHERE id = :b AND punt_id = :p'
            )->execute($upd + ['b' => $bronId, 'p' => $id]);
            flash('De bron is bijgewerkt.');
        }
        stuur_door('punt.php?id=' . $id . '#bronnen');
    }
}

$bronnen = [];
if ($id) {
    $stmt = $db->prepare('SELECT * FROM bronnen WHERE punt_id = :id ORDER BY jaar IS NULL, jaar, id');
    $stmt->execute(['id' => $id]);
    $bronnen = $stmt->fetchAll();
}

$instellingen = $db->query('SELECT sleutel, waarde FROM instellingen')->fetchAll(PDO::FETCH_KEY_PAIR);
$videoPad = (string) ($instellingen['video_bestand'] ?? '');
$videoBestaat = $videoPad !== '' && is_file(__DIR__ . '/../' . $videoPad);

$route = $db->query('SELECT latitude AS lat, longitude AS lng FROM route_punten ORDER BY tijdstip_seconden, volgorde')->fetchAll();
$anderePunten = $db->prepare('SELECT id, titel, latitude AS lat, longitude AS lng FROM punten WHERE id <> :id');
$anderePunten->execute(['id' => $id ?? 0]);

admin_kop($id ? 'Punt bewerken' : 'Nieuw punt', 'punten', ['leaflet']);
?>
<p class="kruimel"><a href="punten.php">← Alle infopunten</a></p>
<h1><?= $id ? 'Punt bewerken: ' . e($punt['titel']) : 'Nieuw infopunt' ?></h1>

<?php if ($fouten): ?>
  <div class="melding melding--fout" role="alert">Controleer de gemarkeerde velden.</div>
<?php endif; ?>

<form method="post" class="formulier formulier--raster" novalidate>
  <?= csrf_veld() ?>
  <input type="hidden" name="actie" value="opslaan">

  <div class="kolom">
    <section class="kaartje">
      <h2>Inhoud</h2>
      <label class="veld">
        <span>Titel <em>*</em></span>
        <input type="text" name="titel" value="<?= e($punt['titel']) ?>" maxlength="150" required>
        <?= veld_fout($fouten, 'titel') ?>
      </label>
      <label class="veld">
        <span>Korte tekst voor kinderen <em>*</em> <small data-teller="korte_tekst">max. 300 tekens</small></span>
        <textarea name="korte_tekst" id="korte_tekst" rows="3" maxlength="300" required><?= e($punt['korte_tekst']) ?></textarea>
        <small class="hint">Eén of twee korte zinnen. Spreek de kijker aan: "Kijk eens naar…"</small>
        <?= veld_fout($fouten, 'korte_tekst') ?>
      </label>
      <label class="veld">
        <span>Wist je dat…? <small data-teller="wist_je_dat">max. 300 tekens</small></span>
        <input type="text" name="wist_je_dat" id="wist_je_dat" value="<?= e($punt['wist_je_dat']) ?>" maxlength="300">
        <?= veld_fout($fouten, 'wist_je_dat') ?>
      </label>
      <label class="veld">
        <span>Uitleg (achter ‘Meer weten’)</span>
        <textarea name="uitleg" rows="6" maxlength="5000"><?= e($punt['uitleg']) ?></textarea>
        <?= veld_fout($fouten, 'uitleg') ?>
      </label>
      <label class="veld veld--vink">
        <input type="checkbox" name="actief" value="1" <?= $punt['actief'] ? 'checked' : '' ?>>
        <span>Zichtbaar op de website</span>
      </label>
    </section>
  </div>

  <div class="kolom">
    <section class="kaartje">
      <h2>Moment in de video</h2>
      <?php if ($videoBestaat): ?>
        <video id="voorbeeld-video" class="voorbeeld-video" src="../<?= e($videoPad) ?>" controls muted preload="metadata"></video>
        <button type="button" class="knop knop--licht knop--klein" id="neem-tijd">Gebruik huidige videotijd</button>
      <?php else: ?>
        <p class="hint">Plaats de video (zie Instellingen) om het tijdstip hier rechtstreeks uit de film te kiezen.</p>
      <?php endif; ?>
      <div class="veld-rij">
        <label class="veld">
          <span>Tijdstip (seconden) <em>*</em></span>
          <input type="number" name="tijdstip_seconden" id="tijdstip" min="0" step="1" value="<?= (int) $punt['tijdstip_seconden'] ?>" required>
          <small class="hint" id="tijdstip-label"><?= e(tijd_label((int) $punt['tijdstip_seconden'])) ?></small>
          <?= veld_fout($fouten, 'tijdstip_seconden') ?>
        </label>
        <label class="veld">
          <span>Volgorde bij gelijke tijd</span>
          <input type="number" name="volgorde" min="0" step="1" value="<?= (int) $punt['volgorde'] ?>">
        </label>
      </div>
    </section>

    <section class="kaartje">
      <h2>Plek op de kaart</h2>
      <p class="hint">Tik op de kaart om de plek te kiezen, of sleep de markering.</p>
      <div class="cms-kaart" id="punt-kaart"
           data-route="<?= e(json_encode($route)) ?>"
           data-anderen="<?= e(json_encode($anderePunten->fetchAll())) ?>"></div>
      <div class="veld-rij">
        <label class="veld">
          <span>Latitude <em>*</em></span>
          <input type="text" inputmode="decimal" name="latitude" id="lat" value="<?= e($punt['latitude']) ?>" required>
          <?= veld_fout($fouten, 'latitude') ?>
        </label>
        <label class="veld">
          <span>Longitude <em>*</em></span>
          <input type="text" inputmode="decimal" name="longitude" id="lng" value="<?= e($punt['longitude']) ?>" required>
          <?= veld_fout($fouten, 'longitude') ?>
        </label>
      </div>
    </section>

    <div class="knoppenrij knoppenrij--rechts">
      <a class="knop knop--licht" href="punten.php">Annuleren</a>
      <button type="submit" class="knop"><?= $id ? 'Opslaan' : 'Punt toevoegen' ?></button>
    </div>
  </div>
</form>

<?php if ($id): ?>
<section class="kaartje" id="bronnen">
  <h2>Bronnen bij dit punt (<?= count($bronnen) ?>)</h2>

  <?php if ($bronnen): ?>
  <div class="bronnen">
    <?php foreach ($bronnen as $b): $isAfb = preg_match('/\.(jpe?g|png|webp|gif|svg)$/i', $b['bestandspad']); ?>
    <form method="post" class="bron">
      <?= csrf_veld() ?>
      <input type="hidden" name="bron_id" value="<?= (int) $b['id'] ?>">
      <a class="bron__voorbeeld" href="../<?= e($b['bestandspad']) ?>" target="_blank" rel="noopener">
        <?php if ($isAfb): ?>
          <img src="../<?= e($b['bestandspad']) ?>" alt="" loading="lazy">
        <?php else: ?>
          <span><?= e(strtoupper(pathinfo($b['bestandspad'], PATHINFO_EXTENSION))) ?></span>
        <?php endif; ?>
      </a>
      <div class="bron__velden">
        <div class="veld-rij">
          <label class="veld"><span>Titel</span><input type="text" name="bron_titel" value="<?= e($b['titel']) ?>" maxlength="150" required></label>
          <label class="veld veld--smal"><span>Type</span>
            <select name="type">
              <?php foreach (BRON_TYPES as $t): ?><option value="<?= $t ?>" <?= $b['type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option><?php endforeach; ?>
            </select>
          </label>
          <label class="veld veld--smal"><span>Jaar</span><input type="number" name="jaar" value="<?= e($b['jaar']) ?>" min="1000" max="2100"></label>
        </div>
        <label class="veld"><span>Bijschrift</span><input type="text" name="bijschrift" value="<?= e($b['bijschrift']) ?>" maxlength="300"></label>
        <label class="veld"><span>Bronvermelding</span><input type="text" name="bronvermelding" value="<?= e($b['bronvermelding']) ?>" maxlength="200"></label>
        <div class="knoppenrij">
          <button type="submit" name="actie" value="bron_bijwerken" class="knop knop--klein">Opslaan</button>
          <button type="submit" name="actie" value="bron_verwijderen" class="knop knop--klein knop--gevaar" onclick="return confirm('Deze bron verwijderen?')">Verwijderen</button>
        </div>
      </div>
    </form>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p class="leeg">Nog geen bronnen bij dit punt.</p>
  <?php endif; ?>

  <h3>Bron toevoegen</h3>
  <?php if ($bronFouten): ?>
    <div class="melding melding--fout" role="alert"><?= e(implode(' ', array_unique($bronFouten))) ?></div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="formulier bron-nieuw">
    <?= csrf_veld() ?>
    <input type="hidden" name="actie" value="bron_toevoegen">
    <div class="veld-rij">
      <label class="veld"><span>Titel <em>*</em></span><input type="text" name="bron_titel" maxlength="150" required value="<?= e($_POST['bron_titel'] ?? '') ?>"></label>
      <label class="veld veld--smal"><span>Type</span>
        <select name="type"><?php foreach (BRON_TYPES as $t): ?><option value="<?= $t ?>"><?= ucfirst($t) ?></option><?php endforeach; ?></select>
      </label>
      <label class="veld veld--smal"><span>Jaar</span><input type="number" name="jaar" min="1000" max="2100" value="<?= e($_POST['jaar'] ?? '') ?>"></label>
    </div>
    <label class="veld"><span>Bijschrift</span><input type="text" name="bijschrift" maxlength="300" value="<?= e($_POST['bijschrift'] ?? '') ?>"></label>
    <label class="veld"><span>Bronvermelding</span><input type="text" name="bronvermelding" maxlength="200" placeholder="Bijv. Het Utrechts Archief, catalogusnummer …" value="<?= e($_POST['bronvermelding'] ?? '') ?>"></label>
    <label class="veld">
      <span>Bestand <em>*</em></span>
      <input type="file" name="bestand" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.mp4,.webm" required>
      <small class="hint">Foto/kaart: JPG, PNG, WEBP of GIF (max. 10 MB) · Document: PDF (max. 20 MB) · Film: MP4 of WEBM (max. 200 MB)</small>
    </label>
    <button type="submit" class="knop">Bron uploaden</button>
  </form>
</section>
<?php endif; ?>

<?php admin_voet(['../public/vendor/leaflet/leaflet.js', 'js/punt.js']); ?>
