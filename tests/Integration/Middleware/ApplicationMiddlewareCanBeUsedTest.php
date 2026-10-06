<?php

namespace ArjanWestdorp\Exposable\Test\Integration\Middleware;

use ArjanWestdorp\Exposable\Test\TestCase;
use ArjanWestdorp\Exposable\Test\Stubs\User;
use Illuminate\Auth\AuthenticationException;

class ApplicationMiddlewareCanBeUsedTest extends TestCase
{
    /**
     * Add change the exposable.middleware before registering the package.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    public function getPackageProviders($app)
    {
        $app['config']->set('exposable.middleware', ['auth']);

        return parent::getPackageProviders($app);
    }

    public function test_it_will_use_the_application_auth_middleware_and_fail_without_user()
    {
        $this->disableExceptionHandling()->useAuthenticatedGuard()->expectException(AuthenticationException::class);

        $this->get($this->createAttachment()->exposeUrl(), ['Accept' => 'application/json'])->assertStatus(401);
    }

    public function test_it_will_use_the_application_auth_middleware_and_succeed_with_a_user()
    {
        $this->be($this->createUser());

        $this->get($this->createAttachment()->exposeUrl())->assertStatus(200)->assertSee('You did it');
    }
}
