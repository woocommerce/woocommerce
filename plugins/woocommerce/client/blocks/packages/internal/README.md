# WooCommerce Blocks internal packages

This directory contains package-shaped implementation details that are not
supported extension APIs.

An internal package may remain externalized to preserve a singleton or shared
runtime instance. Its script handle or browser global is an implementation
detail and does not provide a backward-compatibility guarantee.

`@woocommerce/entities` currently remains a separate script under the
`wc-entities` handle and the `wc.wcEntities` global. Extensions should not
consume it directly.

See the [public API packages](../public-api/README.md) for supported extension
contracts.
