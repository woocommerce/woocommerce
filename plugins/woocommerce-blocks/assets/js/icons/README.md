# Icons <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Usage](#usage)
-   [Adding Icons](#adding-icons)

WooCommerce Blocks Icons Library.

## Usage

Note we use the `Icon` component from `@wordpress/icons`. We use some SVG icons from `@woocommerce/icons` for WC Blocks specific icons, but we also use existing icons from `@wordpress/icons`.

```js
import { woo } from '@woocommerce/icons';
import { Icon, postComments } from '@wordpress/icons';

<Icon icon={ woo } /> // icon  from '@woocommerce/icons'
<Icon icon={ postComments } /> // icon from '@wordpress/icons'
<Icon icon={ woo } size={ 16 } />
<Icon icon={ woo } width={ 20 } height={ Math.floor( 20 * 1.67 ) } />
```

## Adding Icons

Before adding a new icon, make sure the icon is not already included in the [Library that comes with @wordpress/icons package](https://wordpress.github.io/gutenberg/?path=/story/icons-icon--library). If there is no existing icon suitable:

1. Add the icon file to `./library` folder.
2. Make sure to use `SVG` primitive from `@wordpress/primitives` and not a native svg. `SVG` offers more accessibility features.
3. Remove width and height since they're handled by Icon.
4. Remove any hardcoded colors on the svg. If necessary, use `CurrentColor`.
5. Export the Icon in `./library/index.js`.

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/README.md)

<!-- /FEEDBACK -->
