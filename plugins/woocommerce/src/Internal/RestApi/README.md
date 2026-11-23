# WooCommerce REST API (shared infrastructure)

The experimental REST API v4 (`Routes/V4/`) was removed. A few files in this directory remain and are
version-neutral — they're shared with the production wc/v1, wc/v2 and wc/v3 endpoints and must stay:

- `Refunds/`: the version-neutral refund engine used by the wc/v3 refund endpoints.
- `ProductRequestPreparationTrait.php`: used by the wc/v2 and wc/v3 product endpoints.
- `ProductImageUploadCleanupTrait.php`: used by the wc/v3 product endpoints.
- `OrderLineItemValidationTrait.php`: used by the wc/v1, wc/v2 and wc/v3 order endpoints.
- `Orders/OrderLineMetaValidator.php`: used by the wc/v2 and wc/v3 order endpoints.
- `InteractiveSessionCheck.php`: used by the wc-admin plugin and onboarding endpoints.

`Routes/V4/` now contains only no-op compatibility stubs and two `class_alias` shims, kept to avoid a
mid-upgrade fatal if an in-place update swaps files out from under a request that already resolved
one of the old v4 class names (see `src/Autoloader.php` for background on this class of issue).
