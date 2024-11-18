# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2024-11-18
### Removed
- General: Update minimum PHP version to 7.2. [#40147]

## [0.1.13] - 2024-11-04
### Added
- Enable test coverage. [#39961]

## [0.1.12] - 2024-10-29
### Changed
- Internal updates.

## [0.1.11] - 2024-09-23
### Changed
- Update dependencies.

## [0.1.10] - 2024-09-10
### Fixed
- Check whether `\WC_Install::STORE_ID_OPTION` is defined before attempting to use it, for compatibility with WooCommerce <8.4.0. [#39306]

## [0.1.9] - 2024-09-09
### Added
- Add Store ID property in common woocommerce analytics  properties. [#38857]

## [0.1.8] - 2024-08-26
### Changed
- Updated package dependencies. [#39004]

## [0.1.7] - 2024-06-26
### Removed
- Remove use of `gutenberg_get_block_template()`. Its replacement has been in WP Core since 5.8. [#38015]

## [0.1.6] - 2024-05-20
### Fixed
- Customer creation: avoid PHP warnings when other plugins hook into customer creation process and return malformed user data. [#37440]

## [0.1.5] - 2024-05-06
### Changed
- Ensure the package can only be initialized once. [#37154]

## [0.1.4] - 2024-04-15
### Changed
- Internal updates.

## [0.1.3] - 2024-04-08
### Fixed
- Fixed a JavaScript error when accessing the Shortcode checkout with WooCommerce Analytics enable. [#36560]

## [0.1.2] - 2024-03-25
### Changed
- Internal updates.

## [0.1.1] - 2024-03-18
### Changed
- Internal updates.

## 0.1.0 - 2024-03-12
### Added
- General: add main classes to the package. [#35756]
- Initial version. [#35754]

### Fixed
- Avoid any issues when the package is loaded in an mu-plugin. [#36287]
- Fix namespace issue with WooCommerce class reference. [#35857]
- General: bail early when WooCommerce is not active. [#36278]

[0.2.0]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.13...v0.2.0
[0.1.13]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.12...v0.1.13
[0.1.12]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.11...v0.1.12
[0.1.11]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.10...v0.1.11
[0.1.10]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.9...v0.1.10
[0.1.9]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.8...v0.1.9
[0.1.8]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.7...v0.1.8
[0.1.7]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.6...v0.1.7
[0.1.6]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.5...v0.1.6
[0.1.5]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.4...v0.1.5
[0.1.4]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/Automattic/woocommerce-analytics/compare/v0.1.0...v0.1.1
