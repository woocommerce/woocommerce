---
post_title: Migrating from classic to Block themes
sidebar_label: Migrating from classic themes
sidebar_position: 6
---

# Migrating from classic to Block themes

If you have built WooCommerce themes using PHP templates, style.css overrides, and functions.php hooks, the good news is that most of your knowledge carries over to Block themes. The underlying store pages still exist and WooCommerce has ways to override templates and apply styles.

However, the difference is where that work happens. This page will map some classic patterns to its Block theme equivalent. It will also point you to documentation that covers it in greater detail.

## Requirements

Before you begin, make sure you have the following in place:

- **WordPress 5.9 or later.** Block theme support was introduced in WordPress 5.9.
- **A current version of WooCommerce.** Block templates and global style support require a recent WooCommerce release.
- **Familiarity with classic WooCommerce theme development.** This page assumes working knowledge of PHP templates, action hooks, and CSS overrides.
- **A code editor capable of editing HTML and JSON files.** Block templates are HTML and theme styles are defined in theme.json.
- **The Create Block Theme plugin (optional).** Required only if you are converting an existing classic theme to Blocks rather than building from scratch.

## Template overrides

Classic and Block themes both support template overrides, but the file format and the folder location changes:

- **Classic themes** copy PHP files from the WooCommerce plugin into a /woocommerce directory in the theme root (e.g. yourtheme/woocommerce/single-product.php).
- **Block themes** place HTML Block templates in a /templates folder in the theme root (e.g. yourtheme/templates/single-product.html).

The file naming convention is consistent across both approaches, so single-product.php maps directly to single-product.html. However, the content is composed of WooCommerce and WordPress Blocks rather than PHP markup.

To override the single product template in a classic theme, you copy and edit a PHP file:

```text
wp-content/themes/yourtheme/woocommerce/single-product.php
```

In a Block theme, you create an HTML template in the equivalent location:

```text
wp-content/themes/yourtheme/templates/single-product.html
```

WooCommerce provides HTML Block templates for all core store pages, including single product, product archive, cart, checkout, and taxonomy pages. Any template in your /templates folder with a matching file name takes priority over the WooCommerce default.

The [template hierarchy reference](/docs/theming/block-theme-development/template-hierarchy/) covers every available template and explains how the fallback order works.

## Declaring WooCommerce support

Classic themes that use template overrides declare WooCommerce support in functions.php. Block themes do not require any of these declarations:

- add_theme_support( 'woocommerce' ). WooCommerce support is implicit and template overrides work without it.
- add_theme_support( 'wc-product-gallery-zoom' ) **and related gallery declarations**. The product gallery is handled through the Product Gallery Block and does not depend on functions.php declarations.

In a nutshell, you can remove any WooCommerce-related add_theme_support calls when building a Block theme from scratch.

## Styling

Classic WooCommerce themes apply styles in style.css, targeting unprefixed class names such as .price, .button, and .star-rating. Block themes use theme.json as the primary styling layer.

Defining styles happens at the Block level using the Block's registered namespace, while WooCommerce generates the required CSS from those definitions.

Using theme.json for WooCommerce styles brings several advantages over CSS overrides:

- **Reduced breakage risk.** Styles are defined against the Block namespace rather than internal class names, so markup changes in WooCommerce updates won't break your existing styles.
- **Site Editor compatibility.** Site owners can override your styles in the Site Editor without touching code.
- **Improved performance.** Only the CSS required for the Blocks on a given page is printed, which reduces the overall stylesheet payload.

The following example sets the text color and font weight for the Product Price Block:

```json
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
```

However, CSS will still be available where theme.json does not cover a specific scenario. When you do write CSS, target the prefixed .wc-block- and .wc-block-components- class names rather than the legacy unprefixed classes. The [CSS styling reference](https://developer.woocommerce.com/docs/theming/block-theme-development/css-styling/) covers the full class naming conventions, while there's also documentation on the recommended approach to [Cart and Checkout Block styling](https://developer.woocommerce.com/docs/theming/block-theme-development/cart-and-checkout/).

## Layout hooks

Classic themes use woocommerce_before_main_content and woocommerce_after_main_content in functions.php to wrap WooCommerce output in the markup a theme's layout requires:

```php
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );

add_action( 'woocommerce_before_main_content', 'mytheme_wrapper_start', 10 );

function mytheme_wrapper_start() {
    echo '<section id="main">';
}
```

These hooks remain functional in Block themes. However, layout control in a Block theme belongs in the template itself through editing the relevant template in the Site Editor or placing an HTML override in your /templates folder. You can carry hook-based customizations forward during a migration, but moving layout concerns into the Block template is the more durable long-term approach.

## Migration process

If you are converting an existing classic theme, the [Create Block Theme plugin](https://wordpress.org/plugins/create-block-theme/) can provide a starting point. It generates the file structure and base theme.json a Block theme requires, which you then populate with your overriding templates and style definitions.

Once the base structure is in place, you can apply the WooCommerce-specific customizations covered in this section directly. The [Develop Your First Low-Code Block Theme](https://learn.wordpress.org/course/develop-your-first-low-code-block-theme/) course on Learn WordPress is a great way to establish that foundational structure.

## Next steps

The rest of this section covers each area of WooCommerce Block theme development in detail:

1. [Global styles for WooCommerce](/docs/theming/block-theme-development/global-styles/). Using theme.json to style WooCommerce Blocks.
2. [Template hierarchy reference](/docs/theming/block-theme-development/template-hierarchy/). All WooCommerce Block templates and how to override them.
3. Product page theming. Customizing the single product template.
4. Shop and archive page theming. Customizing the product listing templates.
5. [Cart and Checkout theming](https://developer.woocommerce.com/docs/theming/block-theme-development/cart-and-checkout/). Styling the Cart and Checkout Blocks, including buttons and the mobile submit container.
6. [CSS styling](https://developer.woocommerce.com/docs/theming/block-theme-development/css-styling/). CSS approaches and class name conventions.
7. [Theming for Woo Blocks](https://developer.woocommerce.com/docs/theming/block-theme-development/theming-woo-blocks/). An overview of core concepts, Block templates, template parts, and global styles.

## References

- [Classic theme developer handbook](https://developer.woocommerce.com/docs/theming/theme-development/classic-theme-developer-handbook/). The full reference for classic WooCommerce theme development.
- [CSS styling for themes](https://developer.woocommerce.com/docs/theming/block-theme-development/css-styling/). BEM class name conventions, container queries, and handling theme style conflicts.
- [Template hierarchy reference](/docs/theming/block-theme-development/template-hierarchy/). All WooCommerce Block templates, fallback order, and override paths.
- [WooCommerce block references](https://developer.woocommerce.com/docs/block-development/reference/block-references/). The full list of WooCommerce Block names for use in theme.json.
- [Global settings and styles](https://developer.wordpress.org/themes/global-settings-and-styles/styles/). WordPress documentation on theme.json and the global styles system.
- [Create Block Theme plugin](https://wordpress.org/plugins/create-block-theme/). A tool for generating Block theme file structures from an existing classic theme.
- [Develop Your First Low-Code Block Theme](https://learn.wordpress.org/course/develop-your-first-low-code-block-theme/). A Learn WordPress course covering Block theme fundamentals.
