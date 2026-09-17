<?php

namespace App\Enums;

enum LeadStatus: string
{
    case Nouveau = 'nouveau';
    case Contacte = 'contacte';
    case EnCours = 'en_cours';
    case Qualifie = 'qualifie';
    case Converti = 'converti';
    case NonQualifie = 'non_qualifie';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::Nouveau => 'Nouveau',
            self::Contacte => 'Contacté',
            self::EnCours => 'En cours',
            self::Qualifie => 'Qualifié',
            self::Converti => 'Converti',
            self::NonQualifie => 'Non qualifié',
            self::Archive => 'Archivé',
        };
    }
}
