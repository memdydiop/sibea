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

];
