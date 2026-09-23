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

    /*
    |--------------------------------------------------------------------------
    | Jours fériés supplémentaires (SLA)
    |--------------------------------------------------------------------------
    |
    | Dates Y-m-d ajoutées aux fériés fixes et chrétiens mobiles calculés
    | par SlaClock (Nouvel An, Travail, Nationale, Assomption, Toussaint,
    | Paix, Noël, lundi de Pâques, Ascension, lundi de Pentecôte).
    | À maintenir pour les fêtes musulmanes (Korité, Tabaski, Maouloud).
    |
    */

    'holidays' => [
        // 2026
        '2026-03-20', // Korité (Aïd el-Fitr)
        '2026-05-27', // Tabaski (Aïd el-Kebir)
        '2026-08-25', // Maouloud
        // 2027
        '2027-03-10', // Korité
        '2027-05-17', // Tabaski
        '2027-08-15', // Maouloud (même jour que l'Assomption — redondant OK)
    ],

];
