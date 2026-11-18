<?php
/**
 * Algemene hulpfuncties: escapen, JSON-antwoorden en validatie.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (config()['debug'] ?? false) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

/** Escapen van uitvoer tegen XSS (TO §6). */
function e(mixed $waarde): string
{
    return htmlspecialchars((string) ($waarde ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Stuur een JSON-antwoord en stop. */
function json_antwoord(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');   // wijzigingen uit het CMS zijn direct zichtbaar
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    exit;
}

/** Voer een API-handler uit en vang databasefouten netjes af. */
function api(callable $handler): never
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_antwoord(['fout' => 'Alleen GET is toegestaan.'], 405);
    }
    try {
        json_antwoord($handler());
    } catch (Throwable $t) {
        error_log('[oudegracht-api] ' . $t->getMessage());
        json_antwoord(['fout' => 'De gegevens konden niet worden geladen.'], 500);
    }
}

/* ---------- Validatie (serverkant, TO §6) ---------- */

function tekst(string $naam, int $max, bool $verplicht = true, array &$fouten = []): string
{
    $waarde = trim((string) ($_POST[$naam] ?? ''));
    if ($verplicht && $waarde === '') {
        $fouten[$naam] = 'Dit veld is verplicht.';
    } elseif (mb_strlen($waarde) > $max) {
        $fouten[$naam] = "Maximaal $max tekens.";
    }
    return $waarde;
}

function geheel_getal(string $naam, int $min, int $max, array &$fouten = [], ?int $standaard = null): int
{
    $ruw = $_POST[$naam] ?? '';
    $waarde = filter_var($ruw, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
    if ($waarde === false) {
        if ($standaard !== null && $ruw === '') {
            return $standaard;
        }
        $fouten[$naam] = "Vul een heel getal in tussen $min en $max.";
        return $min;
    }
    return $waarde;
}

function coordinaat(string $naam, float $min, float $max, array &$fouten = []): float
{
    $ruw    = str_replace(',', '.', (string) ($_POST[$naam] ?? ''));
    $waarde = filter_var($ruw, FILTER_VALIDATE_FLOAT);
    if ($waarde === false || $waarde < $min || $waarde > $max) {
        $fouten[$naam] = 'Ongeldige coördinaat.';
        return 0.0;
    }
    return round($waarde, 6);
}

function keuze(string $naam, array $toegestaan, array &$fouten = []): string
{
    $waarde = (string) ($_POST[$naam] ?? '');
    if (!in_array($waarde, $toegestaan, true)) {
        $fouten[$naam] = 'Ongeldige keuze.';
        return $toegestaan[0];
    }
    return $waarde;
}

/** Zet seconden om naar m:ss. */
function tijd_label(int $seconden): string
{
    return sprintf('%d:%02d', intdiv($seconden, 60), $seconden % 60);
}
