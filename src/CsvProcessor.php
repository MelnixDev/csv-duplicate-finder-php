<?php

declare(strict_types=1);

namespace MelnixDev\CsvDuplicateFinder;

use RuntimeException;

final class CsvProcessor
{
    public function __construct(
        private readonly DuplicateFinder $finder = new DuplicateFinder(),
    ) {
    }

    /**
     * @param resource $output
     */
    public function process(string $inputPath, mixed $output): void
    {
        if (!is_resource($output)) {
            throw new RuntimeException('Output must be a writable stream.');
        }

        $rows = $this->readRows($inputPath);
        $result = $this->finder->find($rows);

        if (fputcsv($output, ['ID', 'PARENT_ID'], ',', '"', '', "\n") === false) {
            throw new RuntimeException('Unable to write the CSV header.');
        }

        foreach ($result as $row) {
            if (fputcsv($output, [$row['ID'], $row['PARENT_ID']], ',', '"', '', "\n") === false) {
                throw new RuntimeException('Unable to write a CSV row.');
            }
        }
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function readRows(string $inputPath): array
    {
        $handle = @fopen($inputPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to read input file "%s".', $inputPath));
        }

        try {
            $header = fgetcsv($handle, null, ',', '"', '');
            if ($header === false) {
                throw new RuntimeException('The input CSV is empty.');
            }

            $headers = array_map(
                static fn (?string $value): string => trim((string) $value),
                $header,
            );
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];

            if (in_array('', $headers, true)) {
                throw new RuntimeException('CSV headers cannot be empty.');
            }

            if (count(array_unique($headers)) !== count($headers)) {
                throw new RuntimeException('CSV headers must be unique.');
            }

            foreach (['ID', ...$this->finder->fields()] as $requiredHeader) {
                if (!in_array($requiredHeader, $headers, true)) {
                    throw new RuntimeException(sprintf('Missing required CSV header "%s".', $requiredHeader));
                }
            }

            $rows = [];
            $line = 1;

            while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
                ++$line;

                if ($values === [null]) {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    throw new RuntimeException(
                        sprintf(
                            'CSV row %d has %d columns; expected %d.',
                            $line,
                            count($values),
                            count($headers),
                        ),
                    );
                }

                /** @var array<string, string|null> $row */
                $row = array_combine($headers, $values);
                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
