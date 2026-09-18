<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Livre = 'livre';
    case EnCours = 'en_cours';
    case EnDeveloppement = 'en_developpement';

    public function label(): string
    {
        return match ($this) {
            self::Livre => 'Livré',
            self::EnCours => 'En cours',
            self::EnDeveloppement => 'En développement',
        };
    }
}
