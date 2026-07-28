<?php

declare(strict_types=1);

namespace MelnixDev\CsvDuplicateFinder;

use InvalidArgumentException;

final class DisjointSet
{
    /** @var array<string, string> */
    private array $parent = [];

    /** @var array<string, int> */
    private array $rank = [];

    /** @var array<string, int> */
    private array $firstPosition = [];

    /** @var array<string, string> */
    private array $representative = [];

    /** @var array<string, string> */
    private array $ids = [];

    public function add(string $id, int $position): void
    {
        if ($id === '') {
            throw new InvalidArgumentException('ID cannot be empty.');
        }

        $key = self::key($id);
        if (array_key_exists($key, $this->parent)) {
            throw new InvalidArgumentException(sprintf('Duplicate ID "%s".', $id));
        }

        $this->parent[$key] = $key;
        $this->rank[$key] = 0;
        $this->firstPosition[$key] = $position;
        $this->representative[$key] = $id;
        $this->ids[$key] = $id;
    }

    public function find(string $id): string
    {
        $root = $this->findKey($this->requireKey($id));

        return $this->ids[$root];
    }

    public function representative(string $id): string
    {
        $root = $this->findKey($this->requireKey($id));

        return $this->representative[$root];
    }

    public function union(string $firstId, string $secondId): void
    {
        $firstRoot = $this->findKey($this->requireKey($firstId));
        $secondRoot = $this->findKey($this->requireKey($secondId));

        if ($firstRoot === $secondRoot) {
            return;
        }

        if ($this->rank[$firstRoot] < $this->rank[$secondRoot]) {
            [$firstRoot, $secondRoot] = [$secondRoot, $firstRoot];
        }

        $this->parent[$secondRoot] = $firstRoot;

        if ($this->rank[$firstRoot] === $this->rank[$secondRoot]) {
            ++$this->rank[$firstRoot];
        }

        if ($this->firstPosition[$secondRoot] < $this->firstPosition[$firstRoot]) {
            $this->firstPosition[$firstRoot] = $this->firstPosition[$secondRoot];
            $this->representative[$firstRoot] = $this->representative[$secondRoot];
        }
    }

    private function requireKey(string $id): string
    {
        $key = self::key($id);
        if (!array_key_exists($key, $this->parent)) {
            throw new InvalidArgumentException(sprintf('Unknown ID "%s".', $id));
        }

        return $key;
    }

    private function findKey(string $key): string
    {
        if ($this->parent[$key] !== $key) {
            $this->parent[$key] = $this->findKey($this->parent[$key]);
        }

        return $this->parent[$key];
    }

    private static function key(string $value): string
    {
        return strlen($value) . ':' . $value;
    }
}
