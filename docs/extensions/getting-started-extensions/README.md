# Get started building extensions

This section provides guides and resources for building, testing, and distributing WooCommerce extensions.

## Important: Internal vs public code

Not all WooCommerce code is intended for use by extensions. Classes in the `Automattic\WooCommerce\Internal` namespace and code marked with `@internal` are for WooCommerce core use only: backwards compatibility between WooCommerce releases is not guaranteed and your extension may break if you use them. See the [extension development best practices](../best-practices-extensions/extension-development-best-practices.md) and the [Internal namespace documentation](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/README.md).

## Getting started

- [Design a simple extension](/extensions/getting-started-extensions/how-to-design-a-simple-extension) - Learn extension architecture and best practices
- [Build your first extension](/extensions/getting-started-extensions/building-your-first-extension) - Create your first WooCommerce extension
- [Core concepts](/extensions/core-concepts/) - Master fundamental concepts like plugin headers, lifecycle management, and security

## Submit to the WooCommerce Marketplace

Join the WooCommerce Marketplace and get your extension in front of 4M+ active stores worldwide.

Learn more about why extension developers are choosing the WooCommerce Marketplace, and how to [apply for a vendor account](https://woocommerce.com/partners/). Once you're approved, [submit your extension](../../woo-marketplace/submitting-your-product/).


### Quality Insights Toolkit (QIT)

#### Available to developers with a product submitted to or listed on the Woo Marketplace

QIT (Quality Insights Toolkit) is a testing platform developed by WooCommerce for plugins and themes. It allows developers to quickly run a variety of managed tests out-of-the-box, as well as integrate their own custom E2E tests to ensure their extensions are reliable, secure, and compatible.

#### Key features

- **Managed test suites:** Run pre-configured end-to-end tests, activation tests, security scans, PHPStan analysis, API tests, and more
- **Custom E2E testing:** Write and run your own Playwright-based E2E tests directly through QIT
- **Continuous quality checks:** Seamlessly integrate QIT into your development workflows via CLI, GitHub Actions, and more
- **Marketplace integration:** Currently in closed beta for products submitted to or listed on the Woo Marketplace

[Learn more about QIT](https://qit.woo.com/docs/)

## Development tools

- [Extension scaffolds](/getting-started/scaffolding/#extension-scaffolds) - Learn how to scaffold new extensions with our [create-woo-extension](https://www.npmjs.com/package/@woocommerce/create-woo-extension) package.
- [WooCommerce CLI](/wc-cli/cli-overview) - Command-line tools for WooCommerce development
