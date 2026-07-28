<?php

declare(strict_types=1);

namespace MelnixDev\CsvDuplicateFinder\Tests;

use MelnixDev\CsvDuplicateFinder\CsvProcessor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CsvProcessorTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }
    }

    public function testProcessesTheExampleCsv(): void
    {
        $output = fopen('php://temp', 'w+b');
        self::assertIsResource($output);

        (new CsvProcessor())->process(__DIR__ . '/../examples/input.csv', $output);

        rewind($output);
        $actual = stream_get_contents($output);
        fclose($output);

        self::assertSame(
            file_get_contents(__DIR__ . '/../examples/expected-output.csv'),
            $actual,
        );
    }

    public function testParsesQuotedValuesContainingCommas(): void
    {
        $input = $this->temporaryCsv(
            "ID,EMAIL,CARD,PHONE\n"
            . "100,\"person,tag@example.com\",card1,phone1\n"
            . "900,\"person,tag@example.com\",card2,phone2\n",
        );
        $output = fopen('php://temp', 'w+b');
        self::assertIsResource($output);

        (new CsvProcessor())->process($input, $output);

        rewind($output);
        $actual = stream_get_contents($output);
        fclose($output);

        self::assertSame("ID,PARENT_ID\n100,100\n900,100\n", $actual);
    }

    public function testAcceptsUtf8BomInTheFirstHeader(): void
    {
        $input = $this->temporaryCsv(
            "\xEF\xBB\xBFID,EMAIL,CARD,PHONE\n"
            . "1,email1,card1,phone1\n",
        );
        $output = fopen('php://temp', 'w+b');
        self::assertIsResource($output);

        (new CsvProcessor())->process($input, $output);

        rewind($output);
        $actual = stream_get_contents($output);
        fclose($output);

        self::assertSame("ID,PARENT_ID\n1,1\n", $actual);
    }

    public function testRejectsMissingHeaders(): void
    {
        $input = $this->temporaryCsv("ID,EMAIL,CARD\n1,email1,card1\n");
        $output = fopen('php://temp', 'w+b');
        self::assertIsResource($output);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required CSV header "PHONE".');

        try {
            (new CsvProcessor())->process($input, $output);
        } finally {
            fclose($output);
        }
    }

    public function testRejectsRowsWithTheWrongColumnCount(): void
    {
        $input = $this->temporaryCsv("ID,EMAIL,CARD,PHONE\n1,email1,card1\n");
        $output = fopen('php://temp', 'w+b');
        self::assertIsResource($output);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSV row 2 has 3 columns; expected 4.');

        try {
            (new CsvProcessor())->process($input, $output);
        } finally {
            fclose($output);
        }
    }

    public function testRejectsAnEmptyCsv(): void
    {
        $input = $this->temporaryCsv('');
        $output = fopen('php://temp', 'w+b');
        self::assertIsResource($output);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The input CSV is empty.');

        try {
            (new CsvProcessor())->process($input, $output);
        } finally {
            fclose($output);
        }
    }

    private function temporaryCsv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv-duplicate-finder-');
        if ($path === false) {
            self::fail('Unable to create a temporary CSV file.');
        }

        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
