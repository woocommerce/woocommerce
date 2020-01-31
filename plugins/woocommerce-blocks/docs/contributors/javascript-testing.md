# JavaScript Testing

Tests for JavaScript in the Blocks plugin are powered by [Jest](https://jestjs.io/).

The Blocks plugin follows the same patterns as Gutenberg, therefore for instructions on writing tests you can [refer to this page in the Gutenberg Handbook](https://developer.wordpress.org/block-editor/contributors/develop/testing-overview/).

## Running Tests

Assuming you've already followed the [Getting Started Guide](getting-started.md) on setting up node and other dependencies, tests are ran from the command line using the following command:

```
$ npm run test
```

The test scripts use [wp-scripts](https://github.com/WordPress/gutenberg/tree/master/packages/scripts) to run jest for component and unit testing.

Additionally,

-   `test:update` updates the snapshot tests for components, used if you change a component that has tests attached.
-   `test:watch` keeps watch of files and automatically re-runs tests when things change.
