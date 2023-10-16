<?php

namespace ArjanWestdorp\Exposable;

use Illuminate\Support\ServiceProvider;
use ArjanWestdorp\Exposable\Signers\ExposableSigner;
use ArjanWestdorp\Exposable\Http\Middleware\Authorize;
use ArjanWestdorp\Exposable\Http\Middleware\CheckSignature;
use ArjanWestdorp\Exposable\Http\Middleware\CheckExpiration;
use ArjanWestdorp\Exposable\Http\Controllers\ExposableController;

class ExposableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/exposable.php' => config_path('exposable.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/exposable.php', 'exposable');

        $this->registerMiddleware();

        $this->registerRoutes();

        $this->app->make(ExposableController::class);

        $this->app->bind('exposable.signer', function () {
            return new ExposableSigner(config('exposable.key'));
        });
    }

    /**
     * Register all middleware.
     */
    protected function registerMiddleware()
    {
        $this->app['router']->aliasMiddleware('exposable.signature', CheckSignature::class);
        $this->app['router']->aliasMiddleware('exposable.guard', Authorize::class);
        $this->app['router']->aliasMiddleware('exposable.expire', CheckExpiration::class);
    }

    /**
     * Register the route to expose models.
     */
    protected function registerRoutes()
    {
        $middleware = ['exposable.signature', 'exposable.expire', 'exposable.guard'];

        if (! empty(config('exposable.middleware'))) {
            $middleware = array_merge($middleware, (array) config('exposable.middleware'));
        }

        if (config('exposable.routes')) {
            foreach (config('exposable.routes') as $type => $controller) {
                $this->app['router']->get(config('exposable.url-prefix').'/'.$type.'/{id}', $controller)->middleware($middleware);
            }
        }

        $uri = config('exposable.url-prefix').'/{exposable}/{id}';

        $this->app['router']->get($uri, 'ArjanWestdorp\Exposable\Http\Controllers\ExposableController@show')->middleware($middleware)->name('exposable.show');
    }
}
