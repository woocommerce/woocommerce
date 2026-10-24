---
post_title: 'Dual API (code + GraphQL)'
sidebar_label: 'Dual API'
sidebar_position: 0
---

# WooCommerce Dual API

The **dual API** is a code-first API architecture: you write plain PHP classes (the **code API**), and a build script generates a fully functional **GraphQL API** that mirrors them.

The dual API was introduced as an experimental feature in WooCommerce core 10.9, but as of WooCommerce 11.2 its engine has moved to a dedicated plugin, [WooCommerce Dual API](https://github.com/woocommerce/woocommerce-dual-api), which any plugin can use to build its own dual API.

> **This feature is experimental.** Everything under the `Automattic\WooCommerce\Api` namespace can change in backwards-incompatible ways, or be removed, in any release. Do not use it in production extensions.

## Where the documentation lives

The engine and its documentation live in the plugin's repository:

- **Plugin**: [woocommerce/woocommerce-dual-api](https://github.com/woocommerce/woocommerce-dual-api)
- **Documentation**: the [`docs/` directory](https://github.com/woocommerce/woocommerce-dual-api/tree/trunk/docs) of that repository
- **Working example**: [woocommerce/woocommerce-simple-events](https://github.com/woocommerce/woocommerce-simple-events), a runnable reference plugin that exercises the engine end to end

## Requirements

WooCommerce 11.2 or newer on PHP 8.1+, with the WooCommerce Dual API plugin installed and active. There is no feature flag: activating the plugin enables the engine.

WooCommerce 10.9 to 11.1 ship the engine inside core, behind a hidden `dual_code_graphql_api` feature flag (`wp option update woocommerce_feature_dual_code_graphql_api_enabled yes` to enable it). The plugin stays dormant on those versions and the flag remains the switch there.
