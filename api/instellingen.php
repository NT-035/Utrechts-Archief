<?php
/**
 * GET api/instellingen.php – de publieke instellingen voor de frontend.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/helpers.php';

const PUBLIEKE_INSTELLINGEN = [
    'video_bestand', 'video_duur_seconden', 'inactiviteit_seconden', 'punt_bereik_seconden', 'achteruit_snelheid', 'colofon_makers',
];

api(function (): array {
    $rijen = db_lezen()->query('SELECT sleutel, waarde FROM instellingen')->fetchAll(PDO::FETCH_KEY_PAIR);
    $uit = [];
    foreach (PUBLIEKE_INSTELLINGEN as $k) {
        if (isset($rijen[$k])) {
            $uit[$k] = is_numeric($rijen[$k]) ? $rijen[$k] + 0 : $rijen[$k];
        }
    }
    return $uit;
});
