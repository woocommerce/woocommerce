# Base Components/Context/Hooks

Base components are designed to be used on the frontend of a store. Due to this, we need to avoid using heavy WordPress externals as dependencies (`@wordpress/blocks`, `@wordpress/block-editor`, etc).

Note one exception, we do use the `Slot` and `Fill` which we import and bundle from the latest version of `@wordpress/components`. Otherwise you should avoid importing anything else from that package. If you need primitive/low-level components to build components in this library, please use [Ariakit](https://ariakit.org/) to build them.

Check the built `*.assets.php` files to ensure extra dependencies aren't being added to the build.
