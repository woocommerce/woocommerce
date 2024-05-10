# Base Components/Context/Hooks

Base components are designed to be used on the frontend of a store. Due to this, we need to avoid using heavy WordPress externals as dependencies (`@wordpress/blocks`, `@wordpress/block-editor`, etc).

Note 2 exceptions, we do use the `Slot` and `Fill` which we [import and bundle](../../../packages/checkout/slot/index.js) from `@wordpress/components`. Otherwise you should avoid importing anything else from that package.

The other exception is the `FormTokenField`, which is used in deprecated blocks and use of [that import](../base/components/form-token-field/) is also deprecated.

If you need primitive/low-level components to build components in this library, please use [Ariakit](https://ariakit.org/) to build them.
See [Button](components/button/index.tsx) for an example.

Check the built `*.assets.php` files to ensure extra dependencies aren't being added to the build.
