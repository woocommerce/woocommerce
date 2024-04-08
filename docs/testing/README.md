---
category_title: Testing
category_slug: testing
post_title: Testing
---

# Testing

Properly setting up your test environment and writing tests when contributing to WooCommrece core are essential parts of our development pipeline. The links below are also included in our [Contributing Guidelines](https://github.com/woocommerce/woocommerce/blob/trunk/.github/CONTRIBUTING.md) on GitHub.

If you have any questions about testing please reach out to the developer community in our public channels([Developer Blog](https://developer.woo.com/blog/), [GitHub Discussions](https://github.com/woocommerce/woocommerce/discussions), or [Community Slack](https://woo.com/community-slack/)).

## Unit Testing

[End-to-end tests](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/e2e-pw) are powered by `Playwright`. The test site is spun up using `wp-env` ([recommended](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)), but we will continue to support `e2e-environment` in the meantime, and slowly [deprecate](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/e2e/README.md) `Puppeteer` testing. 

## API Testing

`api-core-tests` is a [package](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/api-core-tests#guide-for-writing-api-tests) that contains automated API tests for WooCommerce, based on `Playwright` and `wp-env`. It supersedes the SuperTest based `api-core-tests` package and `e2e-environment` setup, which we will gradually deprecate.

## Calls for Testing

Keep tabs on calls for testing on our developer blog, and make sure to read our beta testing instructions to help us build new features and enhancements.
