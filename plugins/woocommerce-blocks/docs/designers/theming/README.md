# Theming

This page includes all documentation regarding WooCommerce Blocks and themes.

## General concepts

### Block and component class names

WooCommerce Blocks follows BEM for class names, as [stated in our coding guidelines](../../contributors/coding-guidelines.md). All classes start with one of these two prefixes:

-   `.wc-block-`: class names specific to a single block.
-   `.wc-block-components-`: class names specific to a component. The component might be reused by different blocks.

The combination of block class names and component class names allows themes to style each component either globally or only in specific blocks. As an example, you could style all prices to be italics with:

```css
/* This will apply to all block prices */
.wc-block-components-formatted-money-amount {
	font-style: italic;
}
```

But if you only wanted to make it italic in the Checkout block, that could be done adding the block selector:

```css
/* This will apply to prices in the checkout block */
.wc-block-checkout .wc-block-components-formatted-money-amount {
	font-style: italic;
}
```

**Note:** for backwards compatibility, some components might have class names with both prefixes (ie: `wc-block-sort-select` and `wc-block-components-sort-select`). In those cases, the class name with `.wc-block-` prefix is deprecated and shouldn't be used in new code. It will be removed in future versions. If an element has both classes always style the one with `.wc-block-components-` prefix.

### Container query class names

Some of our components have responsive classes depending on the container width. The reason to use these classes instead of CSS media queries is to adapt to the container where the block is added (CSS media queries only allow reacting to viewport sizes).

Those classes are:

| Container width | Class name  |
| --------------- | ----------- |
| >700px          | `is-large`  |
| 521px-700px     | `is-medium` |
| 401px-520px     | `is-small`  |
| <=400px         | `is-mobile` |

As an example, if we wanted to do the Checkout font size 10% larger when the container has a width of 521px or wider, we could do so with this code:

```css
.wc-block-checkout.is-medium,
.wc-block-checkout.is-large {
	font-size: 1.1em;
}
```

### WC Blocks _vs._ theme style conflicts for semantic elements

WooCommerce Blocks uses HTML elements according to their semantic meaning, not their default layout. That means that some times blocks might use an anchor link (`<a>`) but display it as a button. Or the other way around, a `<button>` might be displayed as a text link. Similarly, headings might be displayed as regular text.

In these cases, Blocks include some CSS resets to undo most default styles introduced by themes. A `<button>` that is displayed as a text link will probably have resets for the background, border, etc. That will solve most conflicts out-of-the-box but in some occasions those CSS resets might not have effect if the theme has a specific CSS selector with higher specificity. When that happens, we really encourage theme developers to decrease their selectors specificity so Blocks styles have preference, if that's not possible, themes can write CSS resets on top.

### Hidden elements

WC Blocks use the [`hidden` HTML attribute](https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/hidden) to hide some elements from the UI so they are not displayed in screens neither read by assistive technologies. If your theme has some generic styles that tweak the CSS display property of blocks elements (ie: `div { display: block; }`), make sure you correctly handle the hidden attribute: `div[hidden] { display: none; }`.

### Legacy classes from WooCommerce (.price, .star-rating, .button...)

WooCommerce Blocks avoids using legacy unprefixed classes as much as possible. However, you might find some of them that have been added for backwards compatibility. We still encourage themes to use the prefixed classes when possible, this avoids conflicts with other plugins, the editor, etc.

## Blocks

-   [Filter blocks](filter-blocks.md)
-   [Cart and Checkout](cart-and-checkout.md)

## Other docs

-   [Product grid blocks style update in 2.7.0](product-grid-270.md)
-   [Class names update in 2.8.0](class-names-update-280.md)
-   [Class names update in 3.3.0](class-names-update-330.md)
-   [Class names update in 3.4.0](class-names-update-340.md)
-   [Class names update in 4.6.0](class-names-update-460.md)

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/designers/theming/README.md)

<!-- /FEEDBACK -->

