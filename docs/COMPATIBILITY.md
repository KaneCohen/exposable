# Exposable 3.0.0 compatibility

This release targets PHP **8.4.x**. Its framework constraints start at the
versions actually exercised: `^10.50.3 || ^11.57.0 || ^12.69.3 || ^13.34.0`.
PHP 8.5, Laravel 5–9, earlier Laravel 11–13 releases and Carbon 1 are outside
this release's tested scope.

## Verified matrix

Executed on 2026-10-06 with PHP **8.4.26**, `error_reporting=E_ALL`, real SQLite
migrations, HTTP middleware and Testbench applications. Every row passed
**53 tests / 4,430 assertions**. League URI and URI Components were both
**7.8.1**; Mockery was **1.6.15**.

| Laravel | Carbon | Testbench | PHPUnit | Result |
| --- | --- | --- | --- | --- |
| 10.50.3 | 2.73.0 | 8.39.0 | 10.5.66 | Migration checkpoint; passed |
| 11.57.0 | 2.73.0 | 9.18.0 | 11.5.57 | Passed |
| 11.57.0 | 3.8.4 | 9.18.0 | 11.5.57 | Minimum Carbon 3; passed |
| 11.57.0 | 3.14.2 | 9.18.0 | 11.5.57 | Passed |
| 12.69.3 | 3.14.2 | 10.12.0 | 11.5.57 | Passed |
| 13.34.0 | 3.14.2 | 11.3.0 | 11.5.57 | Passed |

Carbon 2 is supported on Laravel 10 and 11. The tested Laravel 12 and 13
releases require Carbon 3. The package allows `^2.73.0 || ^3.8.4`; both the
minimum and current tested Carbon 3 versions exercise the same expiration
suite. Relative strings, integer minutes, Carbon instances, DST/timezones,
exact expiration seconds and subsecond boundaries are covered. Expiration
continues to use the existing Carbon `modify()` and `isPast()` semantics.

The minimum League constraints are `^7.8.1` for both packages. These are tagged,
tested releases, with stable `Http::new()`, `Query::new()`, `fromPairs()`,
`has()`, `getAll()` and `withoutPairByKey()` APIs. Exposable explicitly requires
URI Components rather than relying on a transitive installation.

## Framework checkpoint limitations

Laravel 10/PHP 8.4 is a migration checkpoint, not an officially supported
production pairing. Laravel 10's published PHP range is 8.1–8.3, and Laravel
10 and 11 are past their published security support dates as of this test
run. A package test pass does not extend the framework's support policy.
See the [Laravel support policy](https://laravel.com/docs/10.x/releases#support-policy).

Historical Laravel 10/11 test installations use Composer's explicit
`--no-security-blocking` option in isolated temporary projects. Their Composer
logs and locks retain the resolved dependency/advisory information. The
package's Composer configuration does not disable security blocking for
consumers. Supported-current rows resolve with normal Composer security
blocking enabled.

There were **zero package-origin warnings or deprecations**. The separate,
unfiltered upstream runs also reported **zero deprecations, warnings or
notices on the paths exercised**, including the Laravel 10 checkpoint.
This is evidence for the tested package/application paths, not for every
Laravel subsystem. No installed vendor files were patched. Test commands in
the development environment used a temporary CLI ini without the unavailable
SPX extension; the system SPX configuration was left untouched.

## Signed-link compatibility

`tests/Fixtures/legacy-signatures.json` contains **122 golden cases** generated
before editing the signer, using the original `BaseSigner` from commit
`4db4370`, URI 5.3.0, URI Components 1.8.2 and URI Manipulations 1.5.0. The key
is the public synthetic string `exposable-legacy-fixture-key`; no production
keys or configuration were read. The isolated original implementation and
locked dependencies are retained in `tools/legacy/` for reproduction.

Fixtures assert URI normalization, distinct parameter count, presence and
lookup (including duplicate arrays), sorted/merged/deleted query bytes, exact
signed URLs and their original verification results. Coverage includes every
printable ASCII percent escape, Unicode, IDN, escaped paths, user information,
ports, IPv6, fragments, bare/empty parameters, empty delimiters, numeric keys
and duplicate signatures.

The signing algorithm remains hexadecimal HMAC-SHA256 using the unmodified
`exposable.key` configuration. The internal adapter preserves the old query
wire format: literal plus signs, preserved unreserved escapes, grouped
duplicate values, associative merges (including numeric-key renumbering),
empty-pair removal on merge and PHP `ksort(..., SORT_REGULAR)`. Verification
removes all signature pairs and does **not** reorder the remaining query.
Existing valid fixture links still verify.

The old signer already produced self-invalid URLs for some numeric-key and
unnamed-empty-pair inputs, and when signing URLs that already carried a
signature. Those exact outputs and false verification results remain fixtures;
this upgrade does not silently change their format. Duplicate signatures now
return `false` instead of provoking `hash_equals()`'s array type error.
Validation also leaves the active signer untouched.

See the [League URI upgrade guide](https://uri.thephpleague.com/uri/7.0/upgrading/)
and [query API](https://uri.thephpleague.com/components/7.0/query/). Their modern
encoding, first-value lookup and sorting are not interchangeable with the
legacy signing format.

## Model settings and public contracts

The model trait uses configured defaults when `exposableGuard` or
`exposableLifetime` is absent. A declared null remains null; a declared value,
including lifetime zero, remains authoritative. Setters override that instance
through private internal storage, avoiding dynamic properties and Eloquent
database attributes. The trait does not declare `exposableGuard` or
`exposableLifetime`, so consuming models can keep their existing declarations. Runtime overrides are not persisted to the database. Clones inherit them,
then can override them independently, preserving the existing clone behavior.

Namespaces, exception classes, facade, `exposable.signer`, config keys,
fluent signer calls, model methods, route names, custom routes and middleware
aliases remain intact. `Arr::get()` replaces removed `array_get()` calls.
The unauthorized exception uses explicit `?Guard` nullability.

## Reproduce

```sh
composer update
composer test
# Full matrix: each row gets its own temporary project, vendor tree and lock.
php -d error_reporting=32767 tools/test-matrix.php
# Or a single row:
php -d error_reporting=32767 tools/test-matrix.php laravel10-carbon2
```

The runner prints its artifact directory and retains each row's Composer
manifest/lock, package test log, full upstream test log, JUnit report and exact
versions. `EXPOSABLE_MATRIX_DIR` selects an artifact directory and
`EXPOSABLE_COMPOSER` can select a Composer executable. Existing row directories
are never overwritten. Composer 2.10 was used for the recorded run; historical
rows need a Composer version supporting `--no-security-blocking`.

`composer test` selects the checked-in PHPUnit 10 or 11 configuration. Both run
under E_ALL and fail on package deprecations and all warnings. The matrix also
runs a configuration without dependency-deprecation filtering and retains that
separate upstream report. The bootstrap compiles all package declarations
under E_ALL, so PHP 8.4 implicit nullability cannot escape the runtime tests.

Recorded results and logs are in [verification](verification/). GitHub Actions
runs the same matrix and uploads complete dependency locks and reports.

To recapture legacy fixtures, install only the isolated locked dependencies:

```sh
composer install --working-dir=tools/legacy
php -d error_reporting=32767 tools/legacy/capture.php
```

The abandoned legacy dependencies emit their original PHP 8.4 deprecations
during fixture capture. They are test provenance only and are not dependencies
of the release. Do not regenerate golden data with the replacement signer.

## Release handoff

The compatible release tag is **3.0.0**. Keep `exposable.key` unchanged when
upgrading so existing links continue to verify. The CMS dependency/lock change
and deployment are separate tasks. Point its fork repository dependency at
`3.0.0` once that tag is available remotely, then regenerate and test its lock.
