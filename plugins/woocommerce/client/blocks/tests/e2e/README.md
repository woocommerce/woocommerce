# WooCommerce Blocks End-to-End Tests

This document provides an overview of the WooCommerce Blocks end-to-end testing process. For detailed instructions and comprehensive guidelines, please refer to the [contributor guidelines document](../../docs/contributors/e2e-guidelines.md).

## Quick Start

### Preparing the Environment

1. Build the WooCommerce Plugin:

    ```sh
    pnpm --filter='@woocommerce/plugin-woocommerce' watch:build
    ```

2. Start the environment:

    ```sh
    pnpm --filter=@woocommerce/block-library env:start
    ```

### Running the Tests

1. Run all tests:

    ```sh
    pnpm --filter=@woocommerce/block-library test:e2e
    ```

2. Run a single test file:

    ```sh
    pnpm --filter=@woocommerce/block-library test:e2e path/to/the/file.spec.ts
    ```

3. Run in UI mode:

    ```sh
    pnpm --filter=@woocommerce/block-library test:e2e --ui
    ```

4. Run in debug mode:

    ```sh
    pnpm --filter=@woocommerce/block-library test:e2e --debug
    ```
