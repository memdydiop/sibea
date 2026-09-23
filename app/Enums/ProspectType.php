<?php

namespace App\Enums;

enum ProspectType: string
{
    case Particulier = 'particulier';
    case Entreprise = 'entreprise';

    public function label(): string
    {
        return match ($this) {
            self::Particulier => 'Particulier',
            self::Entreprise => 'Entreprise (B2B)',
        };
    }
}
