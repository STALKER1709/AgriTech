<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Images de produits
    |--------------------------------------------------------------------------
    |
    | Les images sont stockées sur un disque privé et servies par un contrôleur,
    | jamais par une URL de fichier directe. Ce choix supprime toute dépendance
    | au lien symbolique `storage:link`, dont le comportement sous Windows n'a
    | pas pu être vérifié — voir DECISIONS.md.
    |
    */

    'images' => [
        'disk' => 'local',
        'directory' => 'products',

        // Types réellement acceptés. La validation contrôle le type MIME réel
        // du fichier, pas l'extension du nom, qu'un attaquant choisit.
        'mimes' => ['jpeg', 'jpg', 'png', 'webp'],

        // En kilo-octets.
        'max_kilobytes' => 4096,

        'max_width' => 4000,
        'max_height' => 4000,

        'max_per_product' => 5,
    ],

];
