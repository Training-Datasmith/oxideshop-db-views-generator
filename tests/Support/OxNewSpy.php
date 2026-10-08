<?php

declare(strict_types=1);

namespace OxidEsales\DatabaseViewsGenerator\Tests\Support;

final class OxNewSpy
{
    /** @var callable|null */
    public static $updateViewsBehavior = null;

    /** @var list<string> */
    public static array $oxNewCalls = [];

    /** @var list<array<int, mixed>> */
    public static array $updateViewsInvocations = [];

    public static function reset(): void
    {
        self::$updateViewsBehavior = null;
        self::$oxNewCalls = [];
        self::$updateViewsInvocations = [];
    }
}
