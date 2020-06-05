# Cart and Checkout Blocks theming

## Item quantity badge

The item quantity badge is the number that appears next to the image in the _Order summary_ section of the _Checkout_ block sidebar.

<img src="https://user-images.githubusercontent.com/3616980/83862844-c8559500-a722-11ea-9653-2fc8bcd544d2.png" alt="Order summary screenshot" width="234" />

By default, it uses a combination of black and white borders and shadows so it has enough contrast with themes with light and dark backgrounds. Themes can modify the colors with their own palette with a single CSS selector and four properties. For example:

```CSS
.wc-block-components-order-summary-item__quantity {
	background: #f9f4ee;
	border-color: #4b3918;
	box-shadow: 0 0 0 2px #f9f4ee;
	color: #4b3918;
}
```

<img src="https://user-images.githubusercontent.com/3616980/83863109-2e421c80-a723-11ea-9bf7-2033a96cf5b2.png" alt="Order summary screenshot with custom styles for the item quantity badge" width="231" />
