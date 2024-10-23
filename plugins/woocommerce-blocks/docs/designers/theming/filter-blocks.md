# Filter blocks

> [!IMPORTANT]
> We strongly discourage writing CSS code based on existing block class names and prioritize using global styles when possible. We especially discourage writing CSS selectors that rely on a specific block being a descendant of another one, as users can move blocks around freely, so they are prone to breaking. Similar to WordPress itself, we consider the HTML structure within components, blocks, and block templates to be “private”, and subject to further change in the future, so using CSS to target the internals of a block or a block template is _not recommended or supported_.

## Price slider accent color

The Filter by Price block includes a price slider which uses an accent color to show the selected range.

![Price filter screenshot](https://user-images.githubusercontent.com/3616980/96570001-2053f900-12ca-11eb-8a75-8a54f243bda3.png)

By default, it uses the WooCommerce purple shade, but it can be easily modified by themes with the following code:

```css
.wc-block-components-price-slider__range-input-progress,
.rtl .wc-block-components-price-slider__range-input-progress {
	--range-color: #ee6948;
}
```

![Price filter screenshot with custom styles](https://user-images.githubusercontent.com/3616980/96569858-f0a4f100-12c9-11eb-8011-05227bb60277.png)

Notice the code snippet above uses a CSS custom property, so the default color might still be available in some browsers that don't support it like Internet Explorer 11. If your theme supports IE11, you can add the following lines to target it:

```css
/* Target only IE11 */
@media all and ( -ms-high-contrast: none ), ( -ms-high-contrast: active ) {
	.wc-block-components-price-slider__range-input-progress {
		background: #ee6948;
	}
}
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./docs/designers/theming/filter-blocks.md)

<!-- /FEEDBACK -->

