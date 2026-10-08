<?php

declare(strict_types=1);

namespace OxidEsales\DatabaseViewsGenerator\Tests\Support;

final class BootstrapFixture
{
    public const MODE_TRUE = 'true';
    public const MODE_FALSE = 'false';
    public const MODE_THROW = 'throw';

    /**
     * @return array{path: string, resultFile: string, controlFile: string, token: string}
     */
    public static function write(
        string $directory,
        string $token,
        string $mode = self::MODE_TRUE,
        ?string $srcRoot = null
    ): array {
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Cannot create directory: ' . $directory);
        }

        $resultFile = $directory . '/oxnew-result.json';
        $controlFile = $directory . '/bootstrap-control.txt';
        $path = $directory . '/bootstrap.php';
        $srcRoot = $srcRoot ?? dirname(__DIR__, 2);

        file_put_contents($controlFile, $mode . "\n" . $token);

        $bootstrapBody = <<<'PHP'
<?php

declare(strict_types=1);

$repoRoot = __REPO_ROOT__;

$control = file_get_contents(__DIR__ . '/bootstrap-control.txt');
[$mode, $token] = array_pad(explode("\n", $control, 2), 2, '');
$resultFile = __DIR__ . '/oxnew-result.json';

spl_autoload_register(function (string $class) use ($repoRoot): void {
    $srcRoot = $repoRoot;
    $prefix = 'OxidEsales\\DatabaseViewsGenerator\\';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file = $srcRoot . '/src/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

if (!function_exists('oxNew')) {
    function oxNew(string $class, mixed ...$args): object
    {
        if ($class === 'OxidEsales\Eshop\Core\DbMetaDataHandler') {
            return new class {
                public function updateViews(?array $tables = null): bool
                {
                    $control = file_get_contents(__DIR__ . '/bootstrap-control.txt');
                    [$mode, $token] = array_pad(explode("\n", $control, 2), 2, '');
                    $resultFile = __DIR__ . '/oxnew-result.json';

                    file_put_contents(
                        $resultFile,
                        json_encode(
                            [
                                'class' => 'OxidEsales\Eshop\Core\DbMetaDataHandler',
                                'tables' => $tables,
                                'token' => trim($token),
                            ],
                            JSON_THROW_ON_ERROR
                        )
                    );

                    if ($mode === 'throw') {
                        throw new RuntimeException('view generation failed');
                    }

                    return $mode !== 'false';
                }
            };
        }

        throw new RuntimeException('Unexpected oxNew: ' . $class);
    }
}
PHP;

        $bootstrapBody = str_replace('__REPO_ROOT__', var_export($srcRoot, true), $bootstrapBody);
        file_put_contents($path, $bootstrapBody);

        return [
            'path' => $path,
            'resultFile' => $resultFile,
            'controlFile' => $controlFile,
            'token' => $token,
        ];
    }

    public static function copyGenerateViewsScript(string $destinationDirectory): string
    {
        $targetDir = $destinationDirectory;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Cannot create directory: ' . $targetDir);
        }

        $source = dirname(__DIR__, 2) . '/generate_views.php';
        $target = $targetDir . '/generate_views.php';
        if (!copy($source, $target)) {
            throw new \RuntimeException('Failed to copy generate_views.php');
        }

        return $target;
    }

    public static function removeTree(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            unlink($path);

            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            self::removeTree($path . '/' . $item);
        }

        rmdir($path);
    }
}
