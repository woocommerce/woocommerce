---
post_title: Theming for Woo blocks
menu_title: Theming for Woo Blocks
tags: reference
---

This page includes documentation about theming WooCommerce blocks and block themes.


**Note:** this document assumes some previous knowledge about block theme development and some WordPress concepts. If you are completely new to block theme development, please check [Develop Your First Low-Code Block Theme](https://learn.wordpress.org/course/develop-your-first-low-code-block-theme/)
to learn about block theme development, and explore
the [Create Block Theme plugin](https://wordpress.org/plugins/create-block-theme/) tool when you're ready to create a
new theme.

## General concepts

### Block templates

WooCommerce comes with several [block templates](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/templates/templates/blockified) by default. Those are:

- Single Product (`single-product.html`)
- Product Catalog (`archive-product.html`)
    - Products by Category (`taxonomy-product_cat.html`)
    - Products by Tag (`taxonomy-product_tag.html`)
    - Products by Attribute (`taxonomy-product_attribute.html`)
- Product Search Results (`product-search-results.html`)
- Page: Coming soon (`page-coming-soon.html`)
- Page: Cart (`page-cart.html`)
- Page: Checkout (`page-checkout.html`)
- Order Confirmation (`order-confirmation.html`)

Block themes can customize those templates in the following ways:

- It's possible to override the templates by creating a file with the same file name under the `/templates` folder. For example, if a block theme contains a `wp-content/themes/yourtheme/templates/single-product.html` template, it will take priority over the WooCommerce default Single Product template.
- Products by Category, Products by Tag and Products by Attribute templates fall back to the Product Catalog template. In other words, if a theme provides an `archive-product.html` template but doesn't provide a `taxonomy-product_cat.html` template, the Products by Category template will use the `archive-product.html` template. Same for the Products by Tag and Products by Attribute templates.
- It's possible to create templates for specific products and taxonomies. For example, if the theme provides a template with file name of `single-product-cap.html`, that template will be used when rendering the product with slug `cap`. Similarly, themes can provide specific taxonomy templates: `taxonomy-product_cat-clothing.html` would be used in the product category with slug `clothing`.
- Always keep in mind users can make modifications to the templates provided by the theme via the Site Editor.

### Block template parts

WooCommerce also comes with two specific [block template parts](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/templates/parts):

- Mini-Cart (`mini-cart.html`): used inside the Mini-Cart block drawer.
- Checkout header (`checkout-header.html`): used as the header in the Checkout template.

Similarly to the templates, they can be overriden by themes by adding a file with the same file name under the `/parts` folder.

### Global styles

WooCommerce blocks rely on [global styles](https://developer.wordpress.org/themes/global-settings-and-styles/styles/) for their styling. Global styles can be defined by themes via `theme.json` or by users via Appearance > Editor > Styles and offer several advantages over plain CSS:

- Better performance, as only the required CSS is printed into the page, reducing the bundle size to render a page.
- Can be easily customized by users via the UI.
- Gracefully handle conflicts between plugins and themes.
- Are not affected by markup or class name updates into individual blocks or components.
- Don't depend on a specific nesting order of blocks: users can freely move blocks around without styles breaking.

#### Example

For example, let's imagine you are building a theme and would like to customize the Product Price block styles, you can do so by adding these properties in your `theme.json`:

```JSON
"styles": {
	"blocks": {
		"woocommerce/product-price": {
			"color": {
				"background": "#00cc00",
				"text": "#fff"
			},
			"typography": {
				"fontStyle": "italic",
				"fontWeight": "700"
			}
		}
		...
	}
	...
}
```

Before                                                                                                                                                                                                      | After
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
<img src="https://github.com/woocommerce/woocommerce/assets/3616980/fbc11b83-f47b-4b25-bdeb-df798b251cce" width="210" alt="Product Collection block showing the Product Price block with default styles" /> | <img src="https://github.com/woocommerce/woocommerce/assets/3616980/c9730445-b9df-4e96-8204-a10896ac2c5a" width="210" alt="Product Collection block showing the Product Price styled with background and text colors and italic and bold typography" /> <!-- markdownlint-disable-line no-inline-html -->

You can find more [documentation on global styles](https://developer.wordpress.org/themes/global-settings-and-styles/styles/) in developer.wordpress.org. You can also find the [list of WooCommerce blocks and their names in the docs](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/docs/block-references/block-references.md).
