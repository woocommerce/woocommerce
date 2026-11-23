# WooCommerce REST API (shared infrastructure)

The experimental REST API v4 (`Routes/V4/`) was removed. A few files in this directory remain
because they are version-neutral: they're shared with the production wc/v1, wc/v2 and wc/v3
endpoints and must stay.

- `Refunds/`: the refund engine used by the wc/v3 refund endpoints.
- `ProductRequestPreparationTrait.php`: used by the wc/v2 and wc/v3 product endpoints.
- `ProductImageUploadCleanupTrait.php`: used by the wc/v3 product endpoints.
- `OrderLineItemValidationTrait.php`: used by the wc/v1, wc/v2 and wc/v3 order endpoints.
- `Orders/OrderLineMetaValidator.php`: used by the wc/v2 and wc/v3 order endpoints.
- `InteractiveSessionCheck.php`: used by the wc-admin plugin and onboarding endpoints.

`Routes/V4/` now holds only compatibility aliases, kept to avoid a mid-upgrade fatal if an
in-place update swaps files out from under a request that already resolved an old v4 class name
(see `src/Autoloader.php` for background on this class of issue). `NoOpStub.php` is the one real
class; every removed v4 controller name is aliased to it with `class_alias()`, and
`Orders/OrderLineMetaValidator.php` and `ShippingZoneMethod/ShippingZoneMethodService.php` alias
to the real classes that moved elsewhere in this directory. All of them are deprecated since
11.5.0 and will be removed in a future version.
