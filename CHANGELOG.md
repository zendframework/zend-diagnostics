# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

Releases prior to 1.2.0 did not have entries.

## 1.2.0 - TBD

### Added

- [#88](https://github.com/zendframework/zenddiagnostics/pull/88) adds a new `Memcached` diagnostic check.

- [#89](https://github.com/zendframework/zenddiagnostics/pull/89) adds full documentation at https://docs.zendframework.com/zend-diagnostics

- [#89](https://github.com/zendframework/zenddiagnostics/pull/89) adds support for Guzzle 6. While support was previously
  added, it included syntax that emitted deprecation notices; it now
  correctly uses the Guzzle HTTP client.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#89](https://github.com/zendframework/zenddiagnostics/pull/89) removes support for Guzzle versions 3 and 4 when using the
  Guzzle HTTP checks. Guzzle 3 has been EOL for approximately 5 years, while version
  4 is incompatible with PHP versions 7.1 and 7.2 due to a syntax issue that only
  those versions detect correctly. Since version 5 has been available for almost 4
  years, users who are on older versions should upgrade.

### Fixed

- Nothing.
