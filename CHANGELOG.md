Changelog
=========

## 1.0.11 - 2017-03-07

### Fixed
- Fixed a bug where the PHP memory limit check would fail if `memory_limit` was set to `-1`, which means no limit. 

## 1.0.10 - 2017-02-21

### Fixed
- Fixed a bug where the requirements checker would error if the Craft project lived at the root of the file system.

## 1.0.9 - 2017-02-13

### Added
- Added the PHP `password_hash()` function as a mandatory requirement.
- Added the PHP Zip extension as a mandatory requirement.

## 1.0.8 - 2017-02-06

### Changed
- Tweaked keywords in composer.json 

### Fixed
- Fixed a bug where the default database port wasn’t being accounted for when connecting to the database.

## 1.0.7 - 2017-01-30

### Changed
- Updated the `support` properties in composer.json
- Craft 3 no longer requires `mcrypt`.

## 1.0.6 - 2017-01-17

### Added
- Added support for configuring the database port

## 1.0.5 - 2017-01-17

### Fixed
- Fixed a bug where `checkIniSet()` wasn’t undoing a change it made to PHP’s `memory_limit` setting.

## 1.0.4 - 2017-01-17

### Changed
- Craft 3 now requires PHP 7

## 1.0.3 - 2017-01-17

### Changed
- Updated code for latest Craft coding guidelines

### Removed
- Removed check for buggy `iconv` extension

## 1.0.2 - 2016-12-30

### Changed
- Autoloading support in composer.json

## 1.0.1 - 2016-12-30

### Changed
- No longer specifying a minimum stability in composer.json
- MIT license in composer.json

## 1.0.0 - 2016-12-30

Initial release.
