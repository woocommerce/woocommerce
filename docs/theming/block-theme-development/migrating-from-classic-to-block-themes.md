---
post_title: Migrating from classic to Block themes
sidebar_label: Migrating from classic themes
sidebar_position: 8
---

# Migrating from classic to Block themes

If you have built WooCommerce themes using PHP templates, `style.css` overrides, and `functions.php` hooks, the good news is that most of your knowledge carries over to Block themes. The underlying store pages still exist and WooCommerce has ways to override templates and apply styles.

However, the difference is where that work happens. This page will map some classic patterns to its Block theme equivalent. It will also point you to documentation that covers it in greater detail.

## Requirements

Before you begin, make sure you have the following in place:

- **Familiarity with classic WooCommerce theme development.** This page assumes working knowledge of PHP templates, action hooks, and CSS overrides.
- **A code editor capable of editing HTML and JSON files.** Block templates are HTML and theme styles are defined in `theme.json`.
- **The Create Block Theme plugin (optional).** This can help create or export a Block theme, but it is not required and does not automatically translate classic WooCommerce PHP templates or hooks.

## Template overrides

Classic and Block themes both support template overrides, but the file format and the folder location changes:

- **Classic themes** copy PHP files from the WooCommerce plugin into a `/woocommerce` directory in the theme root (for example, `yourtheme/woocommerce/single-product.php`).
- **Block themes** place HTML Block templates in a `/templates` folder in the theme root (for example, `yourtheme/templates/single-product.html`).

Some top-level store views retain familiar slugs across both approaches. For example, `single-product.php` and `single-product.html` both represent the single product view. This is not a direct file conversion: many PHP partials have no HTML template counterpart, and the HTML template's content must be composed from WooCommerce and WordPress Blocks rather than copied from the PHP markup.

To override the single product template in a classic theme, you copy and edit a PHP file:

```text
wp-content/themes/yourtheme/woocommerce/single-product.php
```

In a Block theme, you create an HTML template in the equivalent location:

```text
wp-content/themes/yourtheme/templates/single-product.html
```

WooCommerce registers HTML Block templates and fallback slugs for core store views, including single product, product archive, cart, checkout, search, and taxonomy archives. A merchant's Site Editor customization takes priority, followed by a matching file in the theme's `/templates` folder and then the WooCommerce default.

The [template hierarchy reference](/docs/theming/block-theme-development/template-hierarchy/) covers every available template and explains how the fallback order works.

## Declaring WooCommerce support

Classic themes commonly declare WooCommerce support in `functions.php`. Block themes are treated as WooCommerce-compatible without declaring the base support flag:

- `add_theme_support( 'woocommerce' )` is not required for WooCommerce to recognize a Block theme or load its HTML template overrides.
- Arguments passed with that support, such as classic image widths or product-grid limits, can still affect legacy templates and older product Blocks. Audit those values before removing the declaration.
- `add_theme_support( 'wc-product-gallery-zoom' )` and the related lightbox and slider flags are still read by the Product Image Gallery Block to enqueue legacy gallery behavior.

In a nutshell, do not copy support declarations into a new Block theme automatically, but do not remove them automatically either. Keep the declarations whose behavior your theme still needs, test the current Product Gallery and Product Image Gallery Blocks, and remove only the declarations that are genuinely obsolete.

## Styling

Classic WooCommerce themes apply styles in `style.css`, often targeting unprefixed class names such as `.price`, `.button`, and `.star-rating`. Block themes use `theme.json` as the primary styling layer.

Defining styles happens at the Block level using the Block's registered namespace, while WordPress generates the required CSS from those definitions.

Using `theme.json` for WooCommerce styles brings several advantages over CSS overrides:

- **Reduced reliance on private markup.** Styles are defined against a registered Block and its selector rather than a deep chain of internal class names. Block registrations can still change, so verify names and supports when updating WooCommerce.
- **Site Editor compatibility.** Site owners can override your styles in the Site Editor without touching code.
- **Managed precedence.** WordPress resolves core, theme, and user global styles through the same style engine, reducing duplicate declarations and specificity conflicts.

The following example sets the text color and font weight for the Product Price Block:

```json
{
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

CSS is still available where `theme.json` does not cover a specific scenario. Prefer registered Block selectors and WordPress block stylesheets over deep selectors. Do not assume every `.wc-block-` or `.wc-block-components-` class is a public API; WooCommerce considers internal Block markup subject to change. The [CSS styling reference](https://developer.woocommerce.com/docs/theming/block-theme-development/css-styling/) covers the class naming conventions and their stability, while there's also documentation on the recommended approach to [Cart and Checkout Block styling](https://developer.woocommerce.com/docs/theming/block-theme-development/cart-and-checkout/).

## Layout hooks

Classic themes use `woocommerce_before_main_content` and `woocommerce_after_main_content` in `functions.php` to wrap WooCommerce output in the markup a theme's layout requires:

```php
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

add_action( 'woocommerce_before_main_content', 'mytheme_wrapper_start', 10 );
add_action( 'woocommerce_after_main_content', 'mytheme_wrapper_end', 10 );

function mytheme_wrapper_start() {
    echo '<section id="main">';
}

function mytheme_wrapper_end() {
    echo '</section>';
}
```

WooCommerce's compatibility layer runs these hooks around relevant Blocks in the Blockified Single Product and product archive templates. That preserves many extension callbacks, but the exact insertion point follows the Block template rather than the old PHP wrapper. It can also be disabled with the `woocommerce_disable_compatibility_layer` filter.

Layout control in a Block theme belongs in the template itself, either by editing the relevant template in the Site Editor or by placing an HTML override in the theme's `/templates` folder. Test hook-based customizations during migration, and move wrapper and layout concerns into the Block template instead of relying on the compatibility layer's placement.

## Migration process

If you are converting an existing classic theme, the [Create Block Theme plugin](https://wordpress.org/plugins/create-block-theme/) can help generate or export a Block theme file structure and `theme.json`. Treat its output as a starting point: you still need to recreate PHP template overrides as Block markup, move layout decisions out of hooks, and translate styles deliberately.

[Purple](https://wordpress.org/themes/purple/) is a WooCommerce starter Block theme available in the WordPress.org theme directory. Its commerce-ready templates and patterns provide a working reference or starting point for a new theme.

Once the base structure is in place, you can apply the WooCommerce-specific customizations covered in this section directly. The [Develop Your First Low-Code Block Theme](https://learn.wordpress.org/course/develop-your-first-low-code-block-theme/) course on Learn WordPress is a great way to establish that foundational structure.
