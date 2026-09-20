<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Contenus de formation
    |--------------------------------------------------------------------------
    |
    | Les vidéos et les PDF vivent sur le disque privé et sont servis par un
    | contrôleur qui vérifie l'accès (RG05), jamais par une URL de fichier
    | directe. Même principe que les images de produits : voir DECISIONS.md.
    |
    */

    'contents' => [
        'disk' => 'local',
        'directory' => 'trainings',

        // Types réellement acceptés. La validation contrôle le type MIME réel
        // du fichier, pas l'extension du nom, qu'un attaquant choisit.
        'mimes' => ['mp4', 'webm', 'pdf'],

        // En kilo-octets : 500 Mo pour une vidéo, 50 Mo pour un PDF.
        'max_kilobytes' => 512_000,

        'max_per_training' => 20,
    ],

];
