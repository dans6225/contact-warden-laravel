<?php

declare(strict_types=1);

namespace ContactWardenLaravel;

use ContactWarden\Admin\AdminDataSource;
use ContactWarden\Admin\AdminMaintenance;
use ContactWarden\Store\DatabaseConfig;
use ContactWarden\Store\PdoAdminDataSource;
use ContactWarden\Store\PdoAdminMaintenance;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class ContactWardenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdminDataSource::class, fn (Application $app) => new PdoAdminDataSource($this->pdo($app)));
        $this->app->singleton(AdminMaintenance::class, fn (Application $app) => new PdoAdminMaintenance($this->pdo($app)));
        $this->app->singleton(LaravelAdminConnector::class, fn (Application $app) => new LaravelAdminConnector(
            $app->make(AdminDataSource::class),
            $app->make(AdminMaintenance::class),
        ));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'contact-warden');
    }

    /**
     * A dedicated PDO connection to the app's own default database connection
     * — cw_* tables live alongside the app's regular schema. Not Laravel's own
     * Illuminate\Database\Connection, since AdminDataSource/AdminMaintenance
     * need a real \PDO instance either way — same reasoning as the CI4
     * connector's Config\Services factory.
     */
    private function pdo(Application $app): \PDO
    {
        $name = $app['config']->get('database.default');
        $config = $app['config']->get("database.connections.{$name}");

        return new \PDO(
            (new DatabaseConfig(
                host: $config['host'],
                database: $config['database'],
                username: $config['username'],
                password: $config['password'],
                port: (int) ($config['port'] ?? 3306),
                charset: $config['charset'] ?? 'utf8mb4',
            ))->toDsn(),
            $config['username'],
            $config['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
        );
    }
}
