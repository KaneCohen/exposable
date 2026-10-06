<?php

namespace ArjanWestdorp\Exposable\Test\Unit\Signers;

use ArjanWestdorp\Exposable\Signers\ExposableSigner;
use ArjanWestdorp\Exposable\Signers\Signer;
use ArjanWestdorp\Exposable\Test\TestCase;

class SignerContractsTest extends TestCase
{
    public function test_invalid_signatures_are_rejected_without_type_errors()
    {
        $signer = new ExposableSigner(config('exposable.key'));
        foreach (['', '?signature', '?signature=', '?signature=invalid', '?signature[]=invalid', '?signature=one&signature=two'] as $query) {
            $this->assertFalse($signer->validate('https://example.test/p'.$query));
        }
    }

    public function test_signature_removal_preserves_query_order_and_signer_state()
    {
        $url = Signer::url('https://example.test/p?b=two&a=one')->sign();
        $this->assertTrue(Signer::validate($url));
        $this->assertFalse(Signer::validate(str_replace('a=one&b=two', 'b=two&a=one', $url)));
        $this->assertFalse(Signer::validate(str_replace('a=one', 'a=changed', $url)));
        $this->assertFalse(Signer::validate(str_replace('example.test', 'other.test', $url)));
        $this->assertFalse((new ExposableSigner('another-synthetic-key'))->validate($url));

        $signer = Signer::url('https://example.test/current?guard=authenticated');
        $before = $signer->url();
        $this->assertTrue($signer->validate($url));
        $this->assertSame($before, $signer->url());
    }

    public function test_multiple_fluent_operations_do_not_leak_state()
    {
        $first = Signer::url('https://example.test/one')->guard('authenticated')->expire(5)->sign();
        $second = Signer::url('https://example.test/two')->guard(null)->expire(null)->sign();
        $this->assertTrue(Signer::validate($first));
        $this->assertTrue(Signer::validate($second));
        $this->assertFalse(Signer::url($second)->has('guard'));
        $this->assertFalse(Signer::url($second)->has('expire'));

        $instance = new ExposableSigner(config('exposable.key'));
        $instance->url('https://example.test/one?#')->guard('authenticated')->sign();
        $this->assertSame($second, $instance->url('https://example.test/two')->sign());
    }

    public function test_replacement_and_removal_apply_to_every_duplicate()
    {
        $signer = Signer::url('https://example.test/p?a=first&b=two&a=last&a');
        $this->assertSame(['first', 'last', null], $signer->get('a'));
        $this->assertSame(2, $signer->parameters());
        $this->assertSame('https://example.test/p?a=new&b=two', $signer->add('a', 'new')->url());
        $this->assertSame('https://example.test/p?b=two', $signer->delete('a')->url());
    }
}
