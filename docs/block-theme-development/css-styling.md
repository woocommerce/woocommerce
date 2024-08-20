---
post_title: CSS styling for themes
menu_title: CSS Styling for Themes
tags: reference
---

## Block and component class names

> [!IMPORTANT]
> We strongly discourage writing CSS code based on existing block class names and prioritize using global styles when possible. We especially discourage writing CSS selectors that rely on a specific block being a descendant of another one, as users can move blocks around freely, so they are prone to breaking. Similar to WordPress itself, we consider the HTML structure within components, blocks, and block templates to be "private", and subject to further change in the future, so using CSS to target the internals of a block or a block template is _not recommended or supported_.

WooCommerce Blocks follows BEM for class names, as [stated in our coding guidelines](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/docs/contributors/coding-guidelines.md). All classes start with one of these two prefixes:

* `.wc-block-`: class names specific to a single block.
* `.wc-block-components-`: class names specific to a component. The component might be reused by different blocks.

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

## Container query class names

Some of our components have responsive classes depending on the container width. The reason to use these classes instead of CSS media queries is to adapt to the container where the block is added (CSS media queries only allow reacting to viewport sizes).

Those classes are:

Container width | Class name
----------------|------------
\&gt;700px         | `is-large`
521px-700px     | `is-medium`
401px-520px     | `is-small`
&lt;=400px         | `is-mobile`

As an example, if we wanted to do the Checkout font size 10% larger when the container has a width of 521px or wider, we could do so with this code:

```css
.wc-block-checkout.is-medium,
.wc-block-checkout.is-large {
	font-size: 1.1em;
}
```

## WC Blocks _vs._ theme style conflicts for semantic elements

WooCommerce Blocks uses HTML elements according to their semantic meaning, not their default layout. That means that some times blocks might use an anchor link (`&lt;a&gt;`) but display it as a button. Or the other way around, a `&lt;button&gt;` might be displayed as a text link. Similarly, headings might be displayed as regular text.

In these cases, Blocks include some CSS resets to undo most default styles introduced by themes. A `&lt;button&gt;` that is displayed as a text link will probably have resets for the background, border, etc. That will solve most conflicts out-of-the-box but in some occasions those CSS resets might not have effect if the theme has a specific CSS selector with higher specificity. When that happens, we really encourage theme developers to decrease their selectors specificity so Blocks styles have preference, if that's not possible, themes can write CSS resets on top.

## Hidden elements

WC Blocks use the [`hidden` HTML attribute](https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/hidden) to hide some elements from the UI so they are not displayed in screens neither read by assistive technologies. If your theme has some generic styles that tweak the CSS display property of blocks elements (ie: `div { display: block; }`), make sure you correctly handle the hidden attribute: `div[hidden] { display: none; }`.

## Legacy classes from WooCommerce (.price, .star-rating, .button...)

WooCommerce Blocks avoids using legacy unprefixed classes as much as possible. However, you might find some of them that have been added for backwards compatibility. We still encourage themes to use the prefixed classes when possible, this avoids conflicts with other plugins, the editor, etc.

