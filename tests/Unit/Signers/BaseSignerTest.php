<?php

namespace ArjanWestdorp\Exposable\Test\Unit\Signers;

use ArjanWestdorp\Exposable\Test\TestCase;
use ArjanWestdorp\Exposable\Signers\Signer;

class BaseSignerTest extends TestCase
{
    public function test_parameters_can_be_added_to_a_signer()
    {
        $signer = Signer::url('https://www.test.com/attachment/1')->add('expire', 'tomorrow')->add('guard', 'none');

        $this->assertEquals(2, $signer->parameters());
        $this->assertEquals('tomorrow', $signer->get('expire'));
    }

    public function test_parameters_will_be_stripped_from_a_url_on_creation()
    {
        $signer = Signer::url('https://www.test.com/attachment/1?first=one&second=two');

        $this->assertEquals(2, $signer->parameters());
        $this->assertEquals('one', $signer->get('first'));
    }

    public function test_a_parameter_can_be_removed_from_a_url()
    {
        $signer = Signer::url('https://www.test.com/attachment/1?first=one&second=two')->delete('first');

        $this->assertEquals(1, $signer->parameters());
    }

    public function test_parameters_can_be_sorted_in_a_url()
    {
        $signer = Signer::url('https://www.test.com/attachment/1?second=two&first=one')->sort();

        $this->assertEquals('https://www.test.com/attachment/1?first=one&second=two', $signer->url());
    }

    public function test_a_url_can_be_signed()
    {
        $url = Signer::url('https://www.test.com/attachment/1?first=one&second=two')->sign();

        $signed = Signer::url($url);

        $this->assertEquals(3, $signed->parameters());
        $this->assertTrue($signed->has('signature'));
    }

    public function test_a_url_can_be_validated_based_on_the_signature()
    {
        $url = Signer::url('https://www.test.com/attachment/1?first=one&second=two')->sign();

        $this->assertTrue(Signer::validate($url));
    }

    public function test_a_url_will_be_marked_as_invalid_if_there_is_something_changed_after_signing()
    {
        $url = Signer::url('https://www.test.com/attachment/1?first=one&second=two')->sign();

        $url = str_replace('attachment/1', 'attachment/2', $url);

        $this->assertFalse(Signer::validate($url));
    }

    public function test_a_url_will_be_marked_as_invalid_if_no_signature_parameter_is_given()
    {
        $this->assertFalse(Signer::validate('https://www.test.com/attachment/1'));
    }
}
