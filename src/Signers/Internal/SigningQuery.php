<?php

namespace ArjanWestdorp\Exposable\Signers\Internal;

use League\Uri\Components\Query;

/**
 * Legacy encoding derived from league/uri-components 1.8.2 (MIT).
 * Copyright (c) 2016 ignace nyamagana butera; see THIRD_PARTY_NOTICES.md.
 *
 * The wire format of existing signatures. League 7 handles query parsing and
 * tuples; this adapter retains League 1's grouping and RFC3986 serialization.
 *
 * @internal
 */
final class SigningQuery
{
    private function __construct(private Query $query)
    {
    }

    public static function new(string $query): self
    {
        // League 1 deliberately kept escaped unreserved octets as escapes.
        // Protect those octets from League 7's full percent decoding.
        $query = preg_replace_callback('/%[A-Fa-f0-9]{2}/', static function ($match) {
            if (self::keepsEscape($match[0])) {
                return '%25'.substr(strtoupper($match[0]), 1);
            }

            return $match[0];
        }, $query);

        return new self(Query::new('' === $query ? null : $query));
    }

    private static function keepsEscape(string $octet): bool
    {
        // Retain the legacy decoder's exact treatment of percent escapes.
        return (bool) preg_match(',%2[D|E]|3[0-9]|4[1-9|A-F]|5[0-9|A|F]|6[1-9|A-F]|7[0-9|E],i', $octet);
    }

    private static function lookupKey($key): string
    {
        return preg_replace_callback('/%[A-Fa-f0-9]{2}/', static function ($match) {
            // Legacy lookups additionally preserved escaped percent signs.
            return self::keepsEscape($match[0]) || strcasecmp($match[0], '%25') === 0
                ? strtoupper($match[0]) : rawurldecode($match[0]);
        }, (string) $key);
    }

    public function has($key): bool
    {
        return $this->query->has(self::lookupKey($key));
    }

    public function get($key)
    {
        $values = $this->query->getAll(self::lookupKey($key));

        return count($values) > 1 ? $values : ($values[0] ?? null);
    }

    public function count(): int
    {
        return count($this->groups());
    }

    private function groups(): array
    {
        $groups = [];
        foreach ($this->query as [$key, $value]) {
            $groups[$key][] = $value;
        }

        return $groups;
    }

    private static function fromGroups(array $groups): self
    {
        $pairs = [];
        foreach ($groups as $key => $values) {
            foreach ($values as $value) {
                $pairs[] = [(string) $key, $value];
            }
        }

        return new self(Query::fromPairs($pairs));
    }

    public function sort(): self
    {
        $groups = $this->groups();
        ksort($groups, SORT_REGULAR);

        return self::fromGroups($groups);
    }

    public function add($key, $value): self
    {
        // The old associative merge replaced duplicate values and renumbered
        // integer keys. Empty unnamed bare pairs were discarded on merge.
        $new = self::new(self::encode($key).(null === $value ? '' : '='.self::encode($value)));
        $baseGroups = self::withoutEmpty($this->groups());
        $newGroups = self::withoutEmpty($new->groups());
        if ($baseGroups === $newGroups) {
            return $this;
        }

        return self::fromGroups(array_merge($baseGroups, $newGroups));
    }

    private static function withoutEmpty(array $groups): array
    {
        if (isset($groups[''])) {
            $groups[''] = array_values(array_filter($groups[''], static fn ($value) => null !== $value));
            if ([] === $groups['']) {
                unset($groups['']);
            }
        }

        return $groups;
    }

    public function delete($key): self
    {
        return new self($this->query->withoutPairByKey(self::lookupKey($key)));
    }

    private static function encode($value): string
    {
        // RFC3986 query syntax permits these reserved characters literally.
        // Only '&' separates pairs; '+' has never represented a space here.
        $pattern = '/(%[A-Fa-f0-9]{2})|[^A-Za-z0-9_\-\.~'.preg_quote("!$'()*+,;=:@?/%", '/').']+/u';

        return preg_replace_callback($pattern, static function ($match) {
            if (preg_match('/^[A-Za-z0-9_\-\.~]$/', rawurldecode($match[0]))) {
                return $match[0];
            }

            return rawurlencode($match[0]);
        }, (string) $value) ?? rawurlencode((string) $value);
    }

    public function __toString(): string
    {
        $pairs = [];
        foreach ($this->groups() as $key => $values) {
            foreach ($values as $value) {
                $pairs[] = self::encode($key).(null === $value ? '' : '='.self::encode($value));
            }
        }

        return implode('&', $pairs);
    }
}
