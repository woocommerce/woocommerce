# CSS Build System <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Right-to-left](#right-to-left)
-   [Relevant files](#relevant-files)

CSS files are built with Webpack, which gathers all SCSS files in the app and processes them with SASS and some PostCSS plugins like Autoprefixer. The resulting stylesheets are merged into three files for the whole plugin:

-   `style.css`: loaded in the editor and the frontend, it includes the basic styles needed for the blocks frontend.
-   `editor.css`: only loaded in the editor, it includes styles only relevant to the block editor like specific editor components.
-   `vendors-style.css`: loaded in the editor and the frontend of some blocks, it includes external stylesheets which are required by some blocks but are not part of our codebase.

> Details on which stylesheets are included in each output file can be found in [`webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/8befaccf35cac958c2d222e89facb84f9b675525/bin/webpack-configs.js#L655-L680).

## Right-to-left

All files described above are generated in a LTR version and a RTL version. The RTL version is generated automatically with `@automattic/webpack-rtl-plugin` and has a `.rtl.css` suffix at the end of the file name.

## Relevant files

Webpack config is split between several files, some relevant ones for the CSS build system are:

-   [`webpack.config.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/webpack.config.js): Top level webpack config.
    -   [`bin/webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-configs.js): Code for generating each build config. This most closely resembles a classic webpack config - if you're looking for something, start here.
        -   [`bin/webpack-entries.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/bin/webpack-entries.js): Code for generating [webpack `entry` definitions](https://webpack.js.org/concepts/entry-points/) and mapping source files to entry points. If you're adding a new block or module to the build, start here.

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/css-build-system.md)

<!-- /FEEDBACK -->

