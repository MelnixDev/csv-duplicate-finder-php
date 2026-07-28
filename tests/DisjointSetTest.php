<?php

declare(strict_types=1);

namespace MelnixDev\CsvDuplicateFinder\Tests;

use InvalidArgumentException;
use MelnixDev\CsvDuplicateFinder\DisjointSet;
use PHPUnit\Framework\TestCase;

final class DisjointSetTest extends TestCase
{
    public function testUsesFirstInputRecordAsRepresentative(): void
    {
        $sets = new DisjointSet();
        $sets->add('first', 0);
        $sets->add('second', 1);
        $sets->add('third', 2);

        $sets->union('second', 'third');
        $sets->union('third', 'first');

        self::assertSame('first', $sets->representative('first'));
        self::assertSame('first', $sets->representative('second'));
        self::assertSame('first', $sets->representative('third'));
    }

    public function testKeepsIndependentSetsSeparate(): void
    {
        $sets = new DisjointSet();
        foreach (['1', '2', '3', '4'] as $position => $id) {
            $sets->add($id, $position);
        }

        $sets->union('1', '2');
        $sets->union('3', '4');

        self::assertSame('1', $sets->representative('2'));
        self::assertSame('3', $sets->representative('4'));
    }

    public function testRejectsDuplicateIds(): void
    {
        $sets = new DisjointSet();
        $sets->add('1', 0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate ID "1".');

        $sets->add('1', 1);
    }

    public function testRejectsUnknownIds(): void
    {
        $sets = new DisjointSet();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown ID "missing".');

        $sets->find('missing');
    }
}
