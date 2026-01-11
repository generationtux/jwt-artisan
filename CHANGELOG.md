# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1-beta] - 2026-01-11

### Added
- Support for Laravel 12
- Multi-PHP version docker development setup (PHP 8.2, 8.3, 8.4)
- CI workflow now tests all PHP/Laravel version combinations (9 matrix jobs)

### Changed
- Updated PHP requirement from `^8.1` to `^8.2`
- Updated Laravel/Illuminate requirement to `^10.0|^11.0|^12.0`
- Regenerated composer.lock with PHP 8.2 platform constraint for broader compatibility

### Fixed
- PHP 8.2 and 8.3 build failures caused by doctrine/instantiator 2.1.0 requiring PHP 8.4

## [2.0.0-beta] - 2024-XX-XX

### Changed
- Updated for Laravel 10 compatibility
- Updated PHP requirement to ^8.1
- Updated firebase/php-jwt to ^6.0

## [1.x] - Legacy

Previous versions supporting Laravel 5-9 and PHP 7.x-8.0.
