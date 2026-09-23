<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Adresse de notification des prospects
    |--------------------------------------------------------------------------
    |
    | Boîte partagée alertée à chaque nouveau prospect non assigné
    | (formulaire contact, création admin). Un prospect assigné notifie
    | son commercial ensuite, pas cette adresse.
    |
    */

    'notification_email' => env('LEADS_NOTIFICATION_EMAIL', 'contact@sibea.ci'),

    /*
    |--------------------------------------------------------------------------
    | Assignation automatique des prospects
    |--------------------------------------------------------------------------
    |
    | Si vrai, un nouveau prospect issu du site est assigné au commercial
    | le moins chargé (permission manage_leads). Sinon, il reste non
    | assigné et seule la boîte partagée est notifiée.
    |
    */

    'auto_assign' => env('LEADS_AUTO_ASSIGN', true),

    /*
    |--------------------------------------------------------------------------
    | Mode d'assignation automatique
    |--------------------------------------------------------------------------
    |
    | round_robin : rotation stricte A → B → C → A (équitable).
    | least_loaded : commercial avec le moins de dossiers ouverts.
    |
    */

    'assign_mode' => env('LEADS_ASSIGN_MODE', 'round_robin'),

];
