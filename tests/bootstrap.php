<?php

declare(strict_types=1);

use OxidEsales\DatabaseViewsGenerator\Tests\Support\OxNewSpy;
use OxidEsales\Eshop\Core\DbMetaDataHandler;

require dirname(__DIR__) . '/vendor/autoload.php';

if (!function_exists('oxNew')) {
    /**
     * @param class-string $class
     */
    function oxNew(string $class, mixed ...$args): object
    {
        OxNewSpy::$oxNewCalls[] = $class;

        if ($class === DbMetaDataHandler::class) {
            return new class {
                public function updateViews(?array $tables = null): bool
                {
                    OxNewSpy::$updateViewsInvocations[] = [$tables];

                    $behavior = OxNewSpy::$updateViewsBehavior;
                    if ($behavior === null) {
                        return true;
                    }

                    return $behavior($tables);
                }
            };
        }

        throw new RuntimeException('Unexpected oxNew class: ' . $class);
    }
}
