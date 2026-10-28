# WooCommerce REST API (v4 and above)

**⚠️ IMPORTANT: REST API v4 is currently in development and is NOT public.**

This directory contains the implementation of WooCommerce's REST API v4, which represents a complete architectural rewrite of the REST API system.

Only `Routes/V4/` is the experimental v4 API. Other code in this directory is shared with the production endpoints and must stay if the v4 routes are removed:

- `Refunds/`: the version-neutral refund engine used by the wc/v3 refund endpoints and the v4 routes.
- `ProductRequestPreparationTrait.php`: used by the wc/v2 and wc/v3 product endpoints.

`Routes/V4/Orders/OrderLineMetaValidator.php` is also used by the wc/v2 and wc/v3 order endpoints. Move it out of `Routes/V4/` before removing the v4 routes.

---

**Note**: This API is not ready for production use. Do not use in production environments.
