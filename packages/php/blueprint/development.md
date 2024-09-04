# Development

## Running Unit Tests

We use [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) to setup test environment in Docker.
Please install `wp-env` by running `npm install -g @wordpress/env` if you haven't already.

1. Run `composer run test:setup` to run wp-env.
2. Run `composer run test:unit` to run unit tests.
