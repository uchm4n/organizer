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
}
