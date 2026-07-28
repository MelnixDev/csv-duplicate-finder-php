<?php

declare(strict_types=1);

namespace MelnixDev\CsvDuplicateFinder\Tests;

use PHPUnit\Framework\TestCase;

final class CliTest extends TestCase
{
    public function testRunsFromTheTerminalAndWritesCsvToStandardOutput(): void
    {
        $result = $this->runCommand([
            PHP_BINARY,
            __DIR__ . '/../bin/find-duplicates.php',
            __DIR__ . '/../examples/input.csv',
        ]);

        self::assertSame(0, $result['exitCode']);
        self::assertSame('', $result['stderr']);
        self::assertSame(
            file_get_contents(__DIR__ . '/../examples/expected-output.csv'),
            $result['stdout'],
        );
    }

    public function testShowsHelpFromTheTerminal(): void
    {
        $result = $this->runCommand([
            PHP_BINARY,
            __DIR__ . '/../bin/find-duplicates.php',
            '--help',
        ]);

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('<input.csv> [output.csv]', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testWritesTheResultToAFile(): void
    {
        $outputPath = tempnam(sys_get_temp_dir(), 'csv-duplicate-output-');
        self::assertIsString($outputPath);

        try {
            $result = $this->runCommand([
                PHP_BINARY,
                __DIR__ . '/../bin/find-duplicates.php',
                __DIR__ . '/../examples/input.csv',
                $outputPath,
            ]);

            self::assertSame(0, $result['exitCode']);
            self::assertSame('', $result['stdout']);
            self::assertSame('', $result['stderr']);
            self::assertSame(
                file_get_contents(__DIR__ . '/../examples/expected-output.csv'),
                file_get_contents($outputPath),
            );
        } finally {
            @unlink($outputPath);
        }
    }

    public function testRejectsTheSameInputAndOutputPath(): void
    {
        $inputPath = __DIR__ . '/../examples/input.csv';
        $result = $this->runCommand([
            PHP_BINARY,
            __DIR__ . '/../bin/find-duplicates.php',
            $inputPath,
            $inputPath,
        ]);

        self::assertSame(1, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Input and output paths must be different.', $result['stderr']);
    }

    public function testRejectsMissingArguments(): void
    {
        $result = $this->runCommand([
            PHP_BINARY,
            __DIR__ . '/../bin/find-duplicates.php',
        ]);

        self::assertSame(64, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Usage:', $result['stderr']);
    }

    /**
     * @param non-empty-list<string> $command
     *
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    private function runCommand(array $command): array
    {
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );
        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [
            'exitCode' => proc_close($process),
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }
}
