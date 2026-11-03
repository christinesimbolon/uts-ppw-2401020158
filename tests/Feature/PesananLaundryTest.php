<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MySqlTestCase;

class PesananLaundryTest extends MySqlTestCase
{
    private const INDEX = '/pesanan-laundry';

    public function test_index_displays_all_seed_orders_services_and_student_identity(): void
    {
        $this->get(self::INDEX)
            ->assertOk()
            ->assertSee('Christine Simbolon')
            ->assertSee('2401020158')
            ->assertSee('Cuci Kering')
            ->assertSee('Cuci Setrika')
            ->assertSee('LD00000001')->assertSee('Ayu Lestari')->assertSee('081200000001')
            ->assertSee('LD00000002')->assertSee('Rafi Akbar')->assertSee('081200000002')
            ->assertSee('LD00000003')->assertSee('Nina Putri')->assertSee('081200000003')
            ->assertSee('Reguler');
    }

    public function test_named_routes_have_the_required_paths_and_http_methods(): void
    {
        $routes = $this->app['router']->getRoutes();
        $expected = [
            'index' => ['pesanan-laundry', ['GET', 'HEAD']],
            'store' => ['pesanan-laundry', ['POST']],
            'edit' => ['pesanan-laundry/{id}/edit', ['GET', 'HEAD']],
            'update' => ['pesanan-laundry/{id}', ['PUT']],
            'destroy' => ['pesanan-laundry/{id}', ['DELETE']],
        ];

        foreach ($expected as $suffix => [$uri, $methods]) {
            $route = $routes->getByName('pesanan-laundry.'.$suffix);
            $this->assertNotNull($route, 'Missing route pesanan-laundry.'.$suffix);
            $this->assertSame($uri, $route->uri());
            $this->assertEqualsCanonicalizing($methods, $route->methods());
        }
    }

    public function test_store_persists_every_field_and_flashes_success(): void
    {
        $payload = $this->validOrder();

        $this->post(self::INDEX, $payload)
            ->assertRedirect(self::INDEX)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $rows = $this->rows('SELECT * FROM pesanan_laundry WHERE kode_pesanan = ?', [$payload['kode_pesanan']]);
        $this->assertCount(1, $rows);
        $this->assertSame(4, $this->orderCount());
        $this->assertSame($payload['nama_pelanggan'], $rows[0]->nama_pelanggan);
        $this->assertSame($payload['nomor_telepon'], $rows[0]->nomor_telepon);
        $this->assertSame(10, (int) $rows[0]->berat_kg);
        $this->assertSame(2, (int) $rows[0]->jenis_layanan_id);
        $this->get(self::INDEX)->assertOk()->assertSee('Besar')->assertSee('Reguler');
    }

    public function test_edit_displays_the_existing_order_and_service_options(): void
    {
        $this->get(self::INDEX.'/1/edit')
            ->assertOk()
            ->assertSee('LD00000001')->assertSee('Ayu Lestari')->assertSee('081200000001')
            ->assertSee('Cuci Kering')->assertSee('Cuci Setrika');
    }

    public function test_update_persists_all_changed_fields(): void
    {
        $payload = $this->validOrder();

        $this->put(self::INDEX.'/1', $payload)
            ->assertRedirect(self::INDEX)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $order = $this->order(1);
        $this->assertNotNull($order);
        $this->assertSame($payload['kode_pesanan'], $order->kode_pesanan);
        $this->assertSame($payload['nama_pelanggan'], $order->nama_pelanggan);
        $this->assertSame($payload['nomor_telepon'], $order->nomor_telepon);
        $this->assertSame(10, (int) $order->berat_kg);
        $this->assertSame(2, (int) $order->jenis_layanan_id);
        $this->assertSame(3, $this->orderCount());
        $this->assertSame('Rafi Akbar', $this->order(2)->nama_pelanggan);
    }

    public function test_update_accepts_the_orders_own_unchanged_unique_values(): void
    {
        $this->put(self::INDEX.'/1', $this->validOrder([
            'kode_pesanan' => 'LD00000001',
            'nomor_telepon' => '081200000001',
            'nama_pelanggan' => 'Ayu Diperbarui',
        ]))->assertRedirect(self::INDEX)->assertSessionHasNoErrors()->assertSessionHas('success');

        $order = $this->order(1);
        $this->assertSame('LD00000001', $order->kode_pesanan);
        $this->assertSame('081200000001', $order->nomor_telepon);
        $this->assertSame('Ayu Diperbarui', $order->nama_pelanggan);
        $this->assertSame(3, $this->orderCount());
    }

    public function test_an_entirely_unchanged_update_is_not_mistaken_for_a_missing_order(): void
    {
        $this->put(self::INDEX.'/1', [
            'kode_pesanan' => 'LD00000001',
            'nama_pelanggan' => 'Ayu Lestari',
            'nomor_telepon' => '081200000001',
            'berat_kg' => 5,
            'jenis_layanan_id' => 1,
        ])->assertRedirect(self::INDEX)->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('Ayu Lestari', $this->order(1)->nama_pelanggan);
        $this->assertSame(3, $this->orderCount());
    }

    public function test_delete_removes_only_the_requested_order(): void
    {
        $this->delete(self::INDEX.'/1')
            ->assertRedirect(self::INDEX)->assertSessionHas('success')->assertSessionHasNoErrors();

        $this->assertNull($this->order(1));
        $this->assertSame(2, $this->orderCount());
        $this->assertNotNull($this->order(2));
        $this->assertNotNull($this->order(3));
        $this->assertCount(2, $this->rows('SELECT id FROM jenis_layanan'));
    }

    #[DataProvider('uniqueFields')]
    public function test_store_rejects_each_duplicate_with_a_field_error(string $field, string $value): void
    {
        $this->from(self::INDEX)->post(self::INDEX, $this->validOrder([$field => $value]))
            ->assertRedirect(self::INDEX)->assertSessionHasErrors([$field]);

        $this->assertSame(3, $this->orderCount());
    }

    #[DataProvider('uniqueFields')]
    public function test_update_rejects_another_orders_unique_value(string $field, string $value): void
    {
        $before = $this->order(2);

        $this->from(self::INDEX.'/2/edit')->put(self::INDEX.'/2', $this->validOrder([$field => $value]))
            ->assertRedirect(self::INDEX.'/2/edit')->assertSessionHasErrors([$field]);

        $this->assertEquals($before, $this->order(2));
        $this->assertSame(3, $this->orderCount());
    }

    public static function uniqueFields(): iterable
    {
        yield 'kode_pesanan' => ['kode_pesanan', 'LD00000001'];
        yield 'nomor_telepon' => ['nomor_telepon', '081200000001'];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_store_data_has_field_errors_and_is_not_persisted(string $field, mixed $value): void
    {
        $this->from(self::INDEX)->post(self::INDEX, $this->validOrder([$field => $value]))
            ->assertRedirect(self::INDEX)->assertSessionHasErrors([$field]);

        $this->assertSame(3, $this->orderCount());
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_update_data_has_field_errors_and_leaves_the_row_unchanged(string $field, mixed $value): void
    {
        $before = $this->order(1);

        $this->from(self::INDEX.'/1/edit')->put(self::INDEX.'/1', $this->validOrder([$field => $value]))
            ->assertRedirect(self::INDEX.'/1/edit')->assertSessionHasErrors([$field]);

        $this->assertEquals($before, $this->order(1));
    }

    public static function invalidFields(): iterable
    {
        yield 'missing code' => ['kode_pesanan', ''];
        yield 'code too short' => ['kode_pesanan', 'LD0000004'];
        yield 'code too long' => ['kode_pesanan', 'LD000000004'];
        yield 'code contains nonalphanumeric character' => ['kode_pesanan', 'LD0000000!'];
        yield 'array code' => ['kode_pesanan', ['LD00000004']];
        yield 'missing customer' => ['nama_pelanggan', ''];
        yield 'customer too short' => ['nama_pelanggan', 'Li'];
        yield 'customer too long' => ['nama_pelanggan', str_repeat('A', 101)];
        yield 'array customer' => ['nama_pelanggan', ['Dina Sari']];
        yield 'missing phone' => ['nomor_telepon', ''];
        yield 'phone too short' => ['nomor_telepon', '081200004'];
        yield 'phone too long' => ['nomor_telepon', str_repeat('1', 21)];
        yield 'array phone' => ['nomor_telepon', ['081200000004']];
        yield 'missing weight' => ['berat_kg', ''];
        yield 'zero weight' => ['berat_kg', 0];
        yield 'negative weight' => ['berat_kg', -1];
        yield 'weight above maximum' => ['berat_kg', 21];
        yield 'fractional weight' => ['berat_kg', 1.5];
        yield 'nonnumeric weight' => ['berat_kg', 'berat'];
        yield 'array weight' => ['berat_kg', [10]];
        yield 'missing service' => ['jenis_layanan_id', ''];
        yield 'unknown service' => ['jenis_layanan_id', 999999];
        yield 'invalid service type' => ['jenis_layanan_id', 'abc'];
        yield 'array service' => ['jenis_layanan_id', [2]];
    }

    public function test_validation_redirect_preserves_old_input(): void
    {
        $payload = $this->validOrder(['nama_pelanggan' => 'Nama Belum Disimpan', 'berat_kg' => 0]);

        $this->from(self::INDEX)->post(self::INDEX, $payload)
            ->assertRedirect(self::INDEX)->assertSessionHasErrors(['berat_kg'])
            ->assertSessionHasInput('kode_pesanan', $payload['kode_pesanan'])
            ->assertSessionHasInput('nama_pelanggan', $payload['nama_pelanggan'])
            ->assertSessionHasInput('nomor_telepon', $payload['nomor_telepon'])
            ->assertSessionHasInput('berat_kg', 0)
            ->assertSessionHasInput('jenis_layanan_id', 2);

        $this->get(self::INDEX)->assertOk()->assertSee('Nama Belum Disimpan');
    }

    #[DataProvider('weightBoundaries')]
    public function test_both_inclusive_weight_boundaries_are_accepted(int $weight): void
    {
        $this->post(self::INDEX, $this->validOrder(['berat_kg' => $weight]))
            ->assertRedirect(self::INDEX)->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame($weight, (int) $this->rows('SELECT berat_kg FROM pesanan_laundry WHERE kode_pesanan = ?', ['LD00000004'])[0]->berat_kg);
    }

    public static function weightBoundaries(): iterable
    {
        yield 'minimum' => [1];
        yield 'maximum' => [20];
    }

    #[DataProvider('mutationMethods')]
    public function test_text_is_trimmed_stripped_of_tags_and_escaped_in_blade(string $method): void
    {
        $code = $method === 'POST' ? 'LD00000004' : 'LD00000001';
        $phone = $method === 'POST' ? '081200000004' : '081200000001';
        $url = $method === 'POST' ? self::INDEX : self::INDEX.'/1';
        $payload = $this->validOrder([
            'kode_pesanan' => ' <b>'.$code.'</b> ',
            'nama_pelanggan' => ' <img src=x onerror=alert(1)><b>Dina & Dini</b> ',
            'nomor_telepon' => ' <i>'.$phone.'</i> ',
        ]);

        $this->call($method, $url, $payload)
            ->assertRedirect(self::INDEX)->assertSessionHasNoErrors()->assertSessionHas('success');

        $order = $this->rows('SELECT * FROM pesanan_laundry WHERE kode_pesanan = ?', [$code])[0];
        $this->assertSame('Dina & Dini', $order->nama_pelanggan);
        $this->assertSame($phone, $order->nomor_telepon);
        $this->get(self::INDEX)->assertOk()->assertSee('Dina &amp; Dini', false)
            ->assertDontSee('<img src=x', false)->assertDontSee('<b>Dina', false);
    }

    public static function mutationMethods(): iterable
    {
        yield 'store' => ['POST'];
        yield 'update' => ['PUT'];
    }

    #[DataProvider('searchTerms')]
    public function test_search_matches_each_of_the_three_columns(string $query, string $code): void
    {
        $response = $this->get(self::INDEX.'?'.http_build_query(['q' => $query]))->assertOk()->assertSee($code);

        foreach (array_diff(['LD00000001', 'LD00000002', 'LD00000003'], [$code]) as $otherCode) {
            $response->assertDontSee($otherCode);
        }
    }

    public static function searchTerms(): iterable
    {
        yield 'order code' => ['LD00000001', 'LD00000001'];
        yield 'partial customer name' => ['Rafi', 'LD00000002'];
        yield 'telephone' => ['081200000003', 'LD00000003'];
    }

    public function test_service_filter_and_search_are_combined_with_and(): void
    {
        $this->get(self::INDEX.'?jenis_layanan_id=1')->assertOk()
            ->assertSee('LD00000001')->assertSee('LD00000003')->assertDontSee('LD00000002');

        $this->get(self::INDEX.'?'.http_build_query(['q' => 'Nina', 'jenis_layanan_id' => 1]))->assertOk()
            ->assertSee('LD00000003')->assertDontSee('LD00000001')->assertDontSee('LD00000002');

        $this->get(self::INDEX.'?'.http_build_query(['q' => 'Nina', 'jenis_layanan_id' => 2]))->assertOk()
            ->assertDontSee('LD00000001')->assertDontSee('LD00000002')->assertDontSee('LD00000003');
    }

    public function test_sql_injection_search_is_treated_as_literal_input(): void
    {
        $this->get(self::INDEX.'?'.http_build_query(['q' => "' OR 1=1; DROP TABLE pesanan_laundry; -- "]))
            ->assertOk()->assertDontSee('LD00000001')->assertDontSee('LD00000002')->assertDontSee('LD00000003');

        $this->assertSame(3, $this->orderCount());
        $this->assertCount(2, $this->rows('SELECT id FROM jenis_layanan'));
    }

    #[DataProvider('missingOrderRequests')]
    public function test_missing_orders_return_404_without_changing_data(string $method, string $suffix): void
    {
        $this->call($method, self::INDEX.'/999999'.$suffix, $this->validOrder())->assertNotFound();
        $this->assertSame(3, $this->orderCount());
    }

    public static function missingOrderRequests(): iterable
    {
        yield 'edit' => ['GET', '/edit'];
        yield 'update' => ['PUT', ''];
        yield 'delete' => ['DELETE', ''];
    }

    #[DataProvider('wrongMethodRequests')]
    public function test_unsupported_methods_cannot_mutate_data(string $method, string $suffix): void
    {
        $before = $this->order(1);
        $this->call($method, self::INDEX.$suffix, $this->validOrder())->assertStatus(405);
        $this->assertEquals($before, $this->order(1));
        $this->assertSame(3, $this->orderCount());
    }

    public static function wrongMethodRequests(): iterable
    {
        yield 'GET is not delete' => ['GET', '/1'];
        yield 'POST is not update' => ['POST', '/1'];
        yield 'PATCH is not PUT' => ['PATCH', '/1'];
        yield 'PUT cannot target collection' => ['PUT', ''];
    }

    public function test_index_and_edit_are_plain_html_without_css_or_javascript(): void
    {
        foreach ([self::INDEX, self::INDEX.'/1/edit'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression('/<(?:style|script)\b|\sstyle\s*=|<link\b[^>]*\brel\s*=\s*[\'"]?stylesheet/i', $html);
        }
    }
}
