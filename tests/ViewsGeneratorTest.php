<?php

declare(strict_types=1);

namespace OxidEsales\DatabaseViewsGenerator\Tests;

use OxidEsales\DatabaseViewsGenerator\Tests\Support\OxNewSpy;
use OxidEsales\DatabaseViewsGenerator\Tests\Support\TestCase;
use OxidEsales\DatabaseViewsGenerator\ViewsGenerator;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use RuntimeException;

final class ViewsGeneratorTest extends TestCase
{
    public function testGenerateReturnsTrueAndCallsUpdateViewsWithNoArguments(): void
    {
        OxNewSpy::$updateViewsBehavior = static fn (?array $tables): bool => true;

        $generator = new ViewsGenerator();

        self::assertTrue($generator->generate());
        self::assertSame([DbMetaDataHandler::class], OxNewSpy::$oxNewCalls);
        self::assertSame([[null]], OxNewSpy::$updateViewsInvocations);
    }

    public function testGenerateReturnsFalseWhenUpdateViewsReturnsFalse(): void
    {
        OxNewSpy::$updateViewsBehavior = static fn (?array $tables): bool => false;

        $generator = new ViewsGenerator();

        self::assertFalse($generator->generate());
    }

    public function testGeneratePropagatesUpdateViewsException(): void
    {
        OxNewSpy::$updateViewsBehavior = static function (?array $tables): bool {
            throw new RuntimeException('view generation failed');
        };

        $generator = new ViewsGenerator();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('view generation failed');

        $generator->generate();
    }
}
