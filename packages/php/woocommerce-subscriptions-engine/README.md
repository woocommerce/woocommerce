# WooCommerce Subscriptions Engine

This package implements a general-purpose subscriptions engine for WooCommerce.

The engine records subscription facts (plans, contracts, billing cycles) and offers tools to work with them; extensions own every flow and decision. In particular:

- **Due dispatch:** an extension registers a contract due handler with its slug, and the engine calls it when one of its contracts reaches its next due moment. What happens then (renew, skip, clear the due moment) is the extension's decision.
- **Cycle tools:** `Api\Contracts` offers claimed cycle appends, cycle claims and compare-and-set status transitions, so an extension can run its billing flow safely under concurrency.
- **Payments broker:** `Api\Payments` lets an extension ask the order's gateway to charge an order off-session. Gateways opt in by adding the `subscriptions_engine` feature to their `$supports` and charging on `woocommerce_subscriptions_engine_scheduled_payment_{gateway_id}`, reporting the outcome through the order status.
