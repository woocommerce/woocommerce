# Icons

WooCommerce Blocks Icons Library.

## Usage

```js
import { Icon, bill, woo } from '@woocommerce/icons';

<Icon srcElement={ bill } />

<Icon srcElement={ bill } size={ 16 } />

<Icon srcElement={ woo } width={ 20 } height={ Math.floor( 20 * 1.67 ) } />
```

## Props

Name | Type | Default | Description
--- | --- | --- | ---
`size` | `integer` | `24` | Size of icon in pixels.


## Adding Icons

1. Add the icon file to `./library` folder.
2. Make sure to use `SVG` primitive from `@wordpress/components` and not a native svg. `SVG` offers more accessibility features.
3. Remove width and height since they're handled by Icon.
4. Remove any hardcoded colors on the svg. If necessary, use `CurrentColor`.
5. Export the Icon in `./library/index.js`.