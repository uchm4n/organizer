<?php

namespace App\Actions\Item;

use App\Models\Item;

class RestoreItemAction
{
    public function handle(Item $item): void
    {
        if ($item->trashed()) {
            $item->restore();
        }
    }
}
