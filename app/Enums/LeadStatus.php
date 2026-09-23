<?php

namespace App\Enums;

enum LeadStatus: string
{
    case Nouveau = 'nouveau';
    case Qualification = 'qualification';
    case Rdv = 'rdv';
    case Etude = 'etude';
    case Proposition = 'proposition';
    case Negociation = 'negociation';
    case Gagne = 'gagne';
    case Perdu = 'perdu';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::Nouveau => 'Nouveau',
            self::Qualification => 'Qualification',
            self::Rdv => 'RDV',
            self::Etude => 'Étude',
            self::Proposition => 'Proposition',
            self::Negociation => 'Négociation',
            self::Gagne => 'Gagné',
            self::Perdu => 'Perdu',
            self::Archive => 'Archivé',
        };
    }

    public function isWon(): bool
    {
        return $this === self::Gagne;
    }

    public function isLost(): bool
    {
        return $this === self::Perdu || $this === self::Archive;
    }

    public function isOpen(): bool
    {
        return ! $this->isWon() && ! $this->isLost();
    }
}
