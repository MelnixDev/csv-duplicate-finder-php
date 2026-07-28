<?php

declare(strict_types=1);

namespace MelnixDev\CsvDuplicateFinder\Tests;

use InvalidArgumentException;
use MelnixDev\CsvDuplicateFinder\DuplicateFinder;
use PHPUnit\Framework\TestCase;

final class DuplicateFinderTest extends TestCase
{
    public function testFindsGroupsFromOriginalExample(): void
    {
        $rows = [
            self::row('1', 'email1', 'card1', 'phone1'),
            self::row('2', 'email2', 'card1', 'phone2'),
            self::row('3', 'email3', 'card3', 'phone3'),
            self::row('4', 'email1', 'card2', 'phone4'),
            self::row('5', 'email5', 'card5', 'phone2'),
            self::row('6', 'email6', 'card6', 'phone6'),
            self::row('7', 'email3', 'card9', 'phone7'),
            self::row('8', 'email8', 'card10', 'phone8'),
            self::row('9', 'email9', 'card9', 'phone3'),
            self::row('10', 'email2', 'card10', 'phone10'),
        ];

        self::assertSame(
            [
                ['ID' => '1', 'PARENT_ID' => '1'],
                ['ID' => '2', 'PARENT_ID' => '1'],
                ['ID' => '3', 'PARENT_ID' => '3'],
                ['ID' => '4', 'PARENT_ID' => '1'],
                ['ID' => '5', 'PARENT_ID' => '1'],
                ['ID' => '6', 'PARENT_ID' => '6'],
                ['ID' => '7', 'PARENT_ID' => '3'],
                ['ID' => '8', 'PARENT_ID' => '1'],
                ['ID' => '9', 'PARENT_ID' => '3'],
                ['ID' => '10', 'PARENT_ID' => '1'],
            ],
            (new DuplicateFinder())->find($rows),
        );
    }

    public function testKeepsIndependentMergedGroupsSeparate(): void
    {
        $rows = [
            self::row('1', 'email1', 'card1', 'phone1'),
            self::row('2', 'email2', 'card2', 'phone2'),
            self::row('3', 'email1', 'card2', 'phone3'),
            self::row('4', 'email4', 'card4', 'phone4'),
            self::row('5', 'email5', 'card5', 'phone5'),
            self::row('6', 'email4', 'card5', 'phone6'),
        ];

        self::assertSame(
            [
                ['ID' => '1', 'PARENT_ID' => '1'],
                ['ID' => '2', 'PARENT_ID' => '1'],
                ['ID' => '3', 'PARENT_ID' => '1'],
                ['ID' => '4', 'PARENT_ID' => '4'],
                ['ID' => '5', 'PARENT_ID' => '4'],
                ['ID' => '6', 'PARENT_ID' => '4'],
            ],
            (new DuplicateFinder())->find($rows),
        );
    }

    public function testPreservesActualNonSequentialIds(): void
    {
        $rows = [
            self::row('100', 'email1', 'card1', 'phone1'),
            self::row('900', 'email2', 'card1', 'phone2'),
        ];

        self::assertSame(
            [
                ['ID' => '100', 'PARENT_ID' => '100'],
                ['ID' => '900', 'PARENT_ID' => '100'],
            ],
            (new DuplicateFinder())->find($rows),
        );
    }

    public function testIgnoresBlankAndNullValues(): void
    {
        $rows = [
            self::row('1', 'email1', '', null),
            self::row('2', 'email2', 'NULL', '  '),
        ];

        self::assertSame(
            [
                ['ID' => '1', 'PARENT_ID' => '1'],
                ['ID' => '2', 'PARENT_ID' => '2'],
            ],
            (new DuplicateFinder())->find($rows),
        );
    }

    public function testDoesNotMatchValuesAcrossDifferentFields(): void
    {
        $rows = [
            self::row('1', 'shared', 'card1', 'phone1'),
            self::row('2', 'email2', 'shared', 'phone2'),
        ];

        self::assertSame(
            [
                ['ID' => '1', 'PARENT_ID' => '1'],
                ['ID' => '2', 'PARENT_ID' => '2'],
            ],
            (new DuplicateFinder())->find($rows),
        );
    }

    public function testTrimsValuesBeforeMatching(): void
    {
        $rows = [
            self::row('1', ' email@example.com ', 'card1', 'phone1'),
            self::row('2', 'email@example.com', 'card2', 'phone2'),
        ];

        self::assertSame('1', (new DuplicateFinder())->find($rows)[1]['PARENT_ID']);
    }

    public function testRejectsDuplicateIds(): void
    {
        $rows = [
            self::row('1', 'email1', 'card1', 'phone1'),
            self::row('1', 'email2', 'card2', 'phone2'),
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate ID "1".');

        (new DuplicateFinder())->find($rows);
    }

    public function testRejectsMissingMatchingFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Row 1 is missing required field "PHONE".');

        (new DuplicateFinder())->find([
            ['ID' => '1', 'EMAIL' => 'email1', 'CARD' => 'card1'],
        ]);
    }

    public function testRequiresAtLeastOneMatchingField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one matching field is required.');

        new DuplicateFinder([]);
    }

    /**
     * @return array{ID: string, EMAIL: string|null, CARD: string|null, PHONE: string|null}
     */
    private static function row(
        string $id,
        ?string $email,
        ?string $card,
        ?string $phone,
    ): array {
        return [
            'ID' => $id,
            'EMAIL' => $email,
            'CARD' => $card,
            'PHONE' => $phone,
        ];
    }
}
