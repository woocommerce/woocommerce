# Core concepts

This section covers the fundamental principles, best practices, and essential knowledge you need to develop robust, maintainable WooCommerce extensions.

Learn about everything from basic setup and architecture to advanced development patterns. Whether you're building your first WooCommerce extension or maintaining existing ones, these guides will help you follow best practices and create high-quality code.

## Getting started

[Check if WooCommerce is active](./check-if-woo-is-active.md) to learn the proper way to ensure WooCommerce is installed and active before your code runs. This prevents errors and ensures your extension works reliably. You'll also want to understand the [core WooCommerce classes](./class-reference.md) and how to work with them, from the main `WooCommerce` class to `WC_Product`, `WC_Customer`, and `WC_Cart`.

## Internal code boundaries

WooCommerce contains both public APIs intended for extension use and internal infrastructure code. **Extensions must not use internal code**, as backwards compatibility between WooCommerce releases is not guaranteed:

- **Internal namespace**: All classes under `Automattic\WooCommerce\Internal` are internal infrastructure.
- **@internal annotations**: Code entities (classes, methods, hooks) marked with `@internal` in their docblocks.

Using internal code may cause your extension to break when merchants update WooCommerce. See the [extension development best practices](../best-practices-extensions/extension-development-best-practices.md) and the [Internal namespace documentation](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/README.md) for details.

## Development patterns

[Adding actions and filters](./adding-actions-and-filters.md) to master the art of extending WooCommerce through hooks. Learn when and how to add actions and filters, following WordPress and WooCommerce standards. For long-term success, discover strategies for [writing maintainable code](./maintainability.md) and establishing update processes that keep your extensions current and secure. You'll also need to [manage deactivation and uninstallation](./handling-deactivation-and-uninstallation.md) to ensure your extension cleans up properly when deactivated or uninstalled, including scheduled actions, admin notes, and tasks.

## Plugin structure and standards

See the [example header plugin comment](./example-header-plugin-comment.md) format for your extension's main plugin file header, including all required metadata. You'll also want to learn the standard [changelog format](./changelog-txt.md) for documenting changes in your extension's changelog file, and understand the [WooCommerce plugin API callback](./woocommerce-plugin-api-callback.md) for proper integration with WooCommerce's plugin API for seamless functionality.

