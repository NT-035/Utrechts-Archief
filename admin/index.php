<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$gebruiker = vereis_login();
$db = db_schrijven();

$aantalPunten  = (int) $db->query('SELECT COUNT(*) FROM punten')->fetchColumn();
$aantalActief  = (int) $db->query('SELECT COUNT(*) FROM punten WHERE actief = 1')->fetchColumn();
$aantalBronnen = (int) $db->query('SELECT COUNT(*) FROM bronnen')->fetchColumn();
$aantalRoute   = (int) $db->query('SELECT COUNT(*) FROM route_punten')->fetchColumn();
$zonderBron    = (int) $db->query('SELECT COUNT(*) FROM punten p WHERE p.actief = 1 AND NOT EXISTS (SELECT 1 FROM bronnen b WHERE b.punt_id = p.id)')->fetchColumn();
$instellingen  = $db->query('SELECT sleutel, waarde FROM instellingen')->fetchAll(PDO::FETCH_KEY_PAIR);

$videoPad = (string) ($instellingen['video_bestand'] ?? '');
$videoBestaat = $videoPad !== '' && is_file(__DIR__ . '/../' . $videoPad);

$aandachtspunten = [];
if (!$videoBestaat) {
    $aandachtspunten[] = 'Het videobestand <code>' . e($videoPad ?: '(niet ingesteld)') . '</code> is niet gevonden. De website draait in demomodus. Zet de film in de map <code>media/</code> of pas het pad aan bij <a href="instellingen.php">Instellingen</a>.';
}
if ($aantalActief < 30) {
    $aandachtspunten[] = "Er zijn $aantalActief actieve infopunten. De briefing vraagt om minimaal 30.";
}
if ($aantalRoute < 2) {
    $aandachtspunten[] = 'Er is nog geen route ingevoerd. De boot vaart nu rechtstreeks van punt naar punt. <a href="route.php">Route invoeren</a>.';
}
if ($zonderBron > 0) {
    $aandachtspunten[] = "$zonderBron actieve punten hebben nog geen bronmateriaal.";
}

admin_kop('Dashboard', 'index');
?>
<h1>Goedendag, <?= e($gebruiker['naam']) ?></h1>
<p class="intro">Hier beheer je de inhoud van de website. Wijzigingen zijn direct zichtbaar op het digibord.</p>

<div class="tegels">
  <a class="tegel" href="punten.php"><strong><?= $aantalActief ?></strong><span>actieve infopunten<?= $aantalPunten !== $aantalActief ? ' (' . $aantalPunten . ' totaal)' : '' ?></span></a>
  <a class="tegel" href="punten.php"><strong><?= $aantalBronnen ?></strong><span>bronnen</span></a>
  <a class="tegel" href="route.php"><strong><?= $aantalRoute ?></strong><span>routepunten</span></a>
  <a class="tegel" href="instellingen.php"><strong><?= $videoBestaat ? 'OK' : 'Demo' ?></strong><span>video</span></a>
</div>

<?php if ($aandachtspunten): ?>
<section class="kaartje">
  <h2>Aandachtspunten</h2>
  <ul class="lijst-aandacht">
    <?php foreach ($aandachtspunten as $punt): ?>
      <li><?= $punt /* bevat alleen geëscapete waarden */ ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section class="kaartje">
  <h2>Snel aan de slag</h2>
  <div class="knoppenrij">
    <a class="knop" href="punt.php">+ Nieuw infopunt</a>
    <a class="knop knop--licht" href="route.php">Route van de boot bewerken</a>
    <a class="knop knop--licht" href="../public/" target="_blank" rel="noopener">Website bekijken ↗</a>
  </div>
</section>
<?php admin_voet(); ?>
