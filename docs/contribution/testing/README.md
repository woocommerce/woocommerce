---
sidebar_label: Testing
category_slug: testing
post_title: Testing
---

# Testing

Setting up your test environment and writing tests when contributing to WooCommerce Core are essential parts of our development pipeline. The links below are also included in our [Contributing Guidelines](https://github.com/woocommerce/woocommerce/blob/trunk/.github/CONTRIBUTING.md) on GitHub.

If you have questions about testing, reach out to the developer community in our public channels: [Developer Blog](https://developer.woocommerce.com/blog/), [GitHub Discussions](https://github.com/woocommerce/woocommerce/discussions), or [Community Slack](https://woocommerce.com/community-slack/).

## Unit testing

[Unit tests](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/README.md) run against the WooCommerce PHP test suite. The recommended local setup uses `wp-env`.

## End-to-end testing

[End-to-end tests](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/e2e) are powered by Playwright. The test site is spun up using `wp-env`.

## API testing

[API tests](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/e2e/tests/api-tests) are part of the Playwright suite and use the same `wp-env` test environment as end-to-end tests.

## Testing instructions

When opening a pull request, use the [testing instructions guide](/docs/contribution/testing/writing-high-quality-testing-instructions/) to write clear steps that cover the behavior changed in the PR.

## Calls for testing

Keep tabs on calls for testing on our [developer blog](https://developer.woocommerce.com/blog/), and read our [beta testing instructions](/docs/contribution/testing/beta-testing/) to help us build new features and enhancements.
