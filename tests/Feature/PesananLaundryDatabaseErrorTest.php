<?php

namespace Tests\Feature;

use App\Repositories\PesananLaundryRepository;
use Illuminate\Support\Facades\Exceptions;
use Mockery;
use Mockery\MockInterface;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MySqlTestCase;

class PesananLaundryDatabaseErrorTest extends MySqlTestCase
{
    private const SECRET = 'SQLSTATE[HY000] db-private-marker laundry_2401020158 root internal-password';

    private function failingRepository(string $method, PDOException $failure): MockInterface
    {
        $repository = Mockery::mock(PesananLaundryRepository::class);
        $repository->shouldReceive('layanan')->byDefault()->andReturn($this->rows('SELECT * FROM jenis_layanan ORDER BY id'));
        $repository->shouldReceive('daftar')->byDefault()->andReturn($this->rows('SELECT * FROM pesanan_laundry ORDER BY id'));
        $repository->shouldReceive('cari')->byDefault()->andReturn($this->order(1));
        $repository->shouldReceive('kodeTerpakai')->byDefault()->andReturn(false);
        $repository->shouldReceive('teleponTerpakai')->byDefault()->andReturn(false);
        $repository->shouldReceive('layananTersedia')->byDefault()->andReturn(true);
        $repository->shouldReceive($method)->once()->andThrow($failure);
        $this->app->instance(PesananLaundryRepository::class, $repository);

        return $repository;
    }

    #[DataProvider('readFailures')]
    public function test_read_database_errors_are_reported_and_render_a_safe_503(string $url, string $method): void
    {
        Exceptions::fake();
        $failure = new PDOException(self::SECRET);
        $this->failingRepository($method, $failure);

        $response = $this->get($url)->assertStatus(503);
        $response->assertDontSee(self::SECRET, false)
            ->assertDontSee('SQLSTATE', false)
            ->assertDontSee('db-private-marker', false)
            ->assertDontSee('internal-password', false)
            ->assertDontSee('laundry_2401020158', false);
        Exceptions::assertReported(fn (PDOException $reported): bool => $reported === $failure);
        $this->assertSame(3, $this->orderCount());
    }

    public static function readFailures(): iterable
    {
        yield 'index order query' => ['/pesanan-laundry', 'daftar'];
        yield 'index service query' => ['/pesanan-laundry', 'layanan'];
        yield 'edit order query' => ['/pesanan-laundry/1/edit', 'cari'];
        yield 'edit service query' => ['/pesanan-laundry/1/edit', 'layanan'];
    }

    #[DataProvider('mutationFailures')]
    public function test_mutation_database_errors_are_reported_redirect_safely_and_keep_old_input(string $verb, string $url, string $method): void
    {
        Exceptions::fake();
        $failure = new PDOException(self::SECRET);
        $this->failingRepository($method, $failure);
        $before = $this->rows('SELECT * FROM pesanan_laundry ORDER BY id');
        $payload = $verb === 'DELETE' ? [] : $this->validOrder();

        $response = $this->from('/pesanan-laundry')->call($verb, $url, $payload)
            ->assertRedirect()
            ->assertSessionHas('error')
            ->assertSessionMissing('success')
            ->assertSessionHas('_old_input')
            ->assertSessionHasNoErrors();

        foreach ($payload as $field => $value) {
            $response->assertSessionHasInput($field, $value);
        }

        $message = $this->app['session.store']->get('error');
        $this->assertIsString($message);
        $this->assertNotSame('', trim($message));

        foreach (['SQLSTATE', 'db-private-marker', 'internal-password', 'laundry_2401020158'] as $detail) {
            $this->assertStringNotContainsString($detail, $message);
            $this->assertStringNotContainsString($detail, (string) $response->headers->get('Location'));
            $response->assertDontSee($detail, false);
        }

        Exceptions::assertReported(fn (PDOException $reported): bool => $reported === $failure);
        $this->assertEquals($before, $this->rows('SELECT * FROM pesanan_laundry ORDER BY id'));
    }

    public static function mutationFailures(): iterable
    {
        yield 'store insert' => ['POST', '/pesanan-laundry', 'simpan'];
        yield 'update write' => ['PUT', '/pesanan-laundry/1', 'ubah'];
        yield 'delete write' => ['DELETE', '/pesanan-laundry/1', 'hapus'];
        yield 'store code validation lookup' => ['POST', '/pesanan-laundry', 'kodeTerpakai'];
        yield 'store phone validation lookup' => ['POST', '/pesanan-laundry', 'teleponTerpakai'];
        yield 'store service validation lookup' => ['POST', '/pesanan-laundry', 'layananTersedia'];
        yield 'update existence lookup' => ['PUT', '/pesanan-laundry/1', 'cari'];
        yield 'delete existence lookup' => ['DELETE', '/pesanan-laundry/1', 'cari'];
    }

    #[DataProvider('validationRequests')]
    public function test_validation_exceptions_are_not_swallowed_as_database_errors(string $verb, string $url): void
    {
        Exceptions::fake();

        $this->from('/pesanan-laundry')->call($verb, $url, $this->validOrder(['berat_kg' => 0]))
            ->assertRedirect('/pesanan-laundry')
            ->assertSessionHasErrors(['berat_kg'])
            ->assertSessionMissing('error')
            ->assertSessionMissing('success')
            ->assertSessionHasInput('nama_pelanggan', 'Dina Sari');

        Exceptions::assertNothingReported();
        $this->assertSame(3, $this->orderCount());
    }

    public static function validationRequests(): iterable
    {
        yield 'store validation' => ['POST', '/pesanan-laundry'];
        yield 'update validation' => ['PUT', '/pesanan-laundry/1'];
    }
}
