<?php

namespace App\Constants;

class SyrianGovernorates
{
    public const GOVERNORATES = [
        'Damascus',
        'Rif_Dimashq',
        'Aleppo',
        'Homs',
        'Hama',
        'Latakia',
        'Tartus',
        'Idlib',
        'Deir_ez_Zor',
        'Raqqa',
        'Hasakah',
        'Daraa',
        'As_Suwayda',
        'Quneitra',
    ];

    public static function all(): array
    {
        return self::GOVERNORATES;
    }
}
