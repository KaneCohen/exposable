<?php

namespace ArjanWestdorp\Exposable\Test\Integration\Middleware;

use Carbon\Carbon;
use ArjanWestdorp\Exposable\Test\TestCase;
use ArjanWestdorp\Exposable\Exceptions\ExpiredExposableException;

class CheckExpirationTest extends TestCase
{
    public function test_it_will_throw_an_exception_if_a_url_is_expired()
    {
        $this->disableExceptionHandling()->expectException(ExpiredExposableException::class);

        $this->get('/middleware-expire?expire='.Carbon::now()->subDay()->timestamp);
    }

    public function test_it_will_throw_an_exception_if_the_expire_parameter_is_not_present()
    {
        $this->disableExceptionHandling()->expectException(ExpiredExposableException::class);

        $this->get('/middleware-expire');
    }

    public function test_it_will_throw_an_exception_if_the_expire_parameter_is_invalid()
    {
        $this->disableExceptionHandling()->expectException(ExpiredExposableException::class);

        $this->get('/middleware-expire?expire=abc');
    }

    public function test_it_will_continue_if_the_url_is_not_expired()
    {
        $this->get('/middleware-expire?expire='.Carbon::now()->addMinutes(5)->timestamp)->assertSuccessful()->assertSee('You did it');
    }
}
