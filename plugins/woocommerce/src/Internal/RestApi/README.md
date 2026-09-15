# WooCommerce REST API (v4 and above)

**⚠️ IMPORTANT: REST API v4 is currently in development and is NOT public.**

This directory contains the implementation of WooCommerce's REST API v4, which represents a complete architectural rewrite of the REST API system.

The `Refunds/` subdirectory is the exception: it holds the version-neutral refund calculation engine (`DataUtils` and `RefundPreviewSchema`) shared by the production wc/v3 refund endpoints and the wc/v4 routes. It is not part of the experimental v4 surface and must not be removed with it.

---

**Note**: This API is not ready for production use. Do not use in production environments.
