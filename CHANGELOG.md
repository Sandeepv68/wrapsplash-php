# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-07-23

### Added

- Initial release of WrapSplashPHP
- `WrapSplash` main API wrapper class with full Unsplash API support
- `Configuration` data class for credentials and settings
- `WrapSplashException` custom exception with HTTP status info
- Factory methods: `withBearerToken()` and `withCredentials()`
- Users APIs: public profile, portfolio, photos, liked photos, collections, statistics
- Photos APIs: list, get by ID, random, statistics, download link, update, like, unlike
- Collections APIs: list, featured, curated, CRUD operations, photo management
- Search APIs: photos, collections, users
- Current User APIs: get profile, update profile
- Stats APIs: totals, monthly
- OAuth2 bearer token generation
- `PhotoOrder` and `PhotoOrientation` enums
- PHPUnit test suite
- Comprehensive README with full API documentation
- MIT License
