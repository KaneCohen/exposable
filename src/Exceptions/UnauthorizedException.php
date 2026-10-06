<?php

namespace ArjanWestdorp\Exposable\Exceptions;

use Exception;
use ArjanWestdorp\Exposable\Guards\Guard;

class UnauthorizedException extends Exception
{
    /**
     * @var Guard
     */
    protected $guard;

    /**
     * Create a new unauthorized exception.
     *
     * @param string $message
     * @param Guard $guard
     */
    public function __construct($message, ?Guard $guard = null)
    {
        parent::__construct($message);

        $this->guard = $guard;
    }

    /**
     * Guard is required.
     *
     * @return static
     */
    public static function guardIsRequired()
    {
        return new static('Guard is required.');
    }

    /**
     * Unauthenticated by the guard.
     *
     * @param Guard $guard
     * @return static
     */
    public static function unauthenticated($guard)
    {
        return new static('Unauthorized.', $guard);
    }

    /**
     * Get the guard which triggered this exception.
     *
     * @return Guard
     */
    public function guard()
    {
        return $this->guard;
    }
}
