<?php
/**
 * Veilige verwerking van uploads (TO §6):
 * controle op MIME-type (via de inhoud, niet de bestandsnaam), extensie en grootte,
 * een nieuwe willekeurige bestandsnaam en opslag in /uploads (waar geen scripts draaien).
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

const UPLOAD_TYPES = [
    // mime                => [extensie, soort]
    'image/jpeg'           => ['jpg',  'afbeelding'],
    'image/png'            => ['png',  'afbeelding'],
    'image/webp'           => ['webp', 'afbeelding'],
    'image/gif'            => ['gif',  'afbeelding'],
    'application/pdf'      => ['pdf',  'document'],
    'video/mp4'            => ['mp4',  'film'],
    'video/webm'           => ['webm', 'film'],
];

const UPLOAD_EXTENSIES = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'mp4', 'webm'];

/**
 * Verwerk één geüpload bestand uit $_FILES[$veld].
 * Geeft ['pad' => 'uploads/…'] terug, of ['fout' => '…'].
 */
function verwerk_upload(string $veld): array
{
    $bestand = $_FILES[$veld] ?? null;
    if (!$bestand || ($bestand['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['fout' => 'Kies een bestand om te uploaden.'];
    }
    if ($bestand['error'] !== UPLOAD_ERR_OK) {
        return ['fout' => match ($bestand['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Het bestand is te groot voor de server.',
            UPLOAD_ERR_PARTIAL => 'Het bestand is maar half geüpload. Probeer het opnieuw.',
            default => 'Uploaden is mislukt.',
        }];
    }
    if (!is_uploaded_file($bestand['tmp_name'])) {
        return ['fout' => 'Ongeldige upload.'];
    }

    // 1. Extensie van de originele naam moet toegestaan zijn
    $ext = strtolower(pathinfo((string) $bestand['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_EXTENSIES, true)) {
        return ['fout' => 'Dit bestandstype is niet toegestaan. Toegestaan: ' . implode(', ', UPLOAD_EXTENSIES) . '.'];
    }

    // 2. Echte MIME-type bepalen aan de hand van de inhoud
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($bestand['tmp_name']) ?: '';
    if (!isset(UPLOAD_TYPES[$mime])) {
        return ['fout' => 'De inhoud van het bestand past niet bij een toegestaan type.'];
    }
    [$veiligeExt, $soort] = UPLOAD_TYPES[$mime];

    // Extensie en inhoud moeten bij elkaar passen
    $extNorm = $ext === 'jpeg' ? 'jpg' : $ext;
    if ($extNorm !== $veiligeExt) {
        return ['fout' => 'De extensie past niet bij de inhoud van het bestand.'];
    }

    // 3. Grootte
    $limieten = config()['uploads'];
    $max = match ($soort) {
        'afbeelding' => $limieten['max_bytes_afbeelding'],
        'document'   => $limieten['max_bytes_document'],
        'film'       => $limieten['max_bytes_film'],
    };
    if ($bestand['size'] > $max) {
        return ['fout' => sprintf('Het bestand is te groot (maximaal %d MB).', intdiv($max, 1024 * 1024))];
    }

    // 4. Afbeeldingen extra controleren
    if ($soort === 'afbeelding' && @getimagesize($bestand['tmp_name']) === false) {
        return ['fout' => 'Dit is geen geldige afbeelding.'];
    }

    // 5. Nieuwe, willekeurige bestandsnaam
    $map = __DIR__ . '/../uploads/' . date('Y');
    if (!is_dir($map) && !mkdir($map, 0755, true)) {
        return ['fout' => 'De uploadmap kon niet worden aangemaakt.'];
    }
    $naam = bin2hex(random_bytes(16)) . '.' . $veiligeExt;
    if (!move_uploaded_file($bestand['tmp_name'], "$map/$naam")) {
        return ['fout' => 'Het bestand kon niet worden opgeslagen.'];
    }
    @chmod("$map/$naam", 0644);

    return ['pad' => 'uploads/' . date('Y') . '/' . $naam];
}

/** Verwijder een eerder geüpload bestand (alleen binnen /uploads). */
function verwijder_upload(?string $pad): void
{
    if (!$pad || !str_starts_with($pad, 'uploads/') || str_contains($pad, '..')) {
        return;
    }
    $vol = realpath(__DIR__ . '/../' . $pad);
    $basis = realpath(__DIR__ . '/../uploads');
    if ($vol && $basis && str_starts_with($vol, $basis) && is_file($vol)) {
        @unlink($vol);
    }
}
