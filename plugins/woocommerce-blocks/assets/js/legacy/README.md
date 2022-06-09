# Legacy Build <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [How does the legacy system work?](#how-does-the-legacy-system-work)
    - [Aliases](#aliases)
    - [Webpack Plugin](#webpack-plugin)

This folder is used to hold any components/code that will get exported to the legacy build.

> Currently, builds in this folder target WP < 5.3

When you add a file, add it in the same folder structure as the root.

So for example, if the original file was in:

`assets/js/base/components/label/index.js`

Then the legacy version will be located in:

`assets/js/legacy/base/components/label/index.js`

Note: you _must_ copy all files related to the entry point for that module according to the path aliased.

Legacy builds will be identical to the main builds except:

-   files will have a `-legacy` suffix (so server can conditionally enqueue). It is expected that the server will load either the main or the legacy bundles, not both.
-   any imports not in the legacy folder will fallback to the main file.

## How does the legacy system work?

Short answer, through the magic of WebPack! Long answer:

### Aliases

We use aliases for paths covering anything that might need a legacy version. Then we have a dedicated builds for main and legacy code.

Current aliases are:

-   `@woocommerce/base-components` -> `assets/js/base/components/`
-   `@woocommerce/base-hocs` -> `assets/js/base/hocs/`
-   `@woocommerce/editor-components` -> `assets/js/editor-components`
-   `@woocommerce/block-hocs` -> `assets/js/block-hocs`

When importing, if outside the module referenced by that path, import from the alias. That will ensure that at compile time the bundles can pull from the appropriate location.

Example:

```js
// will pull from '/assets/js/base/components/label/index.js in the main build
// will pull from '/assets/js/legacy/base/components/label/index.js in the legacy
// build.
import { Label } from '@woocommerce/base-components/label';
```

### Webpack Plugin

The second part of the webpack magic is a custom plugin. Located in `bin/fallback-module-directory-webpack-plugin.js`, this custom plugin is used instead of the default Alias plugin. It handles trying a fallback if the original path aliased to does not exist. The fallback is a variation of the aliased path using the provided `search` and `replace` strings when instantiating the plugin. You can see it setup in the `LegacyBlocksConfig.resolve.plugins` property of the `webpack.config.js` file.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/README.md)

<!-- /FEEDBACK -->
