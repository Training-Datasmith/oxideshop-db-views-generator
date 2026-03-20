<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Database_Views_Generator;

use Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler;
class Views_Generator
{
    /**
     * @return bool Was the call successful?
     */
    public function generate()
    {
        return ox_new(Db_Meta_Data_Handler::class)->update_views();
    }
}