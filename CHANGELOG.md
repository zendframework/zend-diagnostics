# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

Releases prior to 1.2.0 did not have entries.

## 1.6.0 - 2019-11-22

### Added

- [#101](https://github.com/zendframework/zend-diagnostics/pull/101) adds compatibility with symfony/yaml `^5.0`.

- [#101](https://github.com/zendframework/zend-diagnostics/pull/101) adds compatibility with sensiolabs/security-checker `^6.0`.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.5.0 - 2019-03-26

### Added

- [#97](https://github.com/zendframework/zenddiagnostics/pull/97) adds support for doctrine/migrations v2 releases.

- [#96](https://github.com/zendframework/zenddiagnostics/pull/96) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.4.0 - 2019-01-09

### Added

- Nothing.

### Changed

- [#95](https://github.com/zendframework/zenddiagnostics/pull/95) changes the minimum supported version of sensiolabs/security-checker from 1.3 to 5.0.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.3.1 - 2018-09-17

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#94](https://github.com/zendframework/zenddiagnostics/pull/94) updates the
  `AbstractResult::$message` property to default to an empty string instead of
  `null`. Since both `ResultInterface` and `AbstractResult` document that the
  return type for `getMessage()` is a string, and all reporters expect a string,
  this ensures type safety for the method.

## 1.3.0 - 2018-07-30

### Added

- [#93](https://github.com/zendframework/zenddiagnostics/pull/93) adds compatibility for apcu

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.0 - 2018-06-25

### Added

- [#88](https://github.com/zendframework/zenddiagnostics/pull/88) adds a new `Memcached` diagnostic check.

- [#89](https://github.com/zendframework/zenddiagnostics/pull/89) adds full documentation at https://docs.zendframework.com/zend-diagnostics

- [#89](https://github.com/zendframework/zenddiagnostics/pull/89) adds support for Guzzle 6. While support was previously
  added, it included syntax that emitted deprecation notices; it now
  correctly uses the Guzzle HTTP client.

### Changed

- [#90](https://github.com/zendframework/zenddiagnostics/pull/90) modifies what types are allowed for the `GuzzleHttpService` initial constructor
  argument. Previously, it only allowed a URL; it now allow any valid request instance the Guzzle client
  can accept. This change allows you to craft a custom request to send.

- [#90](https://github.com/zendframework/zenddiagnostics/pull/90) modifies the behavior of `GuzzleHttpService` slightly in relation to how
  it handles its `$body` argument. It now allows stream instances, any object implementing `__toString()`,
  any iterator objects, any `JsonSerializable` objects, and strings and arrays. In the latter case, it
  checks to see if the request `Content-Type` is JSON, casting the value to JSON if so, and otherwise
  serializing it as form-encoded data.

### Deprecated

- Nothing.

### Removed

- [#89](https://github.com/zendframework/zenddiagnostics/pull/89) removes support for Guzzle versions 3 and 4 when using the
  Guzzle HTTP checks. Guzzle 3 has been EOL for approximately 5 years, while version
  4 is incompatible with PHP versions 7.1 and 7.2 due to a syntax issue that only
  those versions detect correctly. Since version 5 has been available for almost 4
  years, users who are on older versions should upgrade.

### Fixed

- [#92](https://github.com/zendframework/zenddiagnostics/pull/92) fixes how the `ProcessRunning` diagnostic works when given
  a process name, but the current window is too small to display it (a problem
  that only occurs on some operating systems).

- [#80](https://github.com/zendframework/zenddiagnostics/pull/80) fixes how the `MongoDB\Client` instance is created when using ext-mongodb + mongodb/mongodb,
  ensuring it uses the provided connection URI.
