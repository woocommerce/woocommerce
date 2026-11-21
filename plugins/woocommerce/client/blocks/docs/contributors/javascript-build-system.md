# JavaScript Build System <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Environment variables](#environment-variables)
-   [Babel](#babel)
-   [External scripts](#external-scripts)
-   [Aliases](#aliases)
-   [Styling](#styling)
-   [Relevant files](#relevant-files)

WooCommerce Blocks uses [Rolldown](https://rolldown.rs) to build the files that will be consumed by browsers. The build lives in [`bin/rolldown/`](../../bin/rolldown/) and the shared parts in [`packages/js/rolldown-build`](../../../../../../packages/js/rolldown-build/README.md). There are several builds for different contexts of the plugin:

-   `core`: shared libraries like settings, blocks data or some HOCs and context.
-   `main`: the JS files used by blocks in the editor, which register the blocks in Gutenberg.
-   `frontend`: the JS files used by blocks in the store frontend.
-   `payments`: the JS files used by payment methods in the Cart and Checkout blocks.
-   `extensions`: extension integrations.
-   `styling`: CSS files. You can read more about it in the page [CSS build system](css-build-system.md).
-   Interactivity API script modules, the dependency detection script and `block.json` files.

Each build is described in [`bin/rolldown/config.mjs`](../../bin/rolldown/config.mjs). Entry points are declared in [`bin/entries.js`](../../bin/entries.js).

## Environment variables

`pnpm build` writes a production build: minified, without propTypes. `--dev` or `NODE_ENV=development` writes a development build with source maps, and `pnpm start` watches and rebuilds development builds.

## Babel

The build does not use Babel. Rolldown compiles TypeScript and JSX itself and targets the browsers WordPress supports. Jest still uses Babel.

## External scripts

Several scripts are loaded as externals. That means that they can be imported in WooCommerce Blocks as any other package, but instead of being bundled in our built files, they are loaded from an external file provided by WordPress (or Gutenberg, if enabled).

The build uses the same externals rules as [`@wordpress/dependency-extraction-webpack-plugin`](https://developer.wordpress.org/block-editor/packages/packages-dependency-extraction-webpack-plugin/), and writes an `.asset.php` file with the dependencies of each script.

In practice, that means the dependency version is:

-   deterministic when running WooCommerce Blocks in isolation (e.g. unit tests). In this case, the dependency will have a version as stated in `package.json`,
-   non-deterministic when run in the WordPress ecosystem. Depending on the WordPress or Gutenberg version the user has installed, the version of external dependencies may also vary.

These rules apply to each of the builds: Core, Main, Frontend, Payments, Extensions.

### `wordpress-components` and `wordpress-components-slotfill` alias

We have an alias to the 14.2.0 version of `@wordpress/components` called `wordpress-components`. This alias was used to import a limited set of components in front-end components. This alias is deprecated and **should no longer be used**. When the `FormTokenField` component is removed from the codebase, the alias will be removed as well.

We also have one other alias `wordpress-components-slotfill`, used to import the `SlotFill` and `Fill` components from `@wordpress/components`. This alias **should not be used to import any other dependencies from `@wordpress/components`**, and **should not be used outside of the slot fill context** — import it only within the slot fill seam at `packages/public-api/blocks-checkout/slot`, never directly from other components or blocks.

It is pinned to `@wordpress/components@29.5.4`, matching the version in the `wp-min` catalog in the root `pnpm-workspace.yaml`, so the slot fill resolves to the same `@wordpress/components` the rest of the package already builds against rather than a separate copy. The version is hardcoded because pnpm cannot combine the `catalog:` protocol with an `npm:` alias — re-pin it manually whenever the `wp-min` catalog bumps `@wordpress/components`.

The alias exists so the slot fill is bundled into the front-end instead of externalized. Because the specifier does not start with `@wordpress/`, the externals rules leave it alone rather than mapping it to `window.wp.components` (which is not loaded on the storefront). It is tree-shaken, so only the small amount of code needed for slot fill functionality is bundled.

If you need to build front-end components in future, you should use Ariakit to build them. See examples like the [Button](../../assets/js/base/components/button/index.tsx) component for how to build components using Ariakit.

## Aliases

There are several aliases for internal imports which make importing files across the file tree easier. Instead of having to write a long relative path, they allow writing an alias:

```diff
-import { useStoreCartCoupons } from '../../../../base/hooks';
+import { useStoreCartCoupons } from '@woocommerce/base-context/hooks';
```

Aliases also ease refactors because imports no longer depend on the exact location of the file.

All available aliases can be found in [`bin/build-helpers.js`](../../bin/build-helpers.js).

## Styling

CSS builds follow a separate path from JS builds, more details can be found in the [CSS build system](css-build-system.md).

## Relevant files

-   [`bin/rolldown/build.mjs`](../../bin/rolldown/build.mjs) and [`bin/rolldown/watch.mjs`](../../bin/rolldown/watch.mjs): the build and watch commands.
-   [`bin/rolldown/config.mjs`](../../bin/rolldown/config.mjs): the options of each build. If you're looking for something, start here.
-   [`bin/rolldown/styles.mjs`](../../bin/rolldown/styles.mjs) and [`bin/rolldown/modules.mjs`](../../bin/rolldown/modules.mjs): stylesheets, Interactivity API script modules, the dependency detection script and `block.json` files.
-   [`bin/entries.js`](../../bin/entries.js): mapping source files to entry points. If you're adding a new block or module to the build, start here.
-   [`bin/build-helpers.js`](../../bin/build-helpers.js): aliases, and the WooCommerce packages loaded as externals at run time.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./docs/contributors/javascript-build-system.md)

<!-- /FEEDBACK -->
