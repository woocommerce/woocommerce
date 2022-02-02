# Cart and Checkout Blocks theming

## Buttons

WC Blocks introduces the button component, it differs from a generic `button` in that it has some default styles to make it correctly fit in the Blocks design.

<img src="https://user-images.githubusercontent.com/3616980/86381945-e6fd6c00-bc8d-11ea-8811-7e546bea69b9.png" alt="Button screenshot" width="242" />

Themes can still style them to match theme colors or styles as follows:

```CSS
.wc-block-components-button {
	background-color: #d5502f;
	color: #fff;
	/* More rules can be added to modify the border, shadow, etc. */
}
/* It might be needed to modify the hover, focus, active and disabled states too */
```

<img src="https://user-images.githubusercontent.com/3616980/86381505-b6b5cd80-bc8d-11ea-8ceb-cfbe84b411d4.png" alt="Button screenshot with custom styles" width="239" />

Notice the button component doesn't have the `.button` class name. So themes that wrote some styles for buttons might want to apply some (or all) of those styles to the button component as well.

## Mobile submit container

In small viewports, the Cart block displays the _Proceed to Checkout_ button inside a container fixed at the bottom of the screen.

<img src="https://user-images.githubusercontent.com/3616980/86382876-393e8d00-bc8e-11ea-8d0b-e4e347ea4773.png" alt="Submit container screenshot" width="466" />

By default, the container has a white background so it plays well with the button component default colors. Themes that want to apply the same background color as the rest of the page can do it with the following code snippet:

```CSS
.wc-block-cart__submit-container {
	background-color: #f9f4ee;
}
```

Take into consideration the container has a top box shadow that might not play well with some dark background colors. If needed, it can be modified directly setting the `color` property (internally, shadow color uses `currentColor`, so it honors the `color` property):

```CSS
.wc-block-cart__submit-container::before {
	color: rgba(214, 209, 203, 0.5);
}
```

Alternatively, themes can override the `box-shadow` property completely:

```CSS
.wc-block-cart__submit-container::before {
	box-shadow: 0 -10px 20px 10px rgba(214, 209, 203, 0.5);
}
```

<img src="https://user-images.githubusercontent.com/3616980/86382693-27f58080-bc8e-11ea-894e-de378af3e2bb.png" alt="Submit container screenshot with custom styles" width="462" />

## Item quantity badge

The item quantity badge is the number that appears next to the image in the _Order summary_ section of the _Checkout_ block sidebar.

<img src="https://user-images.githubusercontent.com/3616980/83862844-c8559500-a722-11ea-9653-2fc8bcd544d2.png" alt="Order summary screenshot" width="234" />

By default, it uses a combination of black and white borders and shadows so it has enough contrast with themes with light and dark backgrounds. Themes can modify the colors with their own palette with a single CSS selector and four properties. For example:

```CSS
.wc-block-components-order-summary-item__quantity {
	background-color: #f9f4ee;
	border-color: #4b3918;
	box-shadow: 0 0 0 2px #f9f4ee;
	color: #4b3918;
}
```

<img src="https://user-images.githubusercontent.com/3616980/83863109-2e421c80-a723-11ea-9bf7-2033a96cf5b2.png" alt="Order summary screenshot with custom styles for the item quantity badge" width="231" />

<!-- FEEDBACK -->
---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/theming/cart-and-checkout.md)
<!-- /FEEDBACK -->

