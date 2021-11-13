# Unreleased

# 0.2.0

## Added

- Support for orders.

## Changed

- `delete()` now deletes products and coupons instead of moving to `trash`.

# 0.1.2

## Added

- Support for the external product type.
- Support for grouped product type.
- Support for variable products and product variations.
- Support for coupons.

# 0.1.1

## Breaking Changes

- The `HTTPClientFactory` API was changed to make it easier to configure instances

## Added

- `HTTPClientFactory` methods `withIndexPermalinks()` and `withoutIndexPermalinks()` to enable/disable API pretty permalinks
- Expanded properties of `AbstractProduct` model type
- Added `list`, `read`, `update`, and `delete` operations for `SimpleProduct` repositories

## Changes

- Added a transformation layer between API responses and internal models

## Fixed

- issues that caused the factory creation to fail for SimpleProduct types
- a bug with OAuth signature generation when using query parameters

# 0.1.0

- Initial/beta release
