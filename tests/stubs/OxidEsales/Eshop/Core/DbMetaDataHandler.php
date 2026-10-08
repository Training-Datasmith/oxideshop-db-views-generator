<?php

declare(strict_types=1);

namespace OxidEsales\Eshop\Core;

/**
 * Test stub so ViewsGenerator can reference DbMetaDataHandler::class without oxideshop-ce.
 */
class DbMetaDataHandler
{
    public function updateViews(?array $tables = null): bool
    {
        return true;
    }
}
