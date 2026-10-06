<?php

namespace ArjanWestdorp\Exposable\Test\Integration\Guards;

use ArjanWestdorp\Exposable\Test\TestCase;
use ArjanWestdorp\Exposable\Test\Stubs\User;
use ArjanWestdorp\Exposable\Exceptions\UnauthorizedException;

class AuthGuardTest extends TestCase
{
    public function test_it_authenticates_if_the_user_is_logged_in()
    {
        $this->be($this->createUser());

        $this->get(url('/middleware-guard?guard=auth'))
            ->assertSuccessful()
            ->assertSee('You did it');
    }

    public function test_it_does_not_authenticate_if_the_user_is_not_logged_in()
    {
        $this->disableExceptionHandling()->expectException(UnauthorizedException::class);

        $this->get(url('/middleware-guard?guard=auth'));
    }
}
