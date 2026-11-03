<?php

namespace Tests\Feature;

use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MySqlTestCase;

class LaundrySqlConstraintsTest extends MySqlTestCase
{
    public function test_connection_is_real_mysql_and_selects_only_the_exact_testing_database(): void
    {
        $this->assertSame('mysql', $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertSame(self::TEST_DATABASE, $this->rows('SELECT DATABASE() AS name')[0]->name);
    }

    #[DataProvider('duplicateFields')]
    public function test_database_rejects_each_duplicate_order_unique_field(string $field, string $value): void
    {
        $data = $this->validOrder([$field => $value]);

        try {
            $this->executeSql(
                'INSERT INTO pesanan_laundry (kode_pesanan, nama_pelanggan, nomor_telepon, berat_kg, jenis_layanan_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
                array_values($data),
            );
            $this->fail('The database accepted a duplicate '.$field.'.');
        } catch (PDOException $exception) {
            $this->assertSame('23000', (string) $exception->getCode());
        }

        $this->assertSame(3, $this->orderCount());
    }

    public static function duplicateFields(): iterable
    {
        yield 'unique order code' => ['kode_pesanan', 'LD00000001'];
        yield 'unique telephone' => ['nomor_telepon', '081200000001'];
    }

    public function test_database_rejects_duplicate_service_name(): void
    {
        try {
            $this->executeSql('INSERT INTO jenis_layanan (nama_layanan) VALUES (?)', ['Cuci Kering']);
            $this->fail('The database accepted a duplicate service name.');
        } catch (PDOException $exception) {
            $this->assertSame('23000', (string) $exception->getCode());
        }

        $this->assertCount(2, $this->rows('SELECT id FROM jenis_layanan'));
    }

    #[DataProvider('notNullFields')]
    public function test_database_rejects_null_for_each_required_field(string $table, string $field): void
    {
        $select = "SELECT * FROM `{$table}` WHERE id = ?";
        $before = $this->rows($select, [1]);

        try {
            $this->executeSql("UPDATE `{$table}` SET `{$field}` = ? WHERE id = ?", [null, 1]);
            $this->fail('The database accepted NULL for '.$table.'.'.$field.'.');
        } catch (PDOException $exception) {
            $this->assertSame('23000', (string) $exception->getCode());
            $this->assertSame(1048, (int) $exception->errorInfo[1]);
        }

        $this->assertEquals($before, $this->rows($select, [1]));
    }

    public static function notNullFields(): iterable
    {
        yield 'service name' => ['jenis_layanan', 'nama_layanan'];
        yield 'order code' => ['pesanan_laundry', 'kode_pesanan'];
        yield 'customer name' => ['pesanan_laundry', 'nama_pelanggan'];
        yield 'telephone' => ['pesanan_laundry', 'nomor_telepon'];
        yield 'weight' => ['pesanan_laundry', 'berat_kg'];
        yield 'service foreign key' => ['pesanan_laundry', 'jenis_layanan_id'];
    }

    public function test_database_rejects_a_nonexistent_foreign_key(): void
    {
        try {
            $this->executeSql('UPDATE pesanan_laundry SET jenis_layanan_id = ? WHERE id = ?', [999999, 1]);
            $this->fail('The database accepted a nonexistent service.');
        } catch (PDOException $exception) {
            $this->assertSame('23000', (string) $exception->getCode());
        }

        $this->assertSame(1, (int) $this->order(1)->jenis_layanan_id);
    }

    #[DataProvider('invalidWeights')]
    public function test_database_enforces_the_unsigned_one_to_twenty_weight_range(int $weight): void
    {
        try {
            $this->executeSql('UPDATE pesanan_laundry SET berat_kg = ? WHERE id = ?', [$weight, 1]);
            $this->fail('The database accepted an out-of-range weight.');
        } catch (PDOException $exception) {
            // CHECK violation SQLSTATE differs between MySQL and MariaDB.
            $this->assertNotSame('', $exception->getMessage());
        }

        $this->assertSame(5, (int) $this->order(1)->berat_kg);
    }

    public static function invalidWeights(): iterable
    {
        yield 'below zero' => [-1];
        yield 'below minimum' => [0];
        yield 'above maximum' => [21];
    }

    public function test_service_primary_key_updates_cascade_to_orders(): void
    {
        $this->executeSql('UPDATE jenis_layanan SET id = ? WHERE id = ?', [11, 1]);

        $this->assertSame(11, (int) $this->order(1)->jenis_layanan_id);
        $this->assertSame(11, (int) $this->order(3)->jenis_layanan_id);
        $this->assertSame(2, (int) $this->order(2)->jenis_layanan_id);
    }

    public function test_referenced_service_cannot_be_deleted(): void
    {
        try {
            $this->executeSql('DELETE FROM jenis_layanan WHERE id = ?', [1]);
            $this->fail('The database deleted a referenced service.');
        } catch (PDOException $exception) {
            $this->assertSame('23000', (string) $exception->getCode());
        }

        $this->assertSame(3, $this->orderCount());
        $this->assertCount(1, $this->rows('SELECT id FROM jenis_layanan WHERE id = ?', [1]));
    }
}
