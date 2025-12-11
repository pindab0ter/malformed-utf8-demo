<?php

declare(strict_types=1);

namespace App\Providers;

use App\Database\ErrorSanitizingSqlLiteConnection;
use App\Exceptions\Renderer\Renderer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Exceptions\Renderer\Listener;
use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\Foundation\Exceptions\Renderer\Renderer as IlluminateExceptionRenderer;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(IlluminateExceptionRenderer::class, function (Application $app) {
            $errorRenderer = new HtmlErrorRenderer(config('app.debug'));

            return new Renderer(
                $app->make(Factory::class),
                $app->make(Listener::class),
                $errorRenderer,
                $app->make(BladeMapper::class),
                $app->basePath(),
            );
        });
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
