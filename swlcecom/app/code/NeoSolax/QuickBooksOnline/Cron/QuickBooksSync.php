<?php

namespace NeoSolax\QuickBooksOnline\Cron;

use Magenest\QuickBooksOnline\Model\Synchronization\Item;

class QuickBooksSync
{
    public function __construct(
        Item $item
    )
    {
        $this->item = $item;
    }

    public function execute()
    {
        $this->item->UpdateInventoryItemStock();
//        $this->item->UpdateNonInventoryItemStock();
    }
}
