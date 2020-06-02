# Product grid blocks style update in 2.7.0

In WC Blocks 2.7.0, some of the styles of the product grid blocks were updated to make the experience more consistent. Below, there are CSS code snippets that can undo those changes.

## Product images

Images in product grid blocks changed so they expand to occupy all the available horizontal space if they are small. This can be undone with this CSS snippet:

```CSS
.wc-block-grid__products .wc-block-grid__product-image img {
	width: auto;
}
```

## All Products prices

_All Products_ block was updated so prices follow the same layout as the other product grid blocks (one line instead of two lines). It's possible to recover the old style with:

```CSS
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
