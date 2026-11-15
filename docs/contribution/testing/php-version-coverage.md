---
post_title: Guidelines for choosing PHP versions in tests
sidebar_label: PHP version guidelines
category_slug: testing
---

# Guidelines for choosing PHP versions in tests

Use [WordPress.org PHP usage statistics](https://wordpress.org/about/stats/) to choose the most widely used version within WooCommerce's supported range for representative coverage. Also test the minimum and latest supported versions. PHP unit tests cover all three roles in routine CI; broader E2E coverage runs nightly to limit CI cost.

| Version choice | Configured PHP | E2E coverage | PHP unit coverage | Why |
| --- | --- | --- | --- | --- |
| Representative version | 8.3 | PR, push, release, pre-release, and nightly | PR and push, with latest WordPress | Focus frequent checks on a widely used PHP runtime, using WordPress.org adoption data to guide the choice. |
| Minimum supported by WooCommerce | 7.4 | Standard Core and Blocks nightly suites | PR and push, with WordPress L-1; optional WordPress pre-release | Preserve minimum-version compatibility through PHP behavior checks and complete user flows. |
| Latest supported by WooCommerce | 8.5 | Standard Core and Blocks nightly suites; existing Core jobs also run on PR, release, and pre-release | PR and push, with latest WordPress, including HPOS disabled | Catch compatibility problems as merchants adopt newer PHP releases. |
