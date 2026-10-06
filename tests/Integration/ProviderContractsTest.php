<?php

namespace ArjanWestdorp\Exposable\Test\Integration;

use ArjanWestdorp\Exposable\ExposableServiceProvider;
use ArjanWestdorp\Exposable\Http\Middleware\Authorize;
use ArjanWestdorp\Exposable\Http\Middleware\CheckExpiration;
use ArjanWestdorp\Exposable\Http\Middleware\CheckSignature;
use ArjanWestdorp\Exposable\Signers\Signer;
use ArjanWestdorp\Exposable\Signers\ExposableSigner;
use ArjanWestdorp\Exposable\Test\TestCase;

class ProviderContractsTest extends TestCase
{
    public function test_provider_facade_binding_aliases_routes_and_config_contracts()
    {
        $this->assertInstanceOf(ExposableSigner::class, app('exposable.signer'));
        $this->assertNotSame(app('exposable.signer'), app('exposable.signer'));
        $aliases = app('router')->getMiddleware();
        $this->assertSame(CheckSignature::class, $aliases['exposable.signature']);
        $this->assertSame(CheckExpiration::class, $aliases['exposable.expire']);
        $this->assertSame(Authorize::class, $aliases['exposable.guard']);
        $route = app('router')->getRoutes()->getByName('exposable.show');
        $this->assertSame('expose/{exposable}/{id}', $route->uri());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(['exposable.signature', 'exposable.expire', 'exposable.guard', 'web'], $route->middleware());
        $paths = ExposableServiceProvider::pathsToPublish(ExposableServiceProvider::class, 'config');
        $sources = array_map('realpath', array_keys($paths));
        $this->assertContains(realpath(__DIR__.'/../../config/exposable.php'), $sources);
        $this->config('key', 'synthetic-provider-key');
        Signer::clearResolvedInstance('exposable.signer');
        $url = Signer::url('https://example.test/p')->guard('auth')->expire(1)->sign();
        $this->assertTrue((new ExposableSigner('synthetic-provider-key'))->validate($url));
    }

    public function test_unknown_exposable_key_returns_404()
    {
        $url = Signer::url(url('/expose/missing/1'))->guard('authenticated')->expire(5)->sign();
        $this->get($url)->assertNotFound();
    }

    public function test_custom_routes_and_application_middleware_remain_registered()
    {
        $this->config('routes', ['custom' => fn ($id) => 'custom '.$id]);
        $this->config('url-prefix', '/custom-expose');
        $this->config('middleware', []);
        (new ExposableServiceProvider($this->app))->register();
        $url = Signer::url(url('/custom-expose/custom/42'))->guard('authenticated')->expire(5)->sign();
        $this->get($url)->assertOk()->assertSee('custom 42');
    }
}
