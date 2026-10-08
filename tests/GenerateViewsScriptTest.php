<?php

declare(strict_types=1);

namespace OxidEsales\DatabaseViewsGenerator\Tests;

use OxidEsales\DatabaseViewsGenerator\Tests\Support\BootstrapFixture;
use OxidEsales\DatabaseViewsGenerator\Tests\Support\ScriptRunner;
use OxidEsales\DatabaseViewsGenerator\Tests\Support\TestCase;

final class GenerateViewsScriptTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempRoot = sys_get_temp_dir() . '/oxviews-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        BootstrapFixture::removeTree($this->tempRoot);
        parent::tearDown();
    }

    private const GUIDANCE = 'Unable to find eShop bootstrap.php file. You can override the path by using ESHOP_BOOTSTRAP_PATH environment variable. ' . "\n";

    /**
     * @param array{exitCode: int|null, stdout: string, stderr: string} $result
     */
    private function assertEmptyStderr(array $result): void
    {
        self::assertSame('', $result['stderr']);
    }

    public function testSuccessfulRunExitsZeroWithEmptyStdout(): void
    {
        $fixture = $this->tempRoot . '/success';
        $bootstrap = BootstrapFixture::write($fixture . '/boot', 'ok', BootstrapFixture::MODE_TRUE);
        $script = BootstrapFixture::copyGenerateViewsScript($fixture . '/script');

        $result = ScriptRunner::run(
            $script,
            $fixture,
            ['ESHOP_BOOTSTRAP_PATH' => $bootstrap['path']]
        );

        self::assertSame(0, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        $this->assertEmptyStderr($result);
        self::assertFileExists($bootstrap['resultFile']);
        $payload = json_decode((string) file_get_contents($bootstrap['resultFile']), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OxidEsales\Eshop\Core\DbMetaDataHandler', $payload['class']);
        self::assertNull($payload['tables']);
    }

    public function testFalseResultExitsTwoWithDatabaseGuidance(): void
    {
        $fixture = $this->tempRoot . '/false-result';
        $bootstrap = BootstrapFixture::write($fixture . '/boot', 'no', BootstrapFixture::MODE_FALSE);
        $script = BootstrapFixture::copyGenerateViewsScript($fixture . '/script');

        $result = ScriptRunner::run(
            $script,
            $fixture,
            ['ESHOP_BOOTSTRAP_PATH' => $bootstrap['path']]
        );

        self::assertSame(2, $result['exitCode']);
        self::assertSame(
            'There was an error while regenerating the views. Please double check the state of database and configuration.' . "\n",
            $result['stdout']
        );
        $this->assertEmptyStderr($result);
    }

    public function testThrownResultPrintsLogGuidance(): void
    {
        $fixture = $this->tempRoot . '/throw';
        $bootstrap = BootstrapFixture::write($fixture . '/boot', 'throw', BootstrapFixture::MODE_THROW);
        $script = BootstrapFixture::copyGenerateViewsScript($fixture . '/script');

        $result = ScriptRunner::run(
            $script,
            $fixture,
            ['ESHOP_BOOTSTRAP_PATH' => $bootstrap['path']]
        );

        self::assertSame(
            'There was an error while regenerating the views. Please look at `oxideshop.log` for more details.' . "\n",
            $result['stdout']
        );
        self::assertStringContainsString('view generation failed', $result['stderr']);
    }

    public function testEnvPathOverridesWalkedBootstrap(): void
    {
        $fixture = $this->tempRoot . '/env-override';
        $scriptDir = $fixture . '/vendor/oxid-esales/oxideshop-db-views-generator';
        mkdir($scriptDir, 0777, true);
        $walkBootstrapDir = $fixture . '/source';
        BootstrapFixture::write($walkBootstrapDir, 'from-walk', BootstrapFixture::MODE_TRUE);
        $envBootstrap = BootstrapFixture::write($fixture . '/env-boot', 'from-env', BootstrapFixture::MODE_TRUE);
        $script = BootstrapFixture::copyGenerateViewsScript($scriptDir);

        $result = ScriptRunner::run(
            $script,
            $fixture,
            ['ESHOP_BOOTSTRAP_PATH' => $envBootstrap['path']]
        );

        self::assertSame(0, $result['exitCode']);
        $this->assertEmptyStderr($result);
        $payload = json_decode((string) file_get_contents($envBootstrap['resultFile']), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('from-env', $payload['token']);
    }

    public function testRelativeEnvPathResolvesFromWorkingDirectory(): void
    {
        $fixture = $this->tempRoot . '/relative-env';
        $bootstrap = BootstrapFixture::write($fixture . '/source', 'rel', BootstrapFixture::MODE_TRUE);
        $script = BootstrapFixture::copyGenerateViewsScript($fixture . '/script');

        $result = ScriptRunner::run(
            $script,
            $fixture,
            ['ESHOP_BOOTSTRAP_PATH' => 'source/bootstrap.php']
        );

        self::assertSame(0, $result['exitCode']);
        $this->assertEmptyStderr($result);
        self::assertFileExists($bootstrap['resultFile']);
    }

    public function testPaddedEnvPathIsTrimmed(): void
    {
        $fixture = $this->tempRoot . '/padded';
        $bootstrap = BootstrapFixture::write($fixture . '/boot', 'pad', BootstrapFixture::MODE_TRUE);
        $script = BootstrapFixture::copyGenerateViewsScript($fixture . '/script');

        $result = ScriptRunner::run(
            $script,
            $fixture,
            ['ESHOP_BOOTSTRAP_PATH' => '  ' . $bootstrap['path'] . '  ']
        );

        self::assertSame(0, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        $this->assertEmptyStderr($result);
        self::assertFileExists($bootstrap['resultFile']);
        $payload = json_decode((string) file_get_contents($bootstrap['resultFile']), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('pad', $payload['token']);
        self::assertSame('OxidEsales\Eshop\Core\DbMetaDataHandler', $payload['class']);
    }

    public function testMissingEnvPathPrintsBootstrapGuidance(): void
    {
        $fixture = $this->tempRoot . '/missing-env';
        $script = BootstrapFixture::copyGenerateViewsScript($fixture . '/script');

        $result = ScriptRunner::run(
            $script,
            $fixture,
            ['ESHOP_BOOTSTRAP_PATH' => $fixture . '/does-not-exist/bootstrap.php']
        );

        self::assertSame(self::GUIDANCE, $result['stdout']);
        $this->assertEmptyStderr($result);
    }

    public function testWhitespaceOnlyEnvPathPrintsBootstrapGuidance(): void
    {
        $fixture = $this->tempRoot . '/ws-env';
        $script = BootstrapFixture::copyGenerateViewsScript($fixture . '/script');
        $missing = $fixture . '/missing/bootstrap.php';

        $result = ScriptRunner::run(
            $script,
            $fixture,
            ['ESHOP_BOOTSTRAP_PATH' => '  ' . $missing . '  ']
        );

        self::assertSame(self::GUIDANCE, $result['stdout']);
        $this->assertEmptyStderr($result);
    }

    public function testDirectoryEnvPathPrintsBootstrapGuidance(): void
    {
        $fixture = $this->tempRoot . '/dir-env';
        mkdir($fixture . '/bootdir', 0777, true);
        $script = BootstrapFixture::copyGenerateViewsScript($fixture . '/script');

        $result = ScriptRunner::run(
            $script,
            $fixture,
            ['ESHOP_BOOTSTRAP_PATH' => $fixture . '/bootdir']
        );

        self::assertSame(self::GUIDANCE, $result['stdout']);
        $this->assertEmptyStderr($result);
    }

    public function testEmptyEnvVarUsesDirectoryWalk(): void
    {
        $fixture = $this->tempRoot . '/empty-env-walk';
        $scriptDir = $fixture . '/vendor/oxid-esales/oxideshop-db-views-generator';
        mkdir($scriptDir, 0777, true);
        BootstrapFixture::write($fixture . '/source', 'walk-token', BootstrapFixture::MODE_TRUE);
        $script = BootstrapFixture::copyGenerateViewsScript($scriptDir);

        $result = ScriptRunner::run(
            $script,
            $fixture,
            ['ESHOP_BOOTSTRAP_PATH' => '']
        );

        self::assertSame(0, $result['exitCode']);
        $this->assertEmptyStderr($result);
        $resultFile = $fixture . '/source/oxnew-result.json';
        self::assertFileExists($resultFile);
        $payload = json_decode((string) file_get_contents($resultFile), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('walk-token', $payload['token']);
    }

    public function testWalkFindsVendorDepthBootstrap(): void
    {
        $fixture = $this->tempRoot . '/vendor-depth';
        $scriptDir = $fixture . '/vendor/oxid-esales/oxideshop-db-views-generator';
        mkdir($scriptDir, 0777, true);
        BootstrapFixture::write($fixture . '/source', 'vendor-depth', BootstrapFixture::MODE_TRUE);
        $script = BootstrapFixture::copyGenerateViewsScript($scriptDir);

        $result = ScriptRunner::run($script, $fixture, []);

        self::assertSame(0, $result['exitCode']);
        $this->assertEmptyStderr($result);
        $payload = json_decode(
            (string) file_get_contents($fixture . '/source/oxnew-result.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('vendor-depth', $payload['token']);
    }

    public function testWalkFindsFifthProbe(): void
    {
        $fixture = $this->tempRoot . '/fifth-probe';
        $scriptDir = $fixture . '/a/b/c/d/vendor/oxid-esales/oxideshop-db-views-generator';
        mkdir($scriptDir, 0777, true);
        BootstrapFixture::write($fixture . '/a/b/c/source', 'fifth', BootstrapFixture::MODE_TRUE);
        $script = BootstrapFixture::copyGenerateViewsScript($scriptDir);

        $result = ScriptRunner::run($script, $fixture, []);

        self::assertSame(0, $result['exitCode']);
        $this->assertEmptyStderr($result);
        $payload = json_decode(
            (string) file_get_contents($fixture . '/a/b/c/source/oxnew-result.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('fifth', $payload['token']);
    }

    public function testWalkStopsAfterFifthProbe(): void
    {
        $fixture = $this->tempRoot . '/sixth-miss';
        $scriptDir = $fixture . '/a/b/c/d/e/vendor/oxid-esales/oxideshop-db-views-generator';
        mkdir($scriptDir, 0777, true);
        BootstrapFixture::write($fixture . '/source', 'too-deep', BootstrapFixture::MODE_TRUE);
        $script = BootstrapFixture::copyGenerateViewsScript($scriptDir);

        $result = ScriptRunner::run($script, $fixture, []);

        self::assertSame(self::GUIDANCE, $result['stdout']);
        $this->assertEmptyStderr($result);
        self::assertFileExists($fixture . '/source/bootstrap.php');
    }

    public function testWalkPrefersNearerBootstrap(): void
    {
        $fixture = $this->tempRoot . '/nearer';
        $scriptDir = $fixture . '/vendor/oxid-esales/oxideshop-db-views-generator';
        mkdir($scriptDir, 0777, true);
        BootstrapFixture::write($scriptDir . '/source', 'near', BootstrapFixture::MODE_TRUE);
        BootstrapFixture::write($fixture . '/source', 'far', BootstrapFixture::MODE_TRUE);
        $script = BootstrapFixture::copyGenerateViewsScript($scriptDir);

        $result = ScriptRunner::run($script, $fixture, []);

        self::assertSame(0, $result['exitCode']);
        $this->assertEmptyStderr($result);
        $payload = json_decode(
            (string) file_get_contents($scriptDir . '/source/oxnew-result.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('near', $payload['token']);
    }

    public function testFailedWalkDoesNotLoadWorkingDirectoryBootstrap(): void
    {
        $fixture = $this->tempRoot . '/no-cwd-fallback';
        $levels = $fixture . '/l1/l2/l3/l4/l5';
        $scriptDir = $levels . '/vendor/oxid-esales/oxideshop-db-views-generator';
        mkdir($scriptDir, 0777, true);
        BootstrapFixture::write($fixture . '/source', 'from-cwd', BootstrapFixture::MODE_TRUE);
        $script = BootstrapFixture::copyGenerateViewsScript($scriptDir);

        $result = ScriptRunner::run($script, $levels, []);

        self::assertSame(self::GUIDANCE, $result['stdout']);
        $this->assertEmptyStderr($result);
        self::assertFileExists($fixture . '/source/bootstrap.php');
    }

    public function testBinEntryPointRunsFromAnotherWorkingDirectory(): void
    {
        $fixture = $this->tempRoot . '/bin-cwd';
        $bootstrap = BootstrapFixture::write($fixture . '/boot', 'bin', BootstrapFixture::MODE_TRUE);
        $bin = dirname(__DIR__) . '/oe-eshop-db_views_generate';
        $emptyCwd = $fixture . '/empty-cwd';
        mkdir($emptyCwd, 0777, true);

        $result = ScriptRunner::run(
            $bin,
            $emptyCwd,
            ['ESHOP_BOOTSTRAP_PATH' => $bootstrap['path']]
        );

        self::assertSame(0, $result['exitCode']);
        $this->assertEmptyStderr($result);
        $payload = json_decode((string) file_get_contents($bootstrap['resultFile']), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OxidEsales\Eshop\Core\DbMetaDataHandler', $payload['class']);
    }
}
