<?php
/**
 * Beveiliging van het CMS (TO §6):
 * - veilige sessiecookies (HttpOnly, Secure op HTTPS, SameSite=Strict)
 * - session_regenerate_id() na inloggen
 * - automatische time-out
 * - beperking van mislukte inlogpogingen
 * - CSRF-tokens in alle formulieren
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function start_sessie(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $c = config()['sessie'];
    session_name($c['naam']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),   // lokaal (http) uit, op de server met HTTPS aan
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();

    // Automatische time-out na inactiviteit
    $nu = time();
    if (isset($_SESSION['laatste_activiteit']) && $nu - $_SESSION['laatste_activiteit'] > $c['timeout_seconden']) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['flash'] = ['tekst' => 'Je bent automatisch uitgelogd na een periode van inactiviteit.', 'type' => 'info'];
    }
    $_SESSION['laatste_activiteit'] = $nu;

    // Extra beveiligingsheaders voor het CMS
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
}

function huidige_gebruiker(): ?array
{
    start_sessie();
    return $_SESSION['gebruiker'] ?? null;
}

/** Gebruik bovenaan elke CMS-pagina: zonder inlog terug naar de loginpagina. */
function vereis_login(): array
{
    $gebruiker = huidige_gebruiker();
    if ($gebruiker === null) {
        header('Location: login.php');
        exit;
    }
    return $gebruiker;
}

/* ---------- Inloggen met pogingenbeperking ---------- */

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'onbekend'), 0, 45);
}

function is_geblokkeerd(string $email): bool
{
    $c    = config()['inloggen'];
    $stmt = db_schrijven()->prepare(
        'SELECT COUNT(*) FROM inlogpogingen
         WHERE (ip = :ip OR email = :email) AND tijdstip > (NOW() - INTERVAL :min MINUTE)'
    );
    $stmt->execute(['ip' => client_ip(), 'email' => $email, 'min' => $c['venster_minuten']]);
    return (int) $stmt->fetchColumn() >= $c['max_pogingen'];
}

function registreer_mislukte_poging(string $email): void
{
    db_schrijven()
        ->prepare('INSERT INTO inlogpogingen (ip, email, tijdstip) VALUES (:ip, :email, NOW())')
        ->execute(['ip' => client_ip(), 'email' => mb_substr($email, 0, 150)]);
}

/**
 * Probeer in te loggen. Geeft null bij succes, anders een foutmelding.
 */
function probeer_in_te_loggen(string $email, string $wachtwoord): ?string
{
    if (is_geblokkeerd($email)) {
        return 'Te veel mislukte pogingen. Probeer het over ' . config()['inloggen']['venster_minuten'] . ' minuten opnieuw.';
    }

    $stmt = db_schrijven()->prepare('SELECT id, naam, email, wachtwoord_hash FROM gebruikers WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $rij = $stmt->fetch();

    // Ook bij een onbekend e-mailadres een hash controleren, zodat de responstijd niets verraadt.
    $hash = $rij['wachtwoord_hash'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    if (!password_verify($wachtwoord, $hash) || !$rij) {
        registreer_mislukte_poging($email);
        return 'E-mailadres of wachtwoord klopt niet.';
    }

    if (password_needs_rehash($rij['wachtwoord_hash'], PASSWORD_DEFAULT)) {
        db_schrijven()->prepare('UPDATE gebruikers SET wachtwoord_hash = :h WHERE id = :id')
            ->execute(['h' => password_hash($wachtwoord, PASSWORD_DEFAULT), 'id' => $rij['id']]);
    }

    // Opruimen van oude pogingen voor dit adres
    db_schrijven()->prepare('DELETE FROM inlogpogingen WHERE email = :email')->execute(['email' => $email]);

    session_regenerate_id(true);
    $_SESSION['gebruiker'] = ['id' => (int) $rij['id'], 'naam' => $rij['naam'], 'email' => $rij['email']];
    $_SESSION['laatste_activiteit'] = time();
    return null;
}

function uitloggen(): void
{
    start_sessie();
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'],
        'httponly' => true, 'samesite' => 'Strict',
    ]);
    session_destroy();
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    start_sessie();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_veld(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Controleer het CSRF-token bij elke POST; stop bij een ongeldig token. */
function controleer_csrf(): void
{
    start_sessie();
    $token = (string) ($_POST['csrf'] ?? '');
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(400);
        exit('Ongeldig formulier (CSRF-controle mislukt). Ga terug en probeer het opnieuw.');
    }
}

/* ---------- Flash-meldingen ---------- */

function flash(string $tekst, string $type = 'succes'): void
{
    start_sessie();
    $_SESSION['flash'] = ['tekst' => $tekst, 'type' => $type];
}

function haal_flash(): ?array
{
    start_sessie();
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
