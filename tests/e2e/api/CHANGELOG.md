# Unreleased

# 0.1.1

## Added

- Support for the external product type.

## Breaking Changes

- The `HTTPClientFactory` API was changed to make it easier to configure instances with

## Added

- `HTTPClientFactory` methods `withIndexPermalinks()` and `withoutIndexPermalinks()` to enable/disable API pretty permalinks
- Expanded properties of `AbstractProduct` model type
- Added `list`, `read`, `update`, and `delete` operations for `SimpleProduct` repositories

## Changes

- Added a tranformation layer between API responses and internal models

## Fixed

- issues that caused the factory creation to fail for SimpleProduct types
- a bug with OAuth signature generation when using query parameters

# 0.1.0

- Initial/beta release
