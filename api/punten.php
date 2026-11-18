<?php
/**
 * GET api/punten.php – alle actieve infopunten, gesorteerd op tijdstip,
 * inclusief het aantal bronnen per punt.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/_bron.php';

api(function (): array {
    $rijen = db_lezen()->query(
        'SELECT p.*, (SELECT COUNT(*) FROM bronnen b WHERE b.punt_id = p.id) AS aantal_bronnen
         FROM punten p
         WHERE p.actief = 1
         ORDER BY p.tijdstip_seconden, p.volgorde, p.id'
    )->fetchAll();

    return array_map(fn ($p) => api_punt($p) + ['aantal_bronnen' => (int) $p['aantal_bronnen']], $rijen);
});
