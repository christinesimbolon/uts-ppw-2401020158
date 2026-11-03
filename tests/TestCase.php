<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected const TEST_DATABASE = 'laundry_2401020158_testing';

    public function createApplication(): Application
    {
        if ((string) getenv('TEST_TOKEN') !== '') {
            throw new RuntimeException('Do not run this shared-database integration suite with --parallel.');
        }

        $expected = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => self::TEST_DATABASE,
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'DB_URL' => '',
            'DB_SOCKET' => '',
        ];

        foreach ($expected as $name => $value) {
            $actual = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

            if ((string) $actual !== $value) {
                throw new RuntimeException("Unsafe testing environment: {$name}. Use this project's phpunit.xml.");
            }
        }

        $app = require dirname(__DIR__).'/bootstrap/app.php';
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));

        // Check even cached configuration before any application provider can open a connection.
        $app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
            $expected = [
                'app.env' => 'testing',
                'app.debug' => false,
                'database.default' => 'mysql',
                'database.connections.mysql.driver' => 'mysql',
                'database.connections.mysql.host' => '127.0.0.1',
                'database.connections.mysql.port' => '3306',
                'database.connections.mysql.database' => self::TEST_DATABASE,
                'database.connections.mysql.username' => 'root',
                'database.connections.mysql.password' => '',
                'database.connections.mysql.url' => '',
                'database.connections.mysql.unix_socket' => '',
                'database.connections.mysql.prefix' => '',
                'session.driver' => 'array',
                'cache.default' => 'array',
            ];

            foreach ($expected as $key => $value) {
                $actual = $app['config']->get($key);
                $matches = is_bool($value) ? $actual === $value : (string) $actual === $value;

                if (! $matches) {
                    throw new RuntimeException("Unsafe testing configuration: {$key}. Clear stale config before testing.");
                }
            }
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
