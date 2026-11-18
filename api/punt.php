<?php
/**
 * GET api/punt.php?id=… – één actief infopunt met al zijn bronnen.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/_bron.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    json_antwoord(['fout' => 'Geef een geldig id mee.'], 400);
}

api(function () use ($id): array {
    $stmt = db_lezen()->prepare('SELECT * FROM punten WHERE id = :id AND actief = 1');
    $stmt->execute(['id' => $id]);
    $punt = $stmt->fetch();
    if (!$punt) {
        json_antwoord(['fout' => 'Punt niet gevonden.'], 404);
    }

    $stmt = db_lezen()->prepare('SELECT * FROM bronnen WHERE punt_id = :id ORDER BY jaar IS NULL, jaar, id');
    $stmt->execute(['id' => $id]);

    return api_punt($punt) + ['bronnen' => array_map('api_bron', $stmt->fetchAll())];
});
