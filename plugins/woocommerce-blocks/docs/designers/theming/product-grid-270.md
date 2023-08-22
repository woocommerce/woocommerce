# Product grid blocks style update in 2.7.0 <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Product images](#product-images)
-   [All Products prices](#all-products-prices)

In WC Blocks 2.7.0, some of the styles of the product grid blocks were updated to make the experience more consistent. Below, there are CSS code snippets that can undo those changes.

## Product images

Images in product grid blocks changed so they expand to occupy all the available horizontal space if they are small. This can be undone with this CSS snippet:

```css
.wc-block-grid__products .wc-block-grid__product-image img {
	width: auto;
}
```

## All Products prices

_All Products_ block was updated so prices follow the same layout as the other product grid blocks (one line instead of two lines). It's possible to recover the old style with:

```css
.wc-block-grid__product-price .wc-block-grid__product-price__regular {
	font-size: 0.8em;
	line-height: 1;
	color: #555;
	margin-top: -0.25em;
	display: block;
}
.wc-block-grid__product-price .wc-block-grid__product-price__value {
	letter-spacing: -1px;
	font-weight: 600;
	display: block;
	font-size: 1.25em;
	line-height: 1.25;
	color: #000;
	margin-left: 0;
}
.wc-block-grid__product-price .wc-block-grid__product-price__value span {
	white-space: nowrap;
}
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/designers/theming/product-grid-270.md)

<!-- /FEEDBACK -->

