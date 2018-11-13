`ProductImage` (component)
==========================

Use `ProductImage` to display a product's or variation's featured image.
If no image can be found, a placeholder matching the front-end image
placeholder will be displayed.



Props
-----

### `width`

- Type: Number
- Default: `60`

The width of image to display.

### `height`

- Type: Number
- Default: `60`

The height of image to display.

### `className`

- Type: String
- Default: `''`

Additional CSS classes.

### `product`

- Type: Object
- Default: null

Product or variation object. The image to display will be pulled from
`product.images` or `variation.image`.
See https://woocommerce.github.io/woocommerce-rest-api-docs/#product-properties
and https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variation-properties

### `alt`

- Type: String
- Default: null

Text to use as the image alt attribute.

