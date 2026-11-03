<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatusBeratTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $helper = dirname(__DIR__, 2).'/app/helpers.php';
        $this->assertFileExists($helper, 'The backend must supply app/helpers.php.');
        require_once $helper;
    }

    #[DataProvider('weights')]
    public function test_status_berat_uses_ten_as_the_inclusive_besar_boundary(int $weight, string $expected): void
    {
        $this->assertTrue(function_exists('statusBerat'));
        $this->assertSame($expected, \statusBerat($weight));
    }

    public static function weights(): iterable
    {
        yield 'minimum valid weight' => [1, 'Reguler'];
        yield 'first seed weight' => [5, 'Reguler'];
        yield 'below threshold' => [9, 'Reguler'];
        yield 'exact threshold' => [10, 'Besar'];
        yield 'above threshold' => [11, 'Besar'];
        yield 'maximum valid weight' => [20, 'Besar'];
    }
}
