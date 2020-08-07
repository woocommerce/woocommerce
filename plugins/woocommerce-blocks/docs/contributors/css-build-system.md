# CSS build system

CSS files are built with Webpack, which gathers all SCSS files in the app and processes them with SASS and some PostCSS plugins like Autoprefixer. The resulting stylesheets are merged into three files for the whole plugin:

-   `style.css`: loaded in the editor and the frontend, it includes the basic styles needed for the blocks frontend.
-   `editor.css`: only loaded in the editor, it includes styles only relevant to the block editor like specific editor components.
-   `vendors-style.css`: loaded in the editor and the frontend of some blocks, it includes external stylesheets which are required by some blocks but are not part of our codebase.

> Details on which stylesheets are included in each output file can be found in [`webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/main/bin/webpack-configs.js#L413-L439).

## Legacy builds

When building WC Blocks, a special build targeting old versions of WordPress is generated. Some blocks might not be available in that legacy build. For example, All Products and Filter blocks not being available on WP 5.2.

Legacy builds use their own CSS files with the suffix `-legacy`, this allows saving some bytes because non-legacy block styles are not included.

> Note: All components' styles are included in legacy CSS files no matter if they are only used by non-legacy blocks. See [#2818](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2818) for more details.

## Right-to-left

All files described above are generated in a LTR version and a RTL version. The RTL version is generated automatically with `webpack-rtl-plugin` and has a `-rtl` suffix at the end of the file name.

## Relevant files

Webpack config is split between several files, some relevant ones for the CSS build system are:

-   [`webpack.config.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/main/webpack.config.js): file that exports the configs.
-   [`bin/webpack-configs.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/main/bin/webpack-configs.js): definition of the different configs used by Webpack.
-   [`bin/webpack-entries.js`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/main/bin/webpack-entries.js): entries used by Webpack definitions.
