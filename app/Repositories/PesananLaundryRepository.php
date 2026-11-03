<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use PDO;

class PesananLaundryRepository
{
    private function pdo(): PDO
    {
        return DB::connection()->getPdo();
    }

    public function layanan(): array
    {
        $statement = $this->pdo()->prepare('SELECT id, nama_layanan FROM jenis_layanan ORDER BY id');
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_OBJ);
    }

    public function daftar(string $q = '', ?int $jenisLayananId = null): array
    {
        $sql = 'SELECT p.*, j.nama_layanan FROM pesanan_laundry p
                JOIN jenis_layanan j ON j.id = p.jenis_layanan_id WHERE 1 = 1';
        $parameters = [];

        if ($q !== '') {
            // Native PDO membutuhkan placeholder berbeda pada setiap kemunculan.
            $sql .= ' AND (p.kode_pesanan LIKE :kode OR p.nama_pelanggan LIKE :nama OR p.nomor_telepon LIKE :telepon)';
            $parameters = ['kode' => '%'.$q.'%', 'nama' => '%'.$q.'%', 'telepon' => '%'.$q.'%'];
        }

        if ($jenisLayananId !== null) {
            $sql .= ' AND p.jenis_layanan_id = :layanan';
            $parameters['layanan'] = $jenisLayananId;
        }

        $sql .= ' ORDER BY p.id';
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_OBJ);
    }

    public function cari(int $id): ?object
    {
        $statement = $this->pdo()->prepare('SELECT * FROM pesanan_laundry WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->fetchObject() ?: null;
    }

    public function simpan(array $data): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO pesanan_laundry
            (kode_pesanan, nama_pelanggan, nomor_telepon, berat_kg, jenis_layanan_id, created_at, updated_at)
            VALUES (:kode_pesanan, :nama_pelanggan, :nomor_telepon, :berat_kg, :jenis_layanan_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
        $statement->execute($data);
    }

    public function ubah(int $id, array $data): bool
    {
        $statement = $this->pdo()->prepare('UPDATE pesanan_laundry SET
            kode_pesanan = :kode_pesanan, nama_pelanggan = :nama_pelanggan,
            nomor_telepon = :nomor_telepon, berat_kg = :berat_kg,
            jenis_layanan_id = :jenis_layanan_id, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute([...$data, 'id' => $id]);

        // MySQL boleh mengembalikan nol untuk pembaruan yang nilainya tidak berubah.
        return $statement->rowCount() > 0 || $this->cari($id) !== null;
    }

    public function hapus(int $id): bool
    {
        $statement = $this->pdo()->prepare('DELETE FROM pesanan_laundry WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function kodeTerpakai(string $kode, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM pesanan_laundry WHERE kode_pesanan = :kode';
        $parameters = ['kode' => $kode];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $parameters['id'] = $exceptId;
        }
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function teleponTerpakai(string $telepon, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM pesanan_laundry WHERE nomor_telepon = :telepon';
        $parameters = ['telepon' => $telepon];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $parameters['id'] = $exceptId;
        }
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function layananTersedia(int $id): bool
    {
        $statement = $this->pdo()->prepare('SELECT id FROM jenis_layanan WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
