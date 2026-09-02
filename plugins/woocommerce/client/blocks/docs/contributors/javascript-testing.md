# JavaScript Testing <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [How to run JavaScript unit tests](#how-to-run-javascript-unit-tests)
-   [How to run end-to-end tests](#how-to-run-end-to-end-tests)
    -   [Debugging e2e tests using generated reports](#debugging-e2e-tests-using-generated-reports)
    -   [WordPress versions and end-to-end tests suites](#wordpress-versions-and-end-to-end-tests-suites)

Tests for JavaScript in the Blocks plugin are powered by [Jest](https://jestjs.io/). The Blocks plugin follows the same patterns as Gutenberg, therefore for instructions on writing tests you can [refer to this page in the Gutenberg Handbook](https://developer.wordpress.org/block-editor/contributors/develop/testing-overview/).

We have two kinds of JavaScript tests:

-   JavaScript unit tests - test APIs, hooks, library functionality that we use to build blocks or expose to plugin authors.
-   End-to-end (e2e) tests - test blocks from the user interface.

These tests are all run automatically on open PRs by Travis CI.

All the following tests require that the dependencies are installed (`npm install` `composer install`). Ensure you've followed the [Getting Started Guide](getting-started.md) to set up node and other dependencies before running any tests.

## How to run JavaScript unit tests

Unit tests are implemented near the code they test, in `*.test.js` files.

Use the following command to run the unit tests:

```sh
npm run test
```

The test scripts use [wp-scripts](https://github.com/WordPress/gutenberg/tree/trunk/packages/scripts) to run jest for component and unit testing.

Additionally,

-   `test:update` updates the snapshot tests for components, used if you change a component that has tests attached.
-   `test:watch` keeps watch of files and automatically re-runs tests when things change.

## How to run end-to-end tests

The Blocks end-to-end tests were merged into the WooCommerce Core e2e suite. They now
live at `plugins/woocommerce/tests/e2e/tests/blocks/` and run through the shared
Playwright config (`tests/e2e/playwright.config.ts`, project `blocks-chromium`).

From `plugins/woocommerce`:

```bash
# Start wp-env, run the Blocks test-env setup, and install the browser
pnpm env:start:blocks

# Run the Blocks e2e suite
pnpm test:e2e:blocks
```

For the full setup and available environments, see the
[core e2e documentation](../../../../tests/e2e/README.md).

### Debugging e2e tests using generated reports

When e2e test suites are run in a GitHub automation, a report is generated automatically for every suite that failed. This can be a useful tool to debug failing tests, as it provides a visual way to inspect the tests that failed and, additionally, it includes some screenshots.

To access the reports, you should go to the _Details_ of a failed e2e test suite:

![PR showing a failing test suite and the cursor over the Details button of that suite](https://user-images.githubusercontent.com/3616980/231486295-26b1d8fd-2420-4890-b143-a249cc990d20.png)

From there, you can open the _Summary_ of the e2e test jobs:

![Log of an e2e test suite that failed, highlighting the Summary button](https://user-images.githubusercontent.com/3616980/231486308-8f85779b-8ede-440d-a250-6ff612d6ea20.png)

From the _Summary_ page, if you scroll down, you can download the report of each test suite that failed:

![Report summary showing the Artifacts list, including the e2e reports](https://user-images.githubusercontent.com/3616980/231486320-c52a0e10-c80e-4d3a-ae0f-b3998013f528.png)

That will download a ZIP that you can open in your browser locally.

### WordPress versions and end-to-end tests suites

Currently, we only run e2e tests with the most recent version of WordPress. We also have the infrastructure in place to run e2e tests with the most recent version of WordPress with Gutenberg installed, but [it's currently disabled](https://github.com/woocommerce/woocommerce-blocks/blob/07605450ffa4e460543980b7011b3bf8a8e82ff4/.github/workflows/php-js-e2e-tests.yml#L10).

When preparing for a new version of WordPress, it's a good practice to search for conditions in our tests that check for specific WP versions (with the variable `WP_VERSION`).

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./docs/contributors/javascript-testing.md)

<!-- /FEEDBACK -->
