<?php
namespace ArjanWestdorp\Exposable\Signers;

// Run only with the isolated tools/legacy dependencies, never the package autoloader.
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/BaseSigner.php';
class FixtureSigner extends BaseSigner {}
class Signer
{
    public static function url($url) { return (new FixtureSigner('exposable-legacy-fixture-key'))->url($url); }
}
$inputs = [
    'empty-fragment' => 'https://example.test/p#',
    'empty-delimiters' => 'https://example.test/p?#',
    'empty-query-fragment' => 'https://example.test/p?#f',
    'host-only' => 'https://EXAMPLE.TEST',
    'encoded-auth' => 'http://u%3aser:p%40ss@example.test:80/p?a=1',
    'ipv6' => 'http://[2001:db8::1]:8080/p?b=2&a=1',
    'reserved-keys' => 'https://example.test/p?a%26b=x&a%3Db=y&a%2Bb=z&%23=x&semi=one;two',
    'empty-pairs-only' => 'https://example.test/p?&&',
    'escaped-case' => 'https://example.test/p?x=%5b%5d%7b%7d%5e%60%7c%5c&y=%23%25%26',
    'question-in-fragment' => 'https://example.test/p?x=1#?',
    'hash-in-fragment' => 'https://example.test/p#fragment#',
    'idn' => 'https://éxample.test/é?z=1&a=2',
    'expanded-ipv6' => 'http://[2001:0db8:0000::1]/p?z=1&a=2',
    'plain' => 'https://example.test/expose/file/1',
    'ordering' => 'https://example.test/p?z=last&a=first&10=ten&2=two&01=one&-1=minus&1e2=hundred',
    'duplicates' => 'https://example.test/p?b=one&a=first&b=two&b&b=&a=last',
    'empty' => 'https://example.test/p?z=&bare&=empty&&a=1&',
    'empty-query' => 'https://example.test/p?',
    'encoding' => 'https://example.test/p?space=a+b&encoded=a%20b&literal=a%2Bb&delimiters=%26%3D%3B%2F%3F%3A%40&bracket[]=v&percent=%25',
    'unreserved' => 'https://example.test/%7e/%41/%2f?%61=%7e&x=%41%30%2D%2E%5F%7E&y=%2520&z=%zz',
    'unicode' => 'https://example.test/日本語/é?名前=値&emoji=🍺#片',
    'normalization' => 'HTTPS://EXAMPLE.TEST:443/a/../b/%2f?q=%2f%2F#f%20g',
    'port-fragment' => 'http://user:pass@EXAMPLE.TEST:8080/a%20b?z=2&a=1#frag',
    'fragment-only' => 'https://example.test/p#fragment',
    'numeric-merge' => 'https://example.test/p?5=five&2=two&name=value',
    'existing-signature' => 'https://example.test/p?signature=old&z=2&a=1',
    'duplicate-signature' => 'https://example.test/p?signature=one&signature=two&z=1',
];
// Deterministic coverage of every printable ASCII escape, independent of the
// replacement implementation. The generated cases also mix repeated keys.
for ($i = 32; $i < 127; $i++) {
    $octet = sprintf('%%%02x', $i);
    $inputs['octet-'.$i] = 'https://example.test/%7e?p='.$octet.'&'.$octet.'=v&z=2&p=last';
}
$fixtures = [];
foreach ($inputs as $name => $input) {
    $signer = Signer::url($input);
    $lookups = [];
    foreach (['a','b','bare','x','signature','%61','%25','%2d','%26','%20','%5b','%3d','%00','missing'] as $key) {
        $lookups[$key] = ['has' => $signer->has($key), 'get' => $signer->get($key)];
    }
    $normalized = $signer->url();
    $count = $signer->parameters();
    $sorted = Signer::url($input)->sort()->url();
    $added = Signer::url($input)->add('guard', 'a +/%~&')->add('expire', 1700000000)->url();
    $deleted = Signer::url($input)->delete('a')->url();
    $signed = $signer->sign();
    $fixtures[$name] = compact('input','normalized','count','lookups','sorted','added','deleted','signed');
    $fixtures[$name]['valid'] = $signer->validate($signed);
}
file_put_contents(__DIR__.'/../../tests/Fixtures/legacy-signatures.json', json_encode([
    'key' => 'exposable-legacy-fixture-key',
    'source' => 'BaseSigner at 4db4370; league/uri 5.3.0, uri-components 1.8.2, uri-manipulations 1.5.0; PHP 8.4.26',
    'fixtures' => $fixtures,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
