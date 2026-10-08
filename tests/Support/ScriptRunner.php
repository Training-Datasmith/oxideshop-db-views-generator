<?php

declare(strict_types=1);

namespace OxidEsales\DatabaseViewsGenerator\Tests\Support;

final class ScriptRunner
{
    /**
     * @param array<string, string> $environment
     * @return array{exitCode: int|null, stdout: string, stderr: string}
     */
    public static function run(
        string $scriptPath,
        string $workingDirectory,
        array $environment = [],
        int $timeoutSeconds = 5
    ): array {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = [
            'PATH' => getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ];
        foreach ($environment as $key => $value) {
            $env[$key] = $value;
        }

        $process = proc_open(
            [
                PHP_BINARY,
                '-d',
                'display_errors=0',
                '-d',
                'display_startup_errors=0',
                $scriptPath,
            ],
            $descriptorSpec,
            $pipes,
            $workingDirectory,
            $env
        );

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start script process');
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $start = time();
        $status = proc_get_status($process);
        while ($status['running']) {
            if (time() - $start >= $timeoutSeconds) {
                proc_terminate($process);
                throw new \RuntimeException('Script process timed out');
            }
            usleep(50_000);
            $status = proc_get_status($process);
        }

        $exitCode = $status['exitcode'];
        proc_close($process);

        return [
            'exitCode' => $exitCode === -1 ? null : $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }
}
