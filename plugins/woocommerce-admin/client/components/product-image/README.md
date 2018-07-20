ProductImage
============

Use `ProductImage` to display a product's featured image. If no image can be found, a placeholder matching the front-end image placeholder will be displayed.

## How to use:

```jsx
import ProductImage from 'components/product-image';

render: function() {
  return (
	<div>
		<ProductImage product={ null } />
		<ProductImage product={ { images: [] } } />
		<ProductImage product={ { images: [
			{
				src: 'https://i.cloudup.com/pt4DjwRB84-3000x3000.png',
			},
		] } } />
	</div>
  );
}
```

## Props

* `product`: Product object. The image to display will be pulled from `product.images`. See https://woocommerce.github.io/woocommerce-rest-api-docs/#product-properties
* `width`: Default 60. The width of image to display.
* `height`: Default 60. The height of image to display.
* `alt`: Text to use as the image alt attribute.
* `className`: Additional CSS classes.