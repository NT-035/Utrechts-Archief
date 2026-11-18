<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

vereis_login();
$db = db_schrijven();

/* ---------- Acties (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $actie = (string) ($_POST['actie'] ?? '');

    if ($id && $actie === 'wissel_actief') {
        $db->prepare('UPDATE punten SET actief = NOT actief WHERE id = :id')->execute(['id' => $id]);
        flash('De zichtbaarheid van het punt is aangepast.');
    } elseif ($id && $actie === 'verwijder') {
        // Bestanden van de bronnen opruimen; de rijen verdwijnen via ON DELETE CASCADE.
        $stmt = $db->prepare('SELECT bestandspad FROM bronnen WHERE punt_id = :id');
        $stmt->execute(['id' => $id]);
        $paden = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $db->prepare('DELETE FROM punten WHERE id = :id')->execute(['id' => $id]);
        require_once __DIR__ . '/../includes/upload.php';
        foreach ($paden as $pad) {
            verwijder_upload($pad);
        }
        flash('Het punt en de bijbehorende bronnen zijn verwijderd.');
    }
    stuur_door('punten.php');
}

$punten = $db->query(
    'SELECT p.id, p.titel, p.tijdstip_seconden, p.korte_tekst, p.actief,
            (SELECT COUNT(*) FROM bronnen b WHERE b.punt_id = p.id) AS aantal_bronnen
     FROM punten p ORDER BY p.tijdstip_seconden, p.volgorde, p.id'
)->fetchAll();

admin_kop('Infopunten', 'punten');
?>
<div class="paginakop">
  <div>
    <h1>Infopunten</h1>
    <p class="intro"><?= count($punten) ?> punten, gesorteerd op het moment in de video. Het nummer op de kaart volgt deze volgorde.</p>
  </div>
  <a class="knop" href="punt.php">+ Nieuw infopunt</a>
</div>

<?php if (!$punten): ?>
  <p class="leeg">Nog geen infopunten. <a href="punt.php">Voeg het eerste punt toe.</a></p>
<?php else: ?>
<div class="tabel-wrap">
<table class="tabel">
  <thead>
    <tr><th>Nr.</th><th>Tijd</th><th>Titel</th><th>Bronnen</th><th>Zichtbaar</th><th><span class="sr">Acties</span></th></tr>
  </thead>
  <tbody>
  <?php $nr = 0; foreach ($punten as $p): $nr += $p['actief'] ? 1 : 0; ?>
    <tr class="<?= $p['actief'] ? '' : 'is-uit' ?>">
      <td><span class="nummer"><?= $p['actief'] ? $nr : '–' ?></span></td>
      <td class="mono"><?= e(tijd_label((int) $p['tijdstip_seconden'])) ?></td>
      <td>
        <a href="punt.php?id=<?= (int) $p['id'] ?>"><strong><?= e($p['titel']) ?></strong></a>
        <div class="klein"><?= e(mb_strimwidth($p['korte_tekst'], 0, 90, '…')) ?></div>
      </td>
      <td><?= (int) $p['aantal_bronnen'] ?></td>
      <td>
        <form method="post">
          <?= csrf_veld() ?>
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="actie" value="wissel_actief">
          <button type="submit" class="schakel <?= $p['actief'] ? 'is-aan' : '' ?>" aria-label="<?= $p['actief'] ? 'Verbergen' : 'Tonen' ?>">
            <?= $p['actief'] ? 'Aan' : 'Uit' ?>
          </button>
        </form>
      </td>
      <td class="acties">
        <a class="knop knop--klein knop--licht" href="punt.php?id=<?= (int) $p['id'] ?>">Bewerken</a>
        <form method="post" onsubmit="return confirm('Weet je zeker dat je dit punt en alle bronnen ervan wilt verwijderen?')">
          <?= csrf_veld() ?>
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="actie" value="verwijder">
          <button type="submit" class="knop knop--klein knop--gevaar">Verwijderen</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php admin_voet(); ?>
