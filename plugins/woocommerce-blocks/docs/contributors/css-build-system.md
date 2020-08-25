# CSS build system

CSS files are built with Webpack, which gathers all SCSS files in the app and processes them with SASS and some PostCSS plugins like Autoprefixer. The resulting stylesheets are merged into three files for the whole plugin:

-   `style.css`: loaded in the editor and the frontend, it includes the basic styles needed for the blocks frontend.
-   `editor.css`: only loaded in the editor, it includes styles only relevant to the block editor like specific editor components.
-   `vendors-style.css`: loaded in the editor and the frontend of some blocks, it includes external stylesheets which are required by some blocks but are not part of our codebase.

> Details on which stylesheets are included in each output file can be found in [`webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/main/bin/webpack-configs.js#L413-L439).

## Legacy builds

In the past, when building WC Blocks, special builds targeting old versions of WordPress were generated. Those builds were named 'legacy builds' and might have a smaller set of blocks available. For example, All Products and Filter blocks were not available on WP 5.2.

Currently, those builds are no longer generated since WC Blocks doesn't support WP 5.2 anymore, but the built system is still in place in case we need legacy builds in the future.

Legacy builds used their own CSS files with the suffix `-legacy`, this allowed saving some bytes because non-legacy block styles were not included.

> Note: All components' styles were included in legacy CSS files no matter if they were only used by non-legacy blocks. See [#2818](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2818) for more details.

## Right-to-left

All files described above are generated in a LTR version and a RTL version. The RTL version is generated automatically with `webpack-rtl-plugin` and has a `-rtl` suffix at the end of the file name.

## Relevant files

Webpack config is split between several files, some relevant ones for the CSS build system are:

-   [`webpack.config.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/main/webpack.config.js): Top level webpack config. Includes support for legacy and main build.
    -   [`bin/webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/main/bin/webpack-configs.js): Code for generating each build config. This most closely resembles a classic webpack config - if you're looking for something, start here.
        -   [`bin/webpack-entries.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/main/bin/webpack-entries.js): Code for generating [webpack `entry` definitions](https://webpack.js.org/concepts/entry-points/) and mapping source files to entry points. If you're adding a new block or module to the build, start here.
