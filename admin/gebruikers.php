<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

$ik = vereis_login();
$db = db_schrijven();
$fouten = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $actie = (string) ($_POST['actie'] ?? '');

    if ($actie === 'toevoegen') {
        $naam  = tekst('naam', 100, true, $fouten);
        $email = strtolower(tekst('email', 150, true, $fouten));
        $ww    = (string) ($_POST['wachtwoord'] ?? '');
        if (!isset($fouten['email']) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fouten['email'] = 'Vul een geldig e-mailadres in.';
        }
        if (strlen($ww) < 10 || strlen($ww) > 200) {
            $fouten['wachtwoord'] = 'Kies een wachtwoord van minimaal 10 tekens.';
        }
        if (!$fouten) {
            try {
                $db->prepare('INSERT INTO gebruikers (naam, email, wachtwoord_hash) VALUES (:n, :e, :h)')
                    ->execute(['n' => $naam, 'e' => $email, 'h' => password_hash($ww, PASSWORD_DEFAULT)]);
                flash("Beheerder $naam is toegevoegd.");
                stuur_door('gebruikers.php');
            } catch (PDOException $ex) {
                if ($ex->getCode() !== '23000') {
                    throw $ex;
                }
                $fouten['email'] = 'Er bestaat al een beheerder met dit e-mailadres.';
            }
        }
    }

    if ($actie === 'wachtwoord') {
        $oud = (string) ($_POST['huidig'] ?? '');
        $nieuw = (string) ($_POST['nieuw'] ?? '');
        $stmt = $db->prepare('SELECT wachtwoord_hash FROM gebruikers WHERE id = :id');
        $stmt->execute(['id' => $ik['id']]);
        if (!password_verify($oud, (string) $stmt->fetchColumn())) {
            $fouten['huidig'] = 'Het huidige wachtwoord klopt niet.';
        } elseif (strlen($nieuw) < 10 || strlen($nieuw) > 200) {
            $fouten['nieuw'] = 'Kies een wachtwoord van minimaal 10 tekens.';
        } else {
            $db->prepare('UPDATE gebruikers SET wachtwoord_hash = :h WHERE id = :id')
                ->execute(['h' => password_hash($nieuw, PASSWORD_DEFAULT), 'id' => $ik['id']]);
            session_regenerate_id(true);
            flash('Je wachtwoord is gewijzigd.');
            stuur_door('gebruikers.php');
        }
    }

    if ($actie === 'verwijderen') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id && $id !== $ik['id']) {
            $db->prepare('DELETE FROM gebruikers WHERE id = :id')->execute(['id' => $id]);
            flash('De beheerder is verwijderd.');
        }
        stuur_door('gebruikers.php');
    }
}

$gebruikers = $db->query('SELECT id, naam, email, aangemaakt_op FROM gebruikers ORDER BY naam')->fetchAll();

admin_kop('Beheerders', 'gebruikers');
?>
<h1>Beheerders</h1>
<p class="intro">Alleen beheerders kunnen inloggen in het CMS. Bezoekers van de website hebben geen account.</p>

<div class="twee-kolommen">
  <section class="kaartje">
    <h2>Alle beheerders</h2>
    <table class="tabel">
      <thead><tr><th>Naam</th><th>E-mail</th><th>Sinds</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($gebruikers as $g): ?>
        <tr>
          <td><?= e($g['naam']) ?><?= (int) $g['id'] === $ik['id'] ? ' <small>(jij)</small>' : '' ?></td>
          <td><?= e($g['email']) ?></td>
          <td class="klein"><?= e(date('d-m-Y', strtotime($g['aangemaakt_op']))) ?></td>
          <td>
            <?php if ((int) $g['id'] !== $ik['id']): ?>
            <form method="post" onsubmit="return confirm('Deze beheerder verwijderen?')">
              <?= csrf_veld() ?>
              <input type="hidden" name="actie" value="verwijderen">
              <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
              <button type="submit" class="knop knop--klein knop--gevaar">Verwijderen</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <div class="kolom">
    <section class="kaartje">
      <h2>Beheerder toevoegen</h2>
      <form method="post" class="formulier" novalidate>
        <?= csrf_veld() ?>
        <input type="hidden" name="actie" value="toevoegen">
        <label class="veld"><span>Naam</span><input type="text" name="naam" maxlength="100" required value="<?= e($_POST['naam'] ?? '') ?>"><?= veld_fout($fouten, 'naam') ?></label>
        <label class="veld"><span>E-mailadres</span><input type="email" name="email" maxlength="150" required value="<?= e($_POST['email'] ?? '') ?>"><?= veld_fout($fouten, 'email') ?></label>
        <label class="veld"><span>Wachtwoord (min. 10 tekens)</span><input type="password" name="wachtwoord" minlength="10" maxlength="200" autocomplete="new-password" required><?= veld_fout($fouten, 'wachtwoord') ?></label>
        <button type="submit" class="knop">Toevoegen</button>
      </form>
    </section>

    <section class="kaartje">
      <h2>Mijn wachtwoord wijzigen</h2>
      <form method="post" class="formulier" novalidate>
        <?= csrf_veld() ?>
        <input type="hidden" name="actie" value="wachtwoord">
        <label class="veld"><span>Huidig wachtwoord</span><input type="password" name="huidig" autocomplete="current-password" required><?= veld_fout($fouten, 'huidig') ?></label>
        <label class="veld"><span>Nieuw wachtwoord</span><input type="password" name="nieuw" minlength="10" maxlength="200" autocomplete="new-password" required><?= veld_fout($fouten, 'nieuw') ?></label>
        <button type="submit" class="knop">Wijzigen</button>
      </form>
    </section>
  </div>
</div>
<?php admin_voet(); ?>
