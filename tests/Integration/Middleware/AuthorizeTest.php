<?php

namespace ArjanWestdorp\Exposable\Test\Integration\Middleware;

use ArjanWestdorp\Exposable\Test\TestCase;
use ArjanWestdorp\Exposable\Exceptions\InvalidGuardException;
use ArjanWestdorp\Exposable\Exceptions\UnauthorizedException;

class AuthorizeTest extends TestCase
{
    public function test_it_does_not_expose_when_no_guard_is_given_and_guard_is_configured_as_required()
    {
        $this->disableExceptionHandling()->expectException(UnauthorizedException::class);

        $this->get(url('/middleware-guard'));
    }

    public function test_it_does_expose_when_no_guard_is_given_and_guard_is_configured_as_not_required()
    {
        $this->config('require-guard', false);

        $this->get(url('/middleware-guard'))->assertSee('You did it');
    }

    public function test_it_throws_an_unauthorized_exception_when_guard_responds_with_unauthenticated()
    {
        $this->disableExceptionHandling()->expectException(UnauthorizedException::class);

        $this->get(url('/middleware-guard?guard=unauthenticated'));
    }

    public function test_it_succeeds_when_guard_is_authenticated()
    {
        $this->get(url('/middleware-guard?guard=authenticated'))->assertSee('You did it');
    }

    public function test_it_throws_an_exception_if_the_guard_is_not_defined()
    {
        $this->disableExceptionHandling()->expectException(InvalidGuardException::class);

        $this->get(url('/middleware-guard?guard=undefined'));
    }
}
