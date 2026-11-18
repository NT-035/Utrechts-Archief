<?php
/**
 * Databaseverbinding via PDO (TO §3 en §6).
 *
 * - db_lezen():     gebruikt de databasegebruiker met alleen SELECT-rechten (publieke API)
 * - db_schrijven(): gebruikt de databasegebruiker met schrijfrechten (CMS)
 *
 * Alle queries in het project gebruiken prepared statements.
 */

declare(strict_types=1);

function config(): array
{
    static $config = null;
    if ($config === null) {
        $pad = __DIR__ . '/../config/config.php';
        if (!is_file($pad)) {
            http_response_code(500);
            exit('Configuratie ontbreekt: kopieer config/config.example.php naar config/config.php.');
        }
        $config = require $pad;
    }
    return $config;
}

function maak_pdo(string $rol): PDO
{
    $c   = config()['db'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['naam'], $c['charset']);

    return new PDO($dsn, $c[$rol]['gebruiker'], $c[$rol]['wachtwoord'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

function db_lezen(): PDO
{
    static $pdo = null;
    return $pdo ??= maak_pdo('lezen');
}

function db_schrijven(): PDO
{
    static $pdo = null;
    return $pdo ??= maak_pdo('schrijven');
}
