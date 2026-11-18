<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

if (huidige_gebruiker()) {
    stuur_door('index.php');
}

$fout = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $wachtwoord = (string) ($_POST['wachtwoord'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $wachtwoord === '' || mb_strlen($email) > 150 || strlen($wachtwoord) > 200) {
        $fout = 'Vul een geldig e-mailadres en wachtwoord in.';
    } else {
        try {
            $fout = probeer_in_te_loggen($email, $wachtwoord);
        } catch (Throwable $t) {
            error_log('[oudegracht-cms] ' . $t->getMessage());
            $fout = 'Er ging iets mis met de database. Probeer het later opnieuw.';
        }
        if ($fout === null) {
            stuur_door('index.php');
        }
    }
}

admin_kop('Inloggen');
?>
<section class="login">
  <p class="login__jaar">1929</p>
  <h1>Ontmoet de Oudegracht</h1>
  <p class="login__sub">Beheer van infopunten, bronnen en route</p>

  <?php if ($fout): ?>
    <div class="melding melding--fout" role="alert"><?= e($fout) ?></div>
  <?php endif; ?>

  <form method="post" class="formulier" novalidate>
    <?= csrf_veld() ?>
    <label class="veld">
      <span>E-mailadres</span>
      <input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required maxlength="150" autofocus>
    </label>
    <label class="veld">
      <span>Wachtwoord</span>
      <input type="password" name="wachtwoord" autocomplete="current-password" required maxlength="200">
    </label>
    <button type="submit" class="knop knop--breed">Inloggen</button>
  </form>
</section>
<?php admin_voet(); ?>
