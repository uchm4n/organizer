<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemType: int
{
    case Note        = 1;
    case Todo        = 2;
    case Spreadsheet = 3;
    case TaxFiling   = 4;
    case Event       = 5;
    case Document    = 6;
    case Custom      = 99;

    /**
     * Human-readable label suitable for item titles.
     */
    public function label(): string
    {
        return match ($this) {
            self::Note        => self::Note->name,
            self::Todo        => self::Todo->name,
            self::Spreadsheet => self::Spreadsheet->name,
            self::TaxFiling   => self::TaxFiling->name,
            self::Event       => self::Event->name,
            self::Document    => self::Document->name,
            self::Custom      => self::Custom->name,
        };
    }
}
