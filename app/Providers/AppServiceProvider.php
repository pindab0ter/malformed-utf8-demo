<?php

declare(strict_types=1);

namespace App\Providers;

use App\Database\ErrorSanitizingSqlLiteConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Connection::resolverFor('sqlite', function ($connection, $database, $prefix, $config) {
            return new ErrorSanitizingSqlLiteConnection($connection, $database, $prefix, $config);
        });
    }
}
