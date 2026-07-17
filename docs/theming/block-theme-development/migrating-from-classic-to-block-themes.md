---
post_title: Migrating from classic to block themes
sidebar_label: Migrating from classic themes
sidebar_position: 1
---

# Migrating from classic to block themes

If you have built WooCommerce themes using PHP templates, `style.css`
overrides, and `functions.php` hooks, most of that knowledge carries over to
block themes. The underlying store pages still exist, and WooCommerce still
provides ways to override templates and apply styles.

What changes is where that work happens. This guide maps common classic-theme
patterns to their block-theme equivalents and links to detailed references for
each area.

:::info
This guide assumes that you are familiar with classic WooCommerce theme
development and basic block-theme concepts. If block themes are new to you,
start with [Develop Your First Low-Code Block Theme](https://learn.wordpress.org/course/develop-your-first-low-code-block-theme/).
:::

## Requirements

Before you begin, make sure you have:

- A version of WordPress supported by the WooCommerce release you are
  targeting. The current requirement is listed in the
  [WooCommerce plugin readme](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/readme.txt).
- A current, supported version of WooCommerce.
- Familiarity with classic WooCommerce template overrides, action hooks, and
  CSS customizations.
- A block theme to work with.

A code editor is useful when you need direct access to HTML templates and
`theme.json`, but it is not required for every workflow. The optional
[Create Block Theme plugin](https://wordpress.org/plugins/create-block-theme/)
can export changes made in the Site Editor to theme files and create new themes
or child themes from the current design.

## Template overrides

Classic and block themes both support template overrides, but the file format
and location change:

- Classic themes copy PHP templates from WooCommerce into a `/woocommerce`
  directory in the theme root, for example
  `yourtheme/woocommerce/single-product.php`.
- Block themes place HTML block templates in the theme's `/templates`
  directory, for example `yourtheme/templates/single-product.html`.

The filenames often map directly between the two systems, so
`single-product.php` becomes `single-product.html`. The contents do not map
directly: the HTML file is composed of WordPress and WooCommerce blocks rather
than PHP markup and hooks.

For example, the classic override path is:

```text
wp-content/themes/yourtheme/woocommerce/single-product.php
```

The equivalent block-theme override path is:

```text
wp-content/themes/yourtheme/templates/single-product.html
```

WooCommerce registers block templates for its core store views, including
single products, product archives, taxonomies, Cart, Checkout, and Order
Confirmation. A matching template in the active theme takes priority over the
WooCommerce default, unless a merchant has saved a higher-priority
customization in the Site Editor.

See the [WooCommerce block template hierarchy](/docs/theming/block-theme-development/template-hierarchy/)
for the complete template list, precedence rules, fallback chains, and
template-part overrides.

## Declaring WooCommerce support

Classic themes commonly declare WooCommerce support in `functions.php`:

```php
add_theme_support( 'woocommerce' );
```

WooCommerce recognizes a block theme through WordPress, so a block theme does
not need this declaration for its WooCommerce block-template overrides to
work.

Classic themes may also enable gallery features with declarations such as:

```php
add_theme_support( 'wc-product-gallery-zoom' );
add_theme_support( 'wc-product-gallery-lightbox' );
add_theme_support( 'wc-product-gallery-slider' );
```

These declarations configure the classic product image gallery. The Product
Gallery block manages its presentation through block settings instead. During
a migration, keep the declarations until you have confirmed that no legacy
template or Product Image Gallery block still depends on them.

## Styling

Classic WooCommerce themes commonly style unprefixed selectors such as
`.price`, `.button`, and `.star-rating` in `style.css`. In a block theme,
`theme.json` and global styles are the primary styling layer.

Block-specific styles use the block's registered namespace under
`styles.blocks`. This example changes the text color and font weight of the
Product Price block:

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"styles": {
		"blocks": {
			"woocommerce/product-price": {
				"color": {
					"text": "#333333"
				},
				"typography": {
					"fontWeight": "700"
				}
			}
		}
	}
}
```

Using global styles has several advantages:

- Styles target registered block features instead of relying on internal
  markup.
- Merchants can customize supported settings in the Site Editor.
- Theme defaults, style variations, and user customizations participate in the
  same WordPress styling system.

CSS remains useful when a block does not expose the setting you need. When you
write CSS for WooCommerce blocks, prefer the `.wc-block-` and
`.wc-block-components-` prefixes and avoid selectors that depend on internal
markup or a particular nesting order.

See [Global styles for WooCommerce](/docs/theming/block-theme-development/global-styles/)
for `theme.json` examples and block style variations. The
[CSS styling reference](/docs/theming/block-theme-development/css-styling/)
covers class naming and other CSS considerations. For Cart and Checkout, also
review [Cart and Checkout blocks theming](/docs/theming/block-theme-development/cart-and-checkout/).

## Layout hooks

Classic themes often replace the callbacks on
`woocommerce_before_main_content` and `woocommerce_after_main_content` to wrap
WooCommerce output in theme-specific markup:

```php
remove_action(
	'woocommerce_before_main_content',
	'woocommerce_output_content_wrapper',
	10
);
add_action(
	'woocommerce_before_main_content',
	'mytheme_wrapper_start',
	10
);

function mytheme_wrapper_start() {
	echo '<section id="main">';
}
```

WooCommerce includes compatibility handling for many classic hooks in its
blockified product templates, so existing callbacks may continue to run.
However, layout control in a block theme belongs in the template itself. Build
the page structure with blocks in the Site Editor or in an HTML file under
`/templates` instead of using wrapper hooks.

Audit hook-based customizations individually during a migration. A callback
that adds content may still be useful, while a callback that opens or closes a
layout wrapper should usually be replaced by template markup.

## Migration process

There is no one-to-one automated conversion from a classic WooCommerce theme
to a block theme. A practical migration looks like this:

1. Create or choose a block-theme foundation.
2. Rebuild the relevant PHP template overrides as HTML block templates.
3. Move supported visual styles from CSS into `theme.json`.
4. Retain targeted CSS for designs that global styles cannot express.
5. Audit PHP hooks and move layout concerns into templates.
6. Test product pages, product archives, Cart, Checkout, and saved Site Editor
   customizations.

The Create Block Theme plugin can help create the theme structure and export
Site Editor changes to files. You still need to adapt WooCommerce PHP
templates, hooks, and CSS intentionally.

## Next steps

Continue with the detailed guides in this section:

1. [WooCommerce block template hierarchy](/docs/theming/block-theme-development/template-hierarchy/)
   explains registered templates, fallback order, and override paths.
2. [Global styles for WooCommerce](/docs/theming/block-theme-development/global-styles/)
   covers WooCommerce blocks in `theme.json`, style variations, and Site Editor
   workflows.
3. [Cart and Checkout blocks theming](/docs/theming/block-theme-development/cart-and-checkout/)
   covers styles and responsive behavior inside the Cart and Checkout blocks.
4. [CSS styling for themes](/docs/theming/block-theme-development/css-styling/)
   documents class naming, container queries, and CSS fallbacks.

## References

- [Classic theme developer handbook](/docs/theming/theme-development/classic-theme-developer-handbook/)
- [WooCommerce block reference](/docs/block-development/reference/block-references/)
- [WordPress global settings and styles](https://developer.wordpress.org/themes/global-settings-and-styles/)
- [Create Block Theme plugin](https://wordpress.org/plugins/create-block-theme/)
- [Develop Your First Low-Code Block Theme](https://learn.wordpress.org/course/develop-your-first-low-code-block-theme/)
