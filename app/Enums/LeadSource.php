<?php

namespace App\Enums;

enum LeadSource: string
{
    case Site = 'site';
    case Telephone = 'telephone';
    case Email = 'email';
    case ReseauxSociaux = 'reseaux_sociaux';
    case Recommandation = 'recommandation';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Site => 'Site web',
            self::Telephone => 'Téléphone',
            self::Email => 'Email',
            self::ReseauxSociaux => 'Réseaux sociaux',
            self::Recommandation => 'Recommandation',
            self::Autre => 'Autre',
        };
    }
}
