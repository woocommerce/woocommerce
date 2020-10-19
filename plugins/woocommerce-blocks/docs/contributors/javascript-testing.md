# JavaScript Testing

Tests for JavaScript in the Blocks plugin are powered by [Jest](https://jestjs.io/).

The Blocks plugin follows the same patterns as Gutenberg, therefore for instructions on writing tests you can [refer to this page in the Gutenberg Handbook](https://developer.wordpress.org/block-editor/contributors/develop/testing-overview/).

We have two kinds of JavaScript tests:

- JavaScript unit tests - test APIs, hooks, library functionality that we use to build blocks or expose to plugin authors.
- End-to-end (e2e) tests - test blocks from the user interface.

These tests are all run automatically on open PRs by Travis CI.

All the following tests require that the dependencies are installed (`npm install` `composer install`). Ensure you've followed the [Getting Started Guide](getting-started.md) to set up node and other dependencies before running any tests.

## How to run JavaScript unit tests

Unit tests are implemented near the code they test, in `*.test.js` files.

Use the following command to run the unit tests:

```
$ npm run test
```

The test scripts use [wp-scripts](https://github.com/WordPress/gutenberg/tree/master/packages/scripts) to run jest for component and unit testing.

Additionally,

-   `test:update` updates the snapshot tests for components, used if you change a component that has tests attached.
-   `test:watch` keeps watch of files and automatically re-runs tests when things change.

## How to run end-to-end tests

End-to-end tests are implemented in `tests/e2e-tests/specs/`.

Since these drive the user interface, they need to run against a test environment - i.e. a web server running WordPress, Woo and blocks plugin, with a known state/configuration.

To set up to run e2e tests:

- `npm run build:e2e-test` builds the assets (js/css), you can exclude this step if you've already got built files to test with.
- `npm run wp-env start` to start the test environment

Then, to run the tests:

- `npm run test:e2e`

When you're iterating on a new test you'll often run this repeatedly, as you develop, until your test is just right.

When you're done, you may want to shut down the test environment:

- `npm run wp-env stop` to stop the test environment

**Note:** There are a number of other useful `wp-env` commands. You can find out more in the [wp-env docs](https://github.com/WordPress/gutenberg/blob/master/packages/env/README.md).

### How to update end-to-end tests suites

We follow the same WordPress support policy as WooCommerce, this means we need to support the latest version, and the two previous ones (L-2).

For that, we run end-to-end tests against all of those versions, and because we use packages published by Gutenberg, we also run tests against the latest version of Gutenberg plugin.

When a new version of WordPress is released, we drop support for the oldest version we have, so if the latest version is 5.5, we would test against:

- WordPress 5.3
- WordPress 5.4
- WordPress 5.5
- WordPress 5.5 + Gutenberg

When 5.6 is released, we would drop support for 5.3, and update our `./.travis.yml` file.

You need to bump the test version, so

```yml
- name: E2E Tests (WP 5.3)
	script:
			- npm run test:e2e
	env:
			- WP_VERSION=5.3
			- E2E_TESTS=1
			- WOOCOMMERCE_BLOCKS_PHASE=3
```

Would become

```yml
- name: E2E Tests (WP 5.4)
	script:
			- npm run test:e2e
	env:
			- WP_VERSION=5.4
			- E2E_TESTS=1
			- WOOCOMMERCE_BLOCKS_PHASE=3
```

You also need to check any existing tests that checks the WP version.

In `./tests/e2e/specs`, verify for conditions like `if ( process.env.WP_VERSION < 5.4 )` and remove them if they're not relevant anymore.