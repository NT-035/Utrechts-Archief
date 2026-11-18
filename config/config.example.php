<?php
/**
 * Configuratie – Ontmoet de Oudegracht
 *
 * Kopieer dit bestand naar config/config.php en vul de gegevens in.
 * Deze map staat buiten de publieke webmap (en is via .htaccess afgeschermd),
 * zodat databasegegevens nooit via de browser op te vragen zijn (TO §6).
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'naam'    => 'oudegracht',
        'charset' => 'utf8mb4',

        // Databasegebruiker met ALLEEN leesrechten (SELECT) voor de publieke API.
        'lezen' => [
            'gebruiker'  => 'hua_lezen',
            'wachtwoord' => 'vul-een-sterk-wachtwoord-in',
        ],

        // Databasegebruiker met schrijfrechten voor het CMS.
        'schrijven' => [
            'gebruiker'  => 'hua_schrijven',
            'wachtwoord' => 'vul-een-sterk-wachtwoord-in',
        ],
    ],

    // Sessie-instellingen voor het CMS.
    'sessie' => [
        'naam'             => 'hua_cms',
        'timeout_seconden' => 1800,  // automatisch uitloggen na 30 minuten inactiviteit
    ],

    // Beperking van mislukte inlogpogingen.
    'inloggen' => [
        'max_pogingen'   => 5,
        'venster_minuten' => 15,
    ],

    // Uploads van bronbestanden.
    'uploads' => [
        'max_bytes_afbeelding' => 10 * 1024 * 1024,   // 10 MB
        'max_bytes_document'   => 20 * 1024 * 1024,   // 20 MB
        'max_bytes_film'       => 200 * 1024 * 1024,  // 200 MB (let op upload_max_filesize in php.ini)
    ],

    // Zet op true tijdens ontwikkeling om foutmeldingen te zien.
    'debug' => false,
];
