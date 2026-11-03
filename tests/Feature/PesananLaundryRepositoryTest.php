<?php

namespace Tests\Feature;

use App\Repositories\PesananLaundryRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MySqlTestCase;

class PesananLaundryRepositoryTest extends MySqlTestCase
{
    private function repository(): PesananLaundryRepository
    {
        return $this->app->make(PesananLaundryRepository::class);
    }

    private function ids(array $rows): array
    {
        $ids = array_map(static fn ($row): int => (int) ((object) $row)->id, $rows);
        sort($ids);

        return $ids;
    }

    public function test_layanan_returns_the_two_sql_seeded_services(): void
    {
        $services = $this->repository()->layanan();
        $this->assertSame([1, 2], $this->ids($services));
        $names = array_map(static fn ($row): string => ((object) $row)->nama_layanan, $services);
        $this->assertEqualsCanonicalizing(['Cuci Kering', 'Cuci Setrika'], $names);
    }

    public function test_daftar_returns_all_orders_without_filters(): void
    {
        $this->assertSame([1, 2, 3], $this->ids($this->repository()->daftar()));
    }

    #[DataProvider('searchQueries')]
    public function test_daftar_searches_the_three_columns_using_literal_values(string $query, array $expected): void
    {
        $this->assertSame($expected, $this->ids($this->repository()->daftar($query)));
        $this->assertSame(3, $this->orderCount());
    }

    public static function searchQueries(): iterable
    {
        yield 'code' => ['LD00000001', [1]];
        yield 'partial name' => ['Rafi', [2]];
        yield 'phone' => ['081200000003', [3]];
        yield 'no match' => ['Tidak Ada Pelanggan Ini', []];
        yield 'quote injection' => ["' OR 1=1 -- ", []];
        yield 'stacked query injection' => ["'; DROP TABLE pesanan_laundry; -- ", []];
    }

    public function test_daftar_applies_service_filter_even_when_search_is_empty(): void
    {
        $this->assertSame([1, 3], $this->ids($this->repository()->daftar('', 1)));
        $this->assertSame([2], $this->ids($this->repository()->daftar('', 2)));
        $this->assertSame([], $this->repository()->daftar('', 999999));
    }

    public function test_daftar_combines_search_and_service_filter(): void
    {
        $this->assertSame([3], $this->ids($this->repository()->daftar('Nina', 1)));
        $this->assertSame([], $this->repository()->daftar('Nina', 2));
        $this->assertSame([], $this->repository()->daftar("' OR 1=1 -- ", 1));
    }

    public function test_cari_returns_an_object_or_null(): void
    {
        $order = $this->repository()->cari(1);
        $this->assertIsObject($order);
        $this->assertSame('LD00000001', $order->kode_pesanan);
        $this->assertSame('Ayu Lestari', $order->nama_pelanggan);
        $this->assertSame('081200000001', $order->nomor_telepon);
        $this->assertSame(5, (int) $order->berat_kg);
        $this->assertSame(1, (int) $order->jenis_layanan_id);
        $this->assertNull($this->repository()->cari(999999));
    }

    public function test_simpan_writes_bound_text_and_all_other_fields_to_mysql(): void
    {
        $payload = $this->validOrder(['nama_pelanggan' => "Dina O'Connor; -- bukan SQL"]);
        $this->repository()->simpan($payload);

        $orders = $this->rows('SELECT * FROM pesanan_laundry WHERE kode_pesanan = ?', [$payload['kode_pesanan']]);
        $this->assertCount(1, $orders);
        $this->assertSame($payload['nama_pelanggan'], $orders[0]->nama_pelanggan);
        $this->assertSame($payload['nomor_telepon'], $orders[0]->nomor_telepon);
        $this->assertSame(10, (int) $orders[0]->berat_kg);
        $this->assertSame(2, (int) $orders[0]->jenis_layanan_id);
        $this->assertSame(4, $this->orderCount());
    }

    public function test_ubah_updates_only_the_target_and_returns_false_for_missing_id(): void
    {
        $payload = $this->validOrder(['nama_pelanggan' => "Nama O'Connor"]);
        $this->assertTrue($this->repository()->ubah(1, $payload));
        $order = $this->order(1);
        $this->assertSame($payload['kode_pesanan'], $order->kode_pesanan);
        $this->assertSame($payload['nama_pelanggan'], $order->nama_pelanggan);
        $this->assertSame($payload['nomor_telepon'], $order->nomor_telepon);
        $this->assertSame(10, (int) $order->berat_kg);
        $this->assertSame(2, (int) $order->jenis_layanan_id);
        $this->assertSame('Rafi Akbar', $this->order(2)->nama_pelanggan);
        $this->assertFalse($this->repository()->ubah(999999, $payload));
        $this->assertSame(3, $this->orderCount());
    }

    public function test_ubah_an_existing_identical_order_still_returns_true(): void
    {
        $this->assertTrue($this->repository()->ubah(1, [
            'kode_pesanan' => 'LD00000001',
            'nama_pelanggan' => 'Ayu Lestari',
            'nomor_telepon' => '081200000001',
            'berat_kg' => 5,
            'jenis_layanan_id' => 1,
        ]));
        $this->assertSame('LD00000001', $this->order(1)->kode_pesanan);
        $this->assertSame(3, $this->orderCount());
    }

    public function test_hapus_returns_whether_an_order_was_deleted(): void
    {
        $this->assertTrue($this->repository()->hapus(1));
        $this->assertNull($this->order(1));
        $this->assertFalse($this->repository()->hapus(1));
        $this->assertFalse($this->repository()->hapus(999999));
        $this->assertSame(2, $this->orderCount());
    }

    public function test_kode_terpakai_honours_the_except_id_and_does_not_interpolate_input(): void
    {
        $repository = $this->repository();
        $this->assertTrue($repository->kodeTerpakai('LD00000001'));
        $this->assertFalse($repository->kodeTerpakai('LD00000001', 1));
        $this->assertTrue($repository->kodeTerpakai('LD00000001', 2));
        $this->assertFalse($repository->kodeTerpakai('LD99999999'));
        $this->assertFalse($repository->kodeTerpakai("' OR 1=1 -- "));
    }

    public function test_telepon_terpakai_honours_the_except_id_and_does_not_interpolate_input(): void
    {
        $repository = $this->repository();
        $this->assertTrue($repository->teleponTerpakai('081200000001'));
        $this->assertFalse($repository->teleponTerpakai('081200000001', 1));
        $this->assertTrue($repository->teleponTerpakai('081200000001', 2));
        $this->assertFalse($repository->teleponTerpakai('081299999999'));
        $this->assertFalse($repository->teleponTerpakai("' OR 1=1 -- "));
    }

    public function test_layanan_tersedia_checks_real_service_ids(): void
    {
        $this->assertTrue($this->repository()->layananTersedia(1));
        $this->assertTrue($this->repository()->layananTersedia(2));
        $this->assertFalse($this->repository()->layananTersedia(0));
        $this->assertFalse($this->repository()->layananTersedia(999999));
    }
}
