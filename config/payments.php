<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Passerelle active
    |--------------------------------------------------------------------------
    |
    | AgriTech ne contacte aucun opérateur Mobile Money réel. La seule
    | implémentation fournie est `fake`, qui reproduit fidèlement le cycle d'un
    | paiement — initiation, décision, callback signé, vérification — sans
    | aucun service externe.
    |
    */

    'gateway' => env('PAYMENT_GATEWAY', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Signature des callbacks
    |--------------------------------------------------------------------------
    |
    | Clé HMAC dont la passerelle simulée signe ses callbacks, et que la route
    | webhook vérifie. Règle de gestion RG06 : un paiement ne devient
    | `succeeded` que sur une confirmation vérifiée côté serveur.
    |
    */

    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', ''),

    /*
    | Fenêtre d'acceptation de l'horodatage d'un callback, en secondes. Au-delà,
    | le callback est rejeté : c'est ce qui empêche de rejouer un ancien appel
    | capté puis renvoyé plus tard.
    */

    'webhook_tolerance_seconds' => 300,

    /*
    |--------------------------------------------------------------------------
    | Latence et expiration
    |--------------------------------------------------------------------------
    |
    | Délai avant l'envoi du callback, pour imiter le temps de réponse d'un
    | opérateur, et délai au-delà duquel un paiement sans réponse est expiré
    | par la tâche de réconciliation.
    |
    */

    'callback_delay_seconds' => (int) env('PAYMENT_CALLBACK_DELAY_SECONDS', 5),

    'expiration_minutes' => (int) env('PAYMENT_EXPIRATION_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Numéros de test à comportement forcé
    |--------------------------------------------------------------------------
    |
    | Quel que soit le bouton choisi sur la page de paiement simulée, ces
    | numéros imposent leur issue. Ils permettent de rejouer un échec ou une
    | expiration sans dépendre de la main de l'opérateur.
    |
    */

    'test_numbers' => [
        '+237670000000' => 'failed',
        '+237670000099' => 'expired',
    ],

];
