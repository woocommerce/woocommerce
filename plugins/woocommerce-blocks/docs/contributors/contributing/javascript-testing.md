# JavaScript Testing <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

- [How to run JavaScript unit tests](#how-to-run-javascript-unit-tests)
- [How to run end-to-end tests](#how-to-run-end-to-end-tests)
    - [Modify the local environment used by end-to-end tests](#modify-the-local-environment-used-by-end-to-end-tests)
    - [How to update end-to-end tests suites](#how-to-update-end-to-end-tests-suites)

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

The test scripts use [wp-scripts](https://github.com/WordPress/gutenberg/tree/master/packages/scripts) to run jest for component and unit testing.

Additionally,

-   `test:update` updates the snapshot tests for components, used if you change a component that has tests attached.
-   `test:watch` keeps watch of files and automatically re-runs tests when things change.

## How to run end-to-end tests

End-to-end tests are implemented in `tests/e2e-tests/specs/`.

Since these drive the user interface, they need to run against a test environment - i.e. a web server running WordPress, Woo and blocks plugin, with a known state/configuration.

To set up to run e2e tests:

-   `npm run build:e2e-test` builds the assets (js/css), you can exclude this step if you've already got built files to test with.
-   `npm run wp-env start` to start the test environment

    Then, to run the tests:

-   `npm run test:e2e`

When you're iterating on a new test you'll often run this repeatedly, as you develop, until your test is just right.

Between tests, especially when they rely on the fixture data added, it might help to run `npm run wp-env clean`.
When you're done, you may want to shut down the test environment:

-   `npm run wp-env stop` to stop the test environment

**Note:** There are a number of other useful `wp-env` commands. You can find out more in the [wp-env docs](https://github.com/WordPress/gutenberg/blob/master/packages/env/README.md).

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

### How to update end-to-end tests suites

We follow the same WordPress support policy as WooCommerce, this means we need to support the latest version, and the two previous ones (L-2).

For that, we run end-to-end tests against all of those versions, and because we use packages published by Gutenberg, we also run tests against the latest version of Gutenberg plugin.

When a new version of WordPress is released, we drop support for the oldest version we have, so if the latest version is 5.6, we would test against:

-   WordPress 5.4
-   WordPress 5.5
-   WordPress 5.6
-   WordPress 5.6 + Gutenberg

When 5.7 is released, we would drop support for 5.4, and update our `./.github/workflows/php-js-e2e-tests.yml` file.

You need to bump the test version, so

```yml
  JSE2ETestsWP54:
    name: JavaScipt E2E Tests (WP 5.4)
      ...
      - name: E2E Tests (WP 5.4)
        env:
          WOOCOMMERCE_BLOCKS_PHASE: 3
          WP_VERSION: 5.4-branch
        run: |
          JSON='{"core": "WordPress/WordPress#'"$WP_VERSION"'"}'
          echo $JSON > .wp-env.override.json
          npm run wp-env start
          npm run wp-env clean all
          npm run test:e2e
```

Would become

```yml
  JSE2ETestsWP55:
    name: JavaScipt E2E Tests (WP 5.5)
      ...
      - name: E2E Tests (WP 5.5)
        env:
          WOOCOMMERCE_BLOCKS_PHASE: 3
          WP_VERSION: 5.5-branch
        run: |
          JSON='{"core": "WordPress/WordPress#'"$WP_VERSION"'"}'
          echo $JSON > .wp-env.override.json
          npm run wp-env start
          npm run wp-env clean all
          npm run test:e2e
```

You also need to check any existing tests that checks the WP version.

In `./tests/e2e/specs`, verify for conditions like `if ( process.env.WP_VERSION < 5.4 )` and remove them if they're not relevant anymore.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/contributing/javascript-testing.md)

<!-- /FEEDBACK -->

