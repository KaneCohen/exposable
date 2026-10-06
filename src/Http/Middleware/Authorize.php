<?php

namespace ArjanWestdorp\Exposable\Http\Middleware;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use ArjanWestdorp\Exposable\Guards\Guard;
use ArjanWestdorp\Exposable\Exceptions\InvalidGuardException;
use ArjanWestdorp\Exposable\Exceptions\UnauthorizedException;

class Authorize
{
    /**
     * Check if the user is authenticated with the given guard.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws UnauthorizedException
     */
    public function handle($request, Closure $next)
    {
        if (! $request->has('guard')) {
            if (config('exposable.require-guard')) {
                throw UnauthorizedException::guardIsRequired();
            }

            return $next($request);
        }

        $guard = $this->retrieveGuard($request->get('guard'));

        // Validate guard
        if (! $guard->authenticate()) {
            throw UnauthorizedException::unauthenticated($guard);
        }

        return $next($request);
    }

    /**
     * Retrieve guard for the given key.
     *
     * @param string $key
     * @return Guard
     * @throws InvalidGuardException
     */
    protected function retrieveGuard($key)
    {
        $class = Arr::get(config('exposable.guards'), $key);

        if (is_null($class)) {
            throw InvalidGuardException::guardNotDefined($key);
        }

        return app($class);
    }
}
