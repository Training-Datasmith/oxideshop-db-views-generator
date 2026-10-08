<?php

declare(strict_types=1);

namespace OxidEsales\DatabaseViewsGenerator\Tests\Support;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        OxNewSpy::reset();
    }
}
