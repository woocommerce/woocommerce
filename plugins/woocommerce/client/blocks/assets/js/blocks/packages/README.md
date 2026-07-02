# Block Context Packages

This folder contains package-like source code that is part of the Blocks implementation surface. Code here can still resolve through public import names such as `@woocommerce/types`, `@woocommerce/shared-context`, and `@woocommerce/shared-hocs`, but these packages are bundled into `@woocommerce/block-library` block and editor builds by default.

Use this folder for code that:

- Is only meaningful in a Blocks or block editor context.
- Is shared by multiple block implementations.
- Should stay close to `assets/js/blocks` so the dependency boundary is visible.

Do not use this folder for public standalone package surfaces that extensions are expected to enqueue directly. Those belong in `plugins/woocommerce/client/blocks/packages/` and should have a dedicated webpack entry, script handle, global, and asset registration.

## Current packages

- `shared-context`: React contexts shared by block implementations.
- `shared-hocs`: Higher-order components shared by block implementations.
- `types`: Blocks type definitions and runtime type guards.
