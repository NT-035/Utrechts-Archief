<?php
/** Gedeelde opmaak van een bron voor de API. */
declare(strict_types=1);

function api_bron(array $b): array
{
    return [
        'id'             => (int) $b['id'],
        'punt_id'        => (int) $b['punt_id'],
        'type'           => $b['type'],
        'titel'          => $b['titel'],
        'bestand'        => $b['bestandspad'],
        'bijschrift'     => $b['bijschrift'],
        'bronvermelding' => $b['bronvermelding'],
        'jaar'           => $b['jaar'] !== null ? (int) $b['jaar'] : null,
    ];
}

function api_punt(array $p): array
{
    return [
        'id'          => (int) $p['id'],
        'titel'       => $p['titel'],
        'tijdstip'    => (int) $p['tijdstip_seconden'],
        'lat'         => (float) $p['latitude'],
        'lng'         => (float) $p['longitude'],
        'volgorde'    => (int) $p['volgorde'],
        'korte_tekst' => $p['korte_tekst'],
        'uitleg'      => $p['uitleg'],
        'wist_je_dat' => $p['wist_je_dat'],
    ];
}
