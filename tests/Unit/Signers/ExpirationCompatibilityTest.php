<?php

namespace ArjanWestdorp\Exposable\Test\Unit\Signers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use ArjanWestdorp\Exposable\Http\Middleware\CheckExpiration;
use ArjanWestdorp\Exposable\Exceptions\ExpiredExposableException;
use ArjanWestdorp\Exposable\Signers\Signer;
use ArjanWestdorp\Exposable\Test\TestCase;

class ExpirationCompatibilityTest extends TestCase
{
    public function test_integer_minutes_and_relative_dates_in_multiple_timezones()
    {
        $original = date_default_timezone_get();
        try {
            foreach (['UTC', 'Europe/Amsterdam', 'America/New_York', 'Asia/Tokyo'] as $timezone) {
                date_default_timezone_set($timezone);
                // Cross the spring DST transition using the existing modify semantics.
                Carbon::setTestNow(Carbon::parse('2026-03-29 00:30:00', 'UTC'));
                $now = Carbon::now();
                foreach ([0, 1, -1, 60, '2 days', 'tomorrow'] as $duration) {
                    $relative = is_int($duration) ? $duration.' minutes' : $duration;
                    $expected = (new \DateTimeImmutable($now->format('Y-m-d H:i:s'), new \DateTimeZone($timezone)))->modify($relative)->getTimestamp();
                    $this->assertSame((string) $expected, Signer::url('https://example.test/p')->expire($duration)->get('expire'), $timezone.' '.$relative);
                }
                $date = $now->copy()->addMinutes(20);
                $this->assertSame((string) $date->timestamp, Signer::url('https://example.test/p')->expire($date)->get('expire'));
            }
        } finally {
            date_default_timezone_set($original);
        }
    }

    public function test_expiration_boundary_is_strictly_in_the_past_in_every_timezone()
    {
        $original = date_default_timezone_get();
        try {
            foreach (['UTC', 'Europe/Amsterdam', 'America/New_York'] as $timezone) {
                date_default_timezone_set($timezone);
                Carbon::setTestNow(Carbon::parse('2026-03-29 00:30:00', 'UTC'));
                foreach ([-1, 0, 1] as $delta) {
                    $request = Request::create('/p?expire='.(Carbon::now()->timestamp + $delta));
                    try {
                        $result = (new CheckExpiration())->handle($request, fn () => 'allowed');
                        $this->assertGreaterThanOrEqual(0, $delta);
                        $this->assertSame('allowed', $result);
                    } catch (ExpiredExposableException $exception) {
                        $this->assertSame(-1, $delta);
                    }
                }
                Carbon::setTestNow(Carbon::parse('2026-03-29 00:30:00.500000', 'UTC'));
                $expired = Request::create('/p?expire='.Carbon::now()->timestamp);
                try {
                    (new CheckExpiration())->handle($expired, fn () => 'allowed');
                    $this->fail('An expiration at the start of the current second is already past.');
                } catch (ExpiredExposableException $exception) {
                    $this->assertSame('Exposable is expired', $exception->getMessage());
                }
            }
        } finally {
            date_default_timezone_set($original);
        }
    }
}
