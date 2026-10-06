<?php

namespace ArjanWestdorp\Exposable\Test\Unit\Signers;

use ArjanWestdorp\Exposable\Signers\ExposableSigner;
use ArjanWestdorp\Exposable\Test\TestCase;

class LegacySignaturesTest extends TestCase
{
    public function test_original_signer_fixtures_match_byte_for_byte()
    {
        $data = json_decode(file_get_contents(__DIR__.'/../../Fixtures/legacy-signatures.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->config('key', $data['key']);
        foreach ($data['fixtures'] as $name => $fixture) {
            $new = fn () => (new ExposableSigner($data['key']))->url($fixture['input']);
            $this->assertSame($fixture['normalized'], $new()->url(), $name.' normalization');
            $this->assertSame($fixture['count'], $new()->parameters(), $name.' count');
            foreach ($fixture['lookups'] as $key => $lookup) {
                $this->assertSame($lookup['has'], $new()->has($key), $name.' has '.$key);
                $this->assertSame($lookup['get'], $new()->get($key), $name.' get '.$key);
            }
            $this->assertSame($fixture['sorted'], $new()->sort()->url(), $name.' sort');
            $this->assertSame($fixture['added'], $new()->add('guard', 'a +/%~&')->add('expire', 1700000000)->url(), $name.' add');
            $this->assertSame($fixture['deleted'], $new()->delete('a')->url(), $name.' delete');
            $this->assertSame($fixture['signed'], $new()->sign(), $name.' sign');
            $this->assertSame($fixture['valid'], $new()->validate($fixture['signed']), $name.' validate');
        }
    }
}
