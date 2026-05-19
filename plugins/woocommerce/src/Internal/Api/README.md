# Important: Internal and experimental code

**ALL** the code that's inside the `Automattic\WooCommerce\Internal` namespace and nested namespaces, or that's annotated with `@internal`, is for exclusive usage of WooCommerce core and must **NEVER** be used in released extensions or otherwise in production environments.

Additionally, the code in this directory (`Automattic\WooCommerce\Internal\Api` namespace and nested namespaces) is part of [an experimental feature](https://github.com/woocommerce/woocommerce/pull/63772) that could get backwards-incompatible changes or even be completely removed in future versions of WooCommerce; moreover, it's infrastructure code that's really not intended for external usage.

If you want to experiment with the feature (**NEVER** in production environments) from the code side, read [the provisional documentation](https://github.com/woocommerce/woocommerce/pull/63772) and look at the classes in the `src/Api` namespace.
