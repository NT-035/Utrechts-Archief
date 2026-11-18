<?php
/**
 * Gedeelde opmaak voor het CMS.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

function admin_kop(string $titel, string $actief = '', array $extra = []): void
{
    $gebruiker = huidige_gebruiker();
    $menu = [
        'index'        => ['index.php', 'Dashboard'],
        'punten'       => ['punten.php', 'Infopunten'],
        'route'        => ['route.php', 'Route'],
        'instellingen' => ['instellingen.php', 'Instellingen'],
        'gebruikers'   => ['gebruikers.php', 'Beheerders'],
    ];
    $flash = haal_flash();
    ?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($titel) ?> – CMS Ontmoet de Oudegracht</title>
  <link rel="icon" href="../public/img/favicon.svg" type="image/svg+xml">
  <?php if (in_array('leaflet', $extra, true)): ?>
  <link rel="stylesheet" href="../public/vendor/leaflet/leaflet.css">
  <?php endif; ?>
  <link rel="stylesheet" href="css/admin.css">
</head>
<body class="<?= $gebruiker ? '' : 'is-login' ?>">
<?php if ($gebruiker): ?>
<header class="balk">
  <a class="balk__merk" href="index.php"><span>1929</span> Ontmoet de Oudegracht <small>CMS</small></a>
  <nav class="balk__menu" aria-label="Hoofdmenu">
    <?php foreach ($menu as $sleutel => [$url, $label]): ?>
      <a href="<?= e($url) ?>"<?= $sleutel === $actief ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="balk__gebruiker">
    <a href="../public/" target="_blank" rel="noopener">Bekijk website ↗</a>
    <span><?= e($gebruiker['naam']) ?></span>
    <form method="post" action="logout.php">
      <?= csrf_veld() ?>
      <button type="submit" class="knop knop--klein knop--licht">Uitloggen</button>
    </form>
  </div>
</header>
<?php endif; ?>
<main class="inhoud">
  <?php if ($flash): ?>
    <div class="melding melding--<?= e($flash['type']) ?>" role="status"><?= e($flash['tekst']) ?></div>
  <?php endif; ?>
<?php
}

function admin_voet(array $scripts = []): void
{
    ?>
</main>
<?php foreach ($scripts as $src): ?>
<script src="<?= e($src) ?>"<?= str_ends_with($src, '.mjs') ? ' type="module"' : '' ?>></script>
<?php endforeach; ?>
</body>
</html>
<?php
}

/** Toon een validatiefout onder een veld. */
function veld_fout(array $fouten, string $naam): string
{
    return isset($fouten[$naam]) ? '<p class="veld__fout">' . e($fouten[$naam]) . '</p>' : '';
}

/** Alleen POST toestaan en CSRF controleren. */
function vereis_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Methode niet toegestaan.');
    }
    controleer_csrf();
}

function stuur_door(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}
