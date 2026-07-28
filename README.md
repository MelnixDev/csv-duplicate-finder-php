# CSV Duplicate Finder in PHP

[![CI](https://github.com/MelnixDev/Chain-of-duplicates-in-a-CSV-union-find-php/actions/workflows/ci.yml/badge.svg)](https://github.com/MelnixDev/Chain-of-duplicates-in-a-CSV-union-find-php/actions/workflows/ci.yml)

[Українська версія](README_UA.md)

Find connected groups of duplicate records in a CSV file using a tested
Union-Find (Disjoint Set Union) implementation.

Two records belong to the same group when they share a non-empty value in the
same matching column. Connections are transitive: if record A matches B and B
matches C, all three records receive the same `PARENT_ID`.

## Matching rules

- The default matching columns are `EMAIL`, `CARD`, and `PHONE`.
- Values only match within the same column.
- Leading and trailing whitespace is ignored.
- Empty values and the case-insensitive string `NULL` are ignored.
- Non-empty values are compared exactly and case-sensitively.
- The first record from each connected group becomes its representative.
- A representative points to itself.
- Input order and original ID values are preserved.
- IDs must be non-empty and unique.

For example:

```text
1 matches 2 by CARD
2 matches 5 by PHONE
```

The connected group is `{1, 2, 5}`, and every record in it receives
`PARENT_ID=1`.

## Requirements

- PHP 8.3 or newer
- Composer

## Installation

```bash
git clone https://github.com/MelnixDev/Chain-of-duplicates-in-a-CSV-union-find-php.git
cd Chain-of-duplicates-in-a-CSV-union-find-php
composer install
```

## Command-line usage

Write the result to the terminal:

```bash
php bin/find-duplicates.php examples/input.csv
```

Write the result to a file:

```bash
php bin/find-duplicates.php examples/input.csv output.csv
```

Show help:

```bash
php bin/find-duplicates.php --help
```

`index.php` remains available as a compatible entry point:

```bash
php index.php examples/input.csv
```

## Input format

The CSV must have a header row containing `ID`, `EMAIL`, `CARD`, and `PHONE`.
Additional columns are allowed and ignored. Column order does not matter.

```csv
ID,PARENT_ID,EMAIL,CARD,PHONE,TMP
1,NULL,email1,card1,phone1,
2,NULL,email2,card1,phone2,
3,NULL,email3,card3,phone3,
4,NULL,email1,card2,phone4,
5,NULL,email5,card5,phone2,
```

The parser supports standard quoted CSV values, including commas inside quoted
fields, and accepts a UTF-8 BOM before the first header.

## Output format

The output contains the original `ID` and the representative `PARENT_ID`:

```csv
ID,PARENT_ID
1,1
2,1
3,3
4,1
5,1
```

See [examples/input.csv](examples/input.csv) and
[examples/expected-output.csv](examples/expected-output.csv) for the complete
example.

## Algorithm

Each record starts in its own disjoint set. For every matching column, the
finder remembers the first record seen for each value and performs `union()`
when that value occurs again. `find()` uses path compression, while `union()`
uses rank to keep the trees shallow.

The representative ID is tracked separately from the internal tree root, so
union-by-rank does not change the rule that the first input record represents
the group.

With a fixed number of matching columns, runtime is near-linear:
`O(n α(n))`, where `α` is the inverse Ackermann function. Memory usage is
`O(n + u)`, where `u` is the number of unique matching values.

## Development

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Run all checks:

```bash
composer check
```

The regression suite covers transitive chains, independent group merges,
non-sequential IDs, blank values, cross-column collisions, quoted CSV values,
invalid headers, and terminal execution.

## Current limitations

- Matching is case-sensitive after trimming whitespace.
- Only comma-delimited CSV is supported.
- The complete input is currently held in memory.
- The output intentionally contains only `ID` and `PARENT_ID`.

## License

This project is licensed under the [GNU General Public License v3.0](LICENSE).
