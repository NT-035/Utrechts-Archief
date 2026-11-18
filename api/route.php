<?php
/**
 * GET api/route.php – routepunten van de boot (tijdstip + coördinaten), gesorteerd op tijd.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/helpers.php';

api(function (): array {
    $rijen = db_lezen()->query(
        'SELECT tijdstip_seconden, latitude, longitude FROM route_punten ORDER BY tijdstip_seconden, volgorde, id'
    )->fetchAll();

    return array_map(fn ($r) => [
        't'   => (int) $r['tijdstip_seconden'],
        'lat' => (float) $r['latitude'],
        'lng' => (float) $r['longitude'],
    ], $rijen);
});
