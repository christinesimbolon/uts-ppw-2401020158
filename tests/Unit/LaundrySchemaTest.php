<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\LaundrySchema;

class LaundrySchemaTest extends TestCase
{
    public function test_database_creation_and_use_are_removed_instead_of_executed(): void
    {
        $sql = <<<'SQL'
            -- The main database must not be selected by tests.
            CREATE DATABASE IF NOT EXISTS `laundry_2401020158`;
            USE `laundry_2401020158`;
            CREATE TABLE `jenis_layanan` (id BIGINT UNSIGNED PRIMARY KEY, nama_layanan VARCHAR(100));
            INSERT INTO `jenis_layanan` (id, nama_layanan) VALUES (1, 'Cuci Kering');
            SQL;

        $statements = LaundrySchema::statements($sql);
        $this->assertCount(2, $statements);
        $this->assertStringStartsWith('CREATE TABLE', $statements[0]);
        $this->assertStringStartsWith('INSERT INTO', $statements[1]);
        $this->assertStringNotContainsString('laundry_2401020158', implode("\n", $statements));
    }

    public function test_multiline_mixed_case_database_statements_and_comments_are_removed(): void
    {
        $sql = "\xEF\xBB\xBF/* header */ CrEaTe\nDATABASE IF NOT EXISTS `laundry_2401020158`;\n"
            ."# select main database\nuSe\n`laundry_2401020158`;\n"
            .'CREATE TABLE jenis_layanan (id BIGINT); -- trailing comment';

        $this->assertSame(['CREATE TABLE jenis_layanan (id BIGINT)'], LaundrySchema::statements($sql));
    }

    public function test_semicolons_comment_markers_and_doubled_quotes_inside_values_are_preserved(): void
    {
        $sql = <<<'SQL'
            CREATE TABLE jenis_layanan (id BIGINT, nama_layanan VARCHAR(100));
            INSERT INTO jenis_layanan (id, nama_layanan) VALUES (1, 'Cuci; -- # /* bukan komentar */ O''Connor');
            SQL;

        $statements = LaundrySchema::statements($sql);
        $this->assertCount(2, $statements);
        $this->assertStringContainsString("'Cuci; -- # /* bukan komentar */ O''Connor'", $statements[1]);
    }

    public function test_a_final_statement_without_semicolon_is_supported(): void
    {
        $this->assertSame(
            ['INSERT INTO jenis_layanan VALUES (1, \'Cuci Kering\')'],
            LaundrySchema::statements('INSERT INTO jenis_layanan VALUES (1, \'Cuci Kering\')'),
        );
    }

    #[DataProvider('unsafeFixtures')]
    public function test_unsupported_or_malformed_fixtures_fail_closed_before_table_reset(string $sql): void
    {
        $this->expectException(RuntimeException::class);
        LaundrySchema::statements($sql);
    }

    public static function unsafeFixtures(): iterable
    {
        yield 'database statements only' => ['CREATE DATABASE other; USE other;'];
        yield 'no executable statements' => ['-- empty fixture'];
        yield 'unrelated table' => ['CREATE TABLE users (id BIGINT);'];
        yield 'qualified main table' => ['CREATE TABLE laundry_2401020158.jenis_layanan (id BIGINT);'];
        yield 'destructive command' => ['DROP DATABASE laundry_2401020158;'];
        yield 'unrelated insert' => ['INSERT INTO users (id) VALUES (1);'];
        yield 'cross-database foreign key' => ['CREATE TABLE pesanan_laundry (jenis_layanan_id BIGINT REFERENCES `laundry_2401020158`.`jenis_layanan` (id));'];
        yield 'unrelated foreign key' => ['CREATE TABLE pesanan_laundry (jenis_layanan_id BIGINT REFERENCES users (id));'];
        yield 'unterminated string' => ["INSERT INTO jenis_layanan (nama_layanan) VALUES ('Cuci);"];
        yield 'unterminated comment' => ['CREATE TABLE jenis_layanan (id BIGINT); /* unfinished'];
    }
}
