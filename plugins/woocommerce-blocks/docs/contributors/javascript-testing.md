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

- `npm run build:e2e-test` to build container images used for e2e
- `npm run wp-env start` to start the test environment

Then, to run the tests:

- `npm run test:e2e` 

When you're iterating on a new test you'll often run this repeatedly, as you develop, until your test is just right.

When you're done, you may want to shut down the test environment:

- `npm run wp-env stop` to stop the test environment

**Note:** There are a number of other useful `wp-env` commands. You can find out more in the [wp-env docs](https://github.com/WordPress/gutenberg/blob/master/packages/env/README.md).
