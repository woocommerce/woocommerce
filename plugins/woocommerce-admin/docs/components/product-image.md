`ProductImage` (component)
==========================

Use `ProductImage` to display a product's featured image. If no image can be found, a placeholder matching the front-end image
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

Product object. The image to display will be pulled from `product.images`.
See https://woocommerce.github.io/woocommerce-rest-api-docs/#product-properties

### `alt`

- Type: String
- Default: null

Text to use as the image alt attribute.

