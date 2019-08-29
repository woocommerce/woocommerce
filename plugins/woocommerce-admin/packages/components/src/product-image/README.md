ProductImage
===

Use `ProductImage` to display a product's or variation's featured image.
If no image can be found, a placeholder matching the front-end image
placeholder will be displayed.

## Usage

```jsx
// Use a real WooCommerce Product here.
const product = {
	images: [
		{
			src: 'https://cldup.com/6L9h56D9Bw.jpg',
		},
	],
};

<ProductImage product={ product } />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`width` | Number | `60` | The width of image to display
`height` | Number | `60` | The height of image to display
`className` | String | `''` | Additional CSS classes
`product` | Object | `null` | Product or variation object. The image to display will be pulled from `product.images` or `variation.image`. See https://woocommerce.github.io/woocommerce-rest-api-docs/#product-properties and https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variation-properties
`alt` | String | `null` | Text to use as the image alt attribute
