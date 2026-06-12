# WooCommerce Subscriptions Engine

A subscriptions engine for WooCommerce: selling plans, subscription contracts, and renewal machinery, packaged so that WooCommerce itself and extensions can build subscription experiences on one shared foundation.

> **Status: scaffold.** No functional code yet; names, APIs, and layout are provisional and will change without notice. Functional code lands in follow-up pull requests.

## What this package will contain

- The engine core: subscription entities (plans, contracts, billing cycles, payment attempts), policies, and renewal scheduling logic, written to be host-agnostic.
- An integration layer binding the core to WordPress and WooCommerce: storage, scheduling via Action Scheduler, order creation, hooks, and REST surfaces.

The package is consumed as a composer dependency (`automattic/woocommerce-subscriptions-engine`) by plugins that provide subscription experiences on top of it. It is intentionally typed `library` so consumer installs vendor it rather than relocating it.

## Structure

```
src/        Package code, PSR-4: Automattic\WooCommerce\SubscriptionsEngine\
changelog/  Changelogger entries (one file per change)
```

Build tooling, tests, and CI configuration arrive together with the first functional code.

## License

GPL-2.0-or-later. See [license.txt](license.txt).
