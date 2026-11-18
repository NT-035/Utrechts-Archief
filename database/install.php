<?php
/**
 * Installatiescript (alleen via de opdrachtregel).
 *
 * Voorbeelden:
 *   php database/install.php --root-wachtwoord=geheim --admin-email=beheer@example.nl --admin-wachtwoord=Sterk!Wachtwoord1
 *       Maakt database, tabellen, databasegebruikers, voorbeeldinhoud en een CMS-beheerder.
 *
 *   php database/install.php --admin-email=… --admin-wachtwoord=…
 *       Zonder root: gebruikt de 'schrijven'-gebruiker uit config.php (database en tabellen moeten al bestaan).
 *
 *   php database/install.php --sql > database/seed.sql
 *       Schrijft de voorbeeldinhoud als SQL weg (handig voor phpMyAdmin).
 *
 * Extra opties: --root-gebruiker=root  --geen-voorbeeld  (sla voorbeeldinhoud over)
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Alleen via de opdrachtregel.');
}

require __DIR__ . '/seed-data.php';

$opt = getopt('', ['root-gebruiker::', 'root-wachtwoord::', 'admin-email::', 'admin-naam::', 'admin-wachtwoord::', 'sql', 'geen-voorbeeld']);

/* ---------- Alleen SQL wegschrijven ---------- */
if (isset($opt['sql'])) {
    echo seed_als_sql();
    exit(0);
}

$configPad = __DIR__ . '/../config/config.php';
if (!is_file($configPad)) {
    fwrite(STDERR, "config/config.php ontbreekt. Kopieer config/config.example.php en vul het in.\n");
    exit(1);
}
$config = require $configPad;
$db = $config['db'];

function verbind(array $db, string $gebruiker, string $wachtwoord, bool $metDatabase): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $db['host'], $db['port'], $db['charset'])
        . ($metDatabase ? ';dbname=' . $db['naam'] : '');
    return new PDO($dsn, $gebruiker, $wachtwoord, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

try {
    if (isset($opt['root-wachtwoord'])) {
        $root = verbind($db, $opt['root-gebruiker'] ?? 'root', (string) $opt['root-wachtwoord'], false);
        echo "Verbonden als root.\n";

        // Schema uitvoeren (zonder de uitgecommentarieerde gebruikersregels)
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $schema = str_replace('oudegracht', $db['naam'], $schema);
        foreach (array_filter(array_map('trim', preg_split('/;\s*$/m', preg_replace('/^--.*$/m', '', $schema)))) as $sql) {
            $root->exec($sql);
        }
        echo "Database '{$db['naam']}' en tabellen aangemaakt.\n";

        // Databasegebruikers met minimale rechten
        $naam = $db['naam'];
        foreach (['lezen', 'schrijven'] as $rol) {
            $g = $db[$rol]['gebruiker'];
            if ($g === 'root') {
                continue;
            }
            $root->exec('CREATE USER IF NOT EXISTS ' . $root->quote($g) . "@'%' IDENTIFIED BY " . $root->quote($db[$rol]['wachtwoord']));
            $root->exec('ALTER USER ' . $root->quote($g) . "@'%' IDENTIFIED BY " . $root->quote($db[$rol]['wachtwoord']));
            if ($rol === 'lezen') {
                foreach (['punten', 'bronnen', 'route_punten', 'instellingen'] as $tabel) {
                    $root->exec("GRANT SELECT ON `$naam`.`$tabel` TO " . $root->quote($g) . "@'%'");
                }
            } else {
                $root->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON `$naam`.* TO " . $root->quote($g) . "@'%'");
            }
            echo "Databasegebruiker '$g' ($rol) ingesteld.\n";
        }
        $root->exec('FLUSH PRIVILEGES');
    }

    $pdo = verbind($db, $db['schrijven']['gebruiker'], $db['schrijven']['wachtwoord'], true);

    if (!isset($opt['geen-voorbeeld'])) {
        $aantal = (int) $pdo->query('SELECT COUNT(*) FROM punten')->fetchColumn();
        if ($aantal > 0) {
            echo "Er staan al $aantal punten in de database; voorbeeldinhoud overgeslagen.\n";
        } else {
            $pdo->exec(seed_als_sql());
            echo "Voorbeeldinhoud toegevoegd (30 punten, route en instellingen).\n";
        }
    }

    if (isset($opt['admin-email'], $opt['admin-wachtwoord'])) {
        $email = strtolower(trim((string) $opt['admin-email']));
        $ww = (string) $opt['admin-wachtwoord'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($ww) < 10) {
            throw new RuntimeException('Gebruik een geldig e-mailadres en een wachtwoord van minimaal 10 tekens.');
        }
        $stmt = $pdo->prepare(
            'INSERT INTO gebruikers (naam, email, wachtwoord_hash) VALUES (:n, :e, :h)
             ON DUPLICATE KEY UPDATE wachtwoord_hash = VALUES(wachtwoord_hash), naam = VALUES(naam)'
        );
        $stmt->execute(['n' => $opt['admin-naam'] ?? 'Beheerder', 'e' => $email, 'h' => password_hash($ww, PASSWORD_DEFAULT)]);
        echo "CMS-beheerder '$email' aangemaakt of bijgewerkt.\n";
    } else {
        echo "Tip: voeg --admin-email=… --admin-wachtwoord=… toe om een CMS-beheerder aan te maken.\n";
    }

    echo "Klaar.\n";
} catch (Throwable $t) {
    fwrite(STDERR, 'Fout: ' . $t->getMessage() . "\n");
    exit(1);
}

/* ---------- SQL voor de voorbeeldinhoud ---------- */
function seed_als_sql(): string
{
    $data = seed_opbouwen();
    $q = static fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string) $v) . "'");

    $sql = "-- Voorbeeldinhoud Ontmoet de Oudegracht (gegenereerd door database/install.php --sql)\n";
    $sql .= "-- Voer eerst schema.sql uit.\n\n";

    $sql .= "INSERT INTO instellingen (sleutel, waarde) VALUES\n";
    $rijen = [];
    foreach (SEED_INSTELLINGEN as $k => $v) {
        $rijen[] = '  (' . $q($k) . ', ' . $q($v) . ')';
    }
    $sql .= implode(",\n", $rijen) . "\nON DUPLICATE KEY UPDATE waarde = waarde;\n\n";

    $sql .= "INSERT INTO route_punten (tijdstip_seconden, latitude, longitude, volgorde) VALUES\n";
    $sql .= implode(",\n", array_map(fn ($r) => "  ({$r['tijdstip_seconden']}, {$r['latitude']}, {$r['longitude']}, {$r['volgorde']})", $data['route'])) . ";\n\n";

    $sql .= "INSERT INTO punten (titel, tijdstip_seconden, latitude, longitude, volgorde, korte_tekst, uitleg, wist_je_dat, actief) VALUES\n";
    $sql .= implode(",\n", array_map(fn ($p) => '  (' . implode(', ', [
        $q($p['titel']), $p['tijdstip_seconden'], $p['latitude'], $p['longitude'], $p['volgorde'],
        $q($p['korte_tekst']), $q($p['uitleg']), $q($p['wist_je_dat']), $p['actief'],
    ]) . ')', $data['punten'])) . ";\n\n";

    foreach ($data['bronnen'] as $b) {
        $sql .= 'INSERT INTO bronnen (punt_id, type, titel, bestandspad, bijschrift, bronvermelding, jaar) '
            . 'SELECT id, ' . implode(', ', [$q($b['type']), $q($b['titel']), $q($b['bestandspad']), $q($b['bijschrift']), $q($b['bronvermelding']), $b['jaar']])
            . ' FROM punten WHERE volgorde = ' . $b['punt_volgorde'] . " ORDER BY id LIMIT 1;\n";
    }

    return $sql;
}
