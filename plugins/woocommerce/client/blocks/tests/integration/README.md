# WooCommerce Blocks Integration Tests

This folder contains integration tests and related utilities for the `@woocommerce/block-library` workspace, leveraging the headless editor setup.

## Purpose

The purpose of this folder is to:

- House integration tests that verify interactions between components or blocks.
- Store helpers, utilities, and other resources relevant to integration testing.

## Structure and Approach

Following the [approach used in Gutenberg](https://github.com/WordPress/gutenberg/blob/trunk/docs/contributors/code/testing-overview.md#integration-testing-for-block-ui):

- Block-specific integration tests are placed within their respective block folders and run as part of the JS test suite. For example, see the [Active Filters block integration test](../../assets/js/blocks/active-filters/test/block.ts).
- **Wider integration tests** or shared helpers can be added directly within this folder, maintaining flexibility and organization.

## Running Tests

All integration tests are executed within the JS test suite:

```sh
pnpm --filter="@woocommerce/block-library" test:js
```

## Why This Structure?

- **Consistency with Gutenberg:** Maintains a familiar structure and development flow.
- **Optimized CI Workflow:** Using a single test suite reduces complexity and execution time, optimizing CI performance.

## Adding Tests and Helpers

- For block-specific scenarios, add tests within the corresponding block folder.
- For broader integration scenarios or shared utilities, add them in this folder.
- Follow existing patterns to ensure consistency and maintainability.

This approach enhances test coverage, maintains organization, and streamlines the integration testing workflow.
