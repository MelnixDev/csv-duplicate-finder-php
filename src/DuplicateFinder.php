<?php

declare(strict_types=1);

namespace MelnixDev\CsvDuplicateFinder;

use InvalidArgumentException;

final class DuplicateFinder
{
    /**
     * @param list<string> $fields
     */
    public function __construct(
        private readonly array $fields = ['EMAIL', 'CARD', 'PHONE'],
    ) {
        if ($this->fields === []) {
            throw new InvalidArgumentException('At least one matching field is required.');
        }

        if (count(array_unique($this->fields)) !== count($this->fields)) {
            throw new InvalidArgumentException('Duplicate matching fields are not allowed.');
        }
    }

    /**
     * @param list<array<string, string|null>> $rows
     *
     * @return list<array{ID: string, PARENT_ID: string}>
     */
    public function find(array $rows): array
    {
        $sets = new DisjointSet();
        $ids = [];

        /** @var array<string, array<string, string>> $seen */
        $seen = array_fill_keys($this->fields, []);

        foreach ($rows as $position => $row) {
            $id = $this->id($row, $position);
            $sets->add($id, $position);
            $ids[] = $id;

            foreach ($this->fields as $field) {
                if (!array_key_exists($field, $row)) {
                    throw new InvalidArgumentException(
                        sprintf('Row %d is missing required field "%s".', $position + 1, $field),
                    );
                }

                $value = self::normalize($row[$field]);
                if ($value === null) {
                    continue;
                }

                $valueKey = self::key($value);
                if (array_key_exists($valueKey, $seen[$field])) {
                    $sets->union($id, $seen[$field][$valueKey]);
                } else {
                    $seen[$field][$valueKey] = $id;
                }
            }
        }

        $result = [];
        foreach ($ids as $id) {
            $result[] = [
                'ID' => $id,
                'PARENT_ID' => $sets->representative($id),
            ];
        }

        return $result;
    }

    /**
     * @param array<string, string|null> $row
     */
    private function id(array $row, int $position): string
    {
        if (!array_key_exists('ID', $row)) {
            throw new InvalidArgumentException(sprintf('Row %d is missing required field "ID".', $position + 1));
        }

        $id = trim((string) $row['ID']);
        if ($id === '' || strcasecmp($id, 'NULL') === 0) {
            throw new InvalidArgumentException(sprintf('Row %d has an empty ID.', $position + 1));
        }

        return $id;
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        return $value;
    }

    private static function key(string $value): string
    {
        return strlen($value) . ':' . $value;
    }
}
