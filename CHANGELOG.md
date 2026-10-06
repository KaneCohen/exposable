# 3.0.0 - 2026-10-06

- Target PHP 8.4 and the tested Laravel 10.50.3, 11.57.0, 12.69.3 and 13.34.0
  branches. Laravel 10/PHP 8.4 remains a migration checkpoint.
- Require maintained, tagged League URI and URI Components 7.8.1 or later.
  Preserve legacy HMAC-SHA256 signed URL bytes and verification with 122
  pre-upgrade golden cases, including duplicate keys and SORT_REGULAR ordering.
- Support Carbon 2.73 and Carbon 3.8.4 or later on applicable frameworks;
  remove Carbon 1 and older PHP/Laravel support.
- Fix PHP 8.4 nullable exception declarations and replace `array_get()` with
  `Illuminate\Support\Arr::get()`.
- Use configured model defaults without missing-property reads. Store setter
  overrides per object outside Eloquent attributes, retaining declared nulls
  and model values without declaring conflicting trait properties.
- Reject duplicate signatures as invalid without a type error and validate
  without mutating the active signer.
- Modernize Testbench, Mockery and PHPUnit; add a reproducible E_ALL matrix,
  integration tests, recorded results and GitHub Actions.

No signing-key rotation, Laravel signed URL migration, CMS changes, vendor
patches, SPX configuration changes or production deployment are included.
See [the tested compatibility matrix and checkpoint limitations](docs/COMPATIBILITY.md).

# 1.0.0 - 2017-05-02

- Initial release
