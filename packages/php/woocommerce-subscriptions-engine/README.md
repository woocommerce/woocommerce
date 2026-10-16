# WooCommerce Subscriptions Engine

This package implements a general-purpose subscriptions engine for WooCommerce.

The engine records subscription facts (plans, contracts, billing cycles) and offers tools to work with them; extensions own every flow and decision. In particular:

- **Due dispatch:** an extension registers a contract due handler with its slug, and the engine calls it when one of its contracts reaches its next due moment, passing the dispatcher's contract lock. What happens then (renew, skip, clear the due moment) is the extension's decision.
- **Contract lock:** one lock per contract on the facade. `Contracts::lock()` takes it for an operation (null when another operation holds it) and `Contracts::create()` creates every contract locked from birth (null when its optional creation key is taken); every write takes the lock as its last parameter, and cycle writes require it. An update or meta write without the lock is refused with `ContractLockedException` while another operation holds it.
- **Cycle tools:** cycle appends are append-if-absent on the chain's positions, and status transitions are compare-and-set.
- **Payments broker:** `Api\Payments` lets an extension ask the order's gateway to take a scheduled payment off-session (`Payments::process_scheduled_payment( $order )`). Gateways opt in by adding the `subscriptions_engine` feature to their `$supports` and charging the order on `woocommerce_subscriptions_engine_scheduled_payment_{gateway_id}( $order )`, reporting the outcome through the order status. What a gateway can do beyond that (renewal payments, payment method changes, ...) it declares with the existing WooCommerce gateway features; extensions check the ones an operation needs with `Payments::gateway_supports( $gateway_id, $features )`, and the engine does not interpret them.
