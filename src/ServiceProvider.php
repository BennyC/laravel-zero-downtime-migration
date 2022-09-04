<?php

namespace Daursu\ZeroDowntimeMigration;

use Daursu\ZeroDowntimeMigration\Commands\Status;
use Daursu\ZeroDowntimeMigration\Connections\GhostConnection;
use Daursu\ZeroDowntimeMigration\Connections\PtOnlineSchemaChangeConnection;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../config/zero-down.php';
        $this->publishes([$configPath => config_path('zero-down.php')], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Status::class,
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/zero-down.php';
        $this->mergeConfigFrom($configPath, 'zero-down');

        Connection::resolverFor('pt-online-schema-change', function ($connection, $database, $prefix, $config) {
            return new PtOnlineSchemaChangeConnection($connection, $database, $prefix, $config);
        });

        Connection::resolverFor('gh-ost', function ($connection, $database, $prefix, $config) {
            return new GhostConnection($connection, $database, $prefix, $config);
        });

        $this->app->bind('db.connector.pt-online-schema-change', function () {
            return new MySqlConnector;
        });

        $this->app->bind('db.connector.gh-ost', function () {
            return new MySqlConnector;
        });
    }
}
