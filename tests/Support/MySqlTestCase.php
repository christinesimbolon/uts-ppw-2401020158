<?php

namespace Tests\Support;

use PDO;
use RuntimeException;
use Tests\TestCase;

abstract class MySqlTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $path = database_path('sql/laundry_2401020158.sql');

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Provide database/sql/laundry_2401020158.sql before running integration tests.');
        }

        // Parse and validate the entire fixture before making any destructive change.
        $statements = LaundrySchema::statements(file_get_contents($path));
        $this->pdo = $this->app['db']->connection('mysql')->getPdo();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->guardSelectedDatabase();

        // Child first: no global foreign-key disabling, migrations, or unrelated table drops.
        $this->pdo->exec('DROP TABLE IF EXISTS `pesanan_laundry`');
        $this->pdo->exec('DROP TABLE IF EXISTS `jenis_layanan`');

        foreach ($statements as $statement) {
            $this->guardSelectedDatabase();
            $this->pdo->exec($statement);
        }

        $this->assertSame(2, (int) $this->rows('SELECT COUNT(*) AS total FROM jenis_layanan')[0]->total);
        $this->assertSame(3, $this->orderCount());
    }

    protected function guardSelectedDatabase(): void
    {
        $statement = $this->pdo->prepare('SELECT DATABASE()');
        $statement->execute();
        $database = (string) $statement->fetchColumn();

        if ($database !== self::TEST_DATABASE || ! str_ends_with($database, '_testing')) {
            throw new RuntimeException('Refusing to reset any database except laundry_2401020158_testing.');
        }
    }

    /** @return list<object> */
    protected function rows(string $sql, array $bindings = []): array
    {
        $this->guardSelectedDatabase();
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll(PDO::FETCH_OBJ);
    }

    protected function executeSql(string $sql, array $bindings = []): void
    {
        $this->guardSelectedDatabase();
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);
    }

    protected function orderCount(): int
    {
        return (int) $this->rows('SELECT COUNT(*) AS total FROM pesanan_laundry')[0]->total;
    }

    protected function order(int $id): ?object
    {
        return $this->rows('SELECT * FROM pesanan_laundry WHERE id = ?', [$id])[0] ?? null;
    }

    protected function validOrder(array $overrides = []): array
    {
        return array_replace([
            'kode_pesanan' => 'LD00000004',
            'nama_pelanggan' => 'Dina Sari',
            'nomor_telepon' => '081200000004',
            'berat_kg' => 10,
            'jenis_layanan_id' => 2,
        ], $overrides);
    }
}
