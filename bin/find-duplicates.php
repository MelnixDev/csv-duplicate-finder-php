<?php

declare(strict_types=1);

use MelnixDev\CsvDuplicateFinder\CsvProcessor;

$projectRoot = dirname(__DIR__);
$autoloadPath = $projectRoot . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    fwrite(STDERR, "Dependencies are missing. Run: composer install\n");
    exit(1);
}

require $autoloadPath;

/** @var list<string> $argumentVector */
$argumentVector = $_SERVER['argv'] ?? [];
$scriptName = basename($argumentVector[0] ?? 'find-duplicates.php');
$arguments = array_slice($argumentVector, 1);

if ($arguments === ['--help'] || $arguments === ['-h']) {
    fwrite(
        STDOUT,
        "Usage: php {$scriptName} <input.csv> [output.csv]\n"
        . "\n"
        . "Without output.csv, the result is written to standard output.\n",
    );
    exit(0);
}

if (count($arguments) < 1 || count($arguments) > 2) {
    fwrite(STDERR, "Usage: php {$scriptName} <input.csv> [output.csv]\n");
    exit(64);
}

[$inputPath] = $arguments;
$outputPath = $arguments[1] ?? null;

try {
    $inputRealPath = realpath($inputPath);
    if ($outputPath !== null && $inputRealPath !== false && realpath($outputPath) === $inputRealPath) {
        throw new RuntimeException('Input and output paths must be different.');
    }

    $output = $outputPath === null ? STDOUT : @fopen($outputPath, 'wb');
    if ($output === false) {
        throw new RuntimeException(sprintf('Unable to open output file "%s".', $outputPath));
    }

    try {
        (new CsvProcessor())->process($inputPath, $output);
    } finally {
        if ($outputPath !== null) {
            fclose($output);
        }
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Error: ' . $error->getMessage() . "\n");
    exit(1);
}
