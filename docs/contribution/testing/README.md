---
sidebar_label: Testing
category_slug: testing
post_title: Testing
---

# Testing

Properly setting up your test environment and writing tests when contributing to WooCommerce core are essential parts of our development pipeline. The links below are also included in our [Contributing Guidelines](https://github.com/woocommerce/woocommerce/blob/trunk/.github/CONTRIBUTING.md) on GitHub.

If you have any questions about testing please reach out to the developer community in our public channels([Developer Blog](https://developer.woocommerce.com/blog/), [GitHub Discussions](https://github.com/woocommerce/woocommerce/discussions), or [Community Slack](https://woocommerce.com/community-slack/)).

## Unit Testing

[End-to-end tests](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/e2e-pw) are powered by `Playwright`. The test site is spun up using `wp-env` ([recommended](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)). 

## API Testing

`api-core-tests` is a [package](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/api-core-tests) that contains automated API tests for WooCommerce, based on `Playwright` and `wp-env`.

## Calls for Testing

Keep tabs on calls for testing on our developer blog, and make sure to read our beta testing instructions to help us build new features and enhancements.
