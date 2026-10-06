<?php

namespace ArjanWestdorp\Exposable\Test\Unit\Exceptions;

use ArjanWestdorp\Exposable\Test\TestCase;
use ArjanWestdorp\Exposable\Exceptions\UnauthorizedException;
use ArjanWestdorp\Exposable\Test\Stubs\FakeAuthenticatedGuard;

class UnauthorizedExceptionTest extends TestCase
{
    public function test_it_can_return_the_guard_the_exception_occurred_for()
    {
        $guard = app(FakeAuthenticatedGuard::class);

        $exception = new UnauthorizedException('Unauthorized', $guard);

        $this->assertEquals($guard, $exception->guard());
    }
}
