<?php

namespace ArjanWestdorp\Exposable\Test\Unit\Signers;

use Carbon\Carbon;
use ArjanWestdorp\Exposable\Test\TestCase;
use ArjanWestdorp\Exposable\Signers\Signer;

class ExposableSignerTest extends TestCase
{
    public function test_a_guard_can_be_added_to_a_url()
    {
        $signer = Signer::url('https://www.test.com/attachment/1?second=two&first=one')->guard('auth');

        $this->assertEquals(3, $signer->parameters());
        $this->assertTrue($signer->has('guard'));
    }

    public function test_an_expire_time_will_be_in_minutes_by_default()
    {
        $signer = Signer::url('https://www.test.com/attachment/1?second=two&first=one')->expire(10);

        $timestamp = Carbon::now()->modify('10 minutes')->timestamp;

        $this->assertEquals($timestamp, $signer->get('expire'));
    }

    public function test_an_expire_time_can_be_in_any_modify_format()
    {
        $signer = Signer::url('https://www.test.com/attachment/1?second=two&first=one')->expire('2 days');

        $timestamp = Carbon::now()->modify('2 days')->timestamp;

        $this->assertEquals($timestamp, $signer->get('expire'));
    }

    public function test_a_carbon_instance_can_be_used_as_expire()
    {
        $expire = Carbon::now()->addMinutes(20);

        $signer = Signer::url('https://www.test.com/attachment/1?second=two&first=one')->expire($expire);

        $this->assertEquals($expire->timestamp, $signer->get('expire'));
    }

    public function test_a_guard_and_expire_will_not_be_added_when_null()
    {
        $url = 'https://www.test.com/attachment/1?second=two&first=one';

        $signer = Signer::url($url)->expire(null)->guard(null);

        $this->assertEquals($url, $signer->url());
        $this->assertFalse($signer->has('guard'));
        $this->assertFalse($signer->has('expire'));
    }
}
