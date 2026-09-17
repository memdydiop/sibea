<?php

namespace App\Enums;

enum RequestType: string
{
    case Information = 'information';
    case Devis = 'devis';
    case Partenariat = 'partenariat';
    case Candidature = 'candidature';
    case Presse = 'presse';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Information => 'Demande d’information',
            self::Devis => 'Demande de devis',
            self::Partenariat => 'Partenariat',
            self::Candidature => 'Candidature',
            self::Presse => 'Presse',
            self::Autre => 'Autre',
        };
    }
}
