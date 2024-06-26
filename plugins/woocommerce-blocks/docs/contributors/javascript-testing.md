# JavaScript Testing <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [How to run JavaScript unit tests](#how-to-run-javascript-unit-tests)
-   [How to run end-to-end tests with deprecated infrastructure](#how-to-run-end-to-end-tests-with-deprecated-infrastructure)
-   [How to run end-to-end tests](#how-to-run-end-to-end-tests)
    -   [Debugging e2e tests using generated reports](#debugging-e2e-tests-using-generated-reports)
    -   [Modify the local environment used by end-to-end tests](#modify-the-local-environment-used-by-end-to-end-tests)
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

## How to run end-to-end tests with deprecated infrastructure

End-to-end tests are implemented in `tests/e2e-tests/specs/`.

Since these drive the user interface, they need to run against a test environment - i.e. a web server running WordPress, Woo and blocks plugin, with a known state/configuration.

To set up to run e2e tests:

-   `npm run build` builds the assets (js/css), you can exclude this step if you've already got built files to test with.
-   `npm run wp-env start` to start the test environment

    Then, to run the tests:

-   `npm run test:e2e`

When you're iterating on a new test you'll often run this repeatedly, as you develop, until your test is just right.

Between tests, especially when they rely on the fixture data added, it might help to run `npm run wp-env clean`.
When you're done, you may want to shut down the test environment:

-   `npm run wp-env stop` to stop the test environment

**Note:** There are a number of other useful `wp-env` commands. You can find out more in the [wp-env docs](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/README.md).

## How to run end-to-end tests

Visit the [dedicated documentation](../../tests/e2e/README.md).

### Debugging e2e tests using generated reports

When e2e test suites are run in a GitHub automation, a report is generated automatically for every suite that failed. This can be a useful tool to debug failing tests, as it provides a visual way to inspect the tests that failed and, additionally, it includes some screenshots.

To access the reports, you should go to the _Details_ of a failed e2e test suite:

<img src="https://user-images.githubusercontent.com/3616980/231486295-26b1d8fd-2420-4890-b143-a249cc990d20.png" alt="PR showing a failing test suite and the cursor over the Details button of that suite" width="780" />

From there, you can open the _Summary_ of the e2e test jobs:

<img src="https://user-images.githubusercontent.com/3616980/231486308-8f85779b-8ede-440d-a250-6ff612d6ea20.png" alt="Log of an e2e test suite that failed, highlighting the Summary button" width="780" />

From the _Summary_ page, if you scroll down, you can download the report of each test suite that failed:

<img src="https://user-images.githubusercontent.com/3616980/231486320-c52a0e10-c80e-4d3a-ae0f-b3998013f528.png" alt="Report summary showing the Artifacts list, including the e2e reports" width="780" />

That will download a ZIP that you can open in your browser locally.

### Modify the local environment used by end-to-end tests

To modify the environment used by tests locally, you will need to modify `.wp-env.json`. For example, you can set a specific WP version and install the latest Gutenberg version with these two lines:

```diff
{
-	"core": "WordPress/WordPress#5.7-branch",
+	"core": "WordPress/WordPress#5.6-branch",
	"plugins": [
		"https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip",
		"https://github.com/WP-API/Basic-Auth/archive/master.zip",
+		"https://downloads.wordpress.org/plugin/gutenberg.latest-stable.zip",
		"."
	],
  ...
}
```

You will need to stop `wp-env` and start it again. In some cases, you will also need to clean the database: `npm run wp-env clean all`.

### WordPress versions and end-to-end tests suites

Currently, we only run e2e tests with the most recent version of WordPress. We also have the infrastructure in place to run e2e tests with the most recent version of WordPress with Gutenberg installed, but [it's currently disabled](https://github.com/woocommerce/woocommerce-blocks/blob/07605450ffa4e460543980b7011b3bf8a8e82ff4/.github/workflows/php-js-e2e-tests.yml#L10).

When preparing for a new version of WordPress, it's a good practice to search for conditions in our tests that check for specific WP versions (with the variable `WP_VERSION`).

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/javascript-testing.md)

<!-- /FEEDBACK -->
