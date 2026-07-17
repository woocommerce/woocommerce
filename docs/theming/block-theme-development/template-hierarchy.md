---
post_title: WooCommerce block template hierarchy
sidebar_label: Template hierarchy
sidebar_position: 2
---

# WooCommerce block template hierarchy

Every WooCommerce store view renders through a template. With a block theme, WooCommerce registers block templates for product pages, product archives, Cart, Checkout, Order Confirmation, and the Coming soon experience. A store therefore has working templates even when the active theme does not provide WooCommerce-specific files.

This reference maps the templates and template parts WooCommerce registers, explains their fallback order, and shows how a theme can override them.

## Requirements

This guide assumes you have:

- A current, supported version of WooCommerce.
- A block theme with HTML templates.
- Familiarity with the Site Editor and its **Code editor** view.
- Familiarity with the [WordPress template hierarchy](https://developer.wordpress.org/themes/templates/template-hierarchy/).

Classic themes use PHP templates under the plugin's `/templates` directory. Those templates follow a different override system, documented in the [classic theme developer handbook](/docs/theming/theme-development/classic-theme-developer-handbook/).

## Understand template precedence

For a given block template, WordPress and WooCommerce use the highest-priority source available:

1. A template customized by a merchant in the Site Editor and saved in the database.
2. A matching template file supplied by the active theme.
3. The default template registered by WooCommerce.

Site Editor customizations are not written back to the theme's files. In the template list, a **Customized** label indicates that the database version is taking precedence. Use the template's **Clear customizations** or **Reset** action when you need to return to the theme or plugin version.

### Blockified and compatibility templates

WooCommerce contains two implementations of its block templates:

- Blockified templates compose the page from individual WooCommerce and core WordPress blocks.
- Compatibility templates contain a `woocommerce/legacy-template` block that renders the classic PHP template inside a block-template shell.

Block themes use the blockified templates by default. If a template does not look as expected, open it in the Site Editor's **Code editor**. A single `woocommerce/legacy-template` block indicates that the compatibility template is active.

The PHP template system is a separate layer. Finding a file such as `taxonomy-product-cat.php` under `/woocommerce/templates` does not mean that the corresponding block template uses PHP markup.

## Registered WooCommerce templates

WooCommerce registers 11 block templates. Some have a default HTML file in the plugin, while taxonomy templates can inherit from Product Catalog.

| Template | Slug | Default behavior |
| --- | --- | --- |
| Single Product | `single-product` | Uses `single-product.html` for an individual product. |
| Product Catalog | `archive-product` | Uses `archive-product.html` for the shop and is the fallback for product taxonomies. |
| Products by Category | `taxonomy-product_cat` | Falls back to Product Catalog unless the theme or merchant provides a more specific template. |
| Products by Tag | `taxonomy-product_tag` | Falls back to Product Catalog unless the theme or merchant provides a more specific template. |
| Products by Attribute | `taxonomy-product_attribute` | Uses a generic `taxonomy-product_attribute.html` template for product attribute taxonomies. |
| Products by Brand | `taxonomy-product_brand` | Falls back to Product Catalog unless the theme or merchant provides a more specific template. Available in WooCommerce 9.6 and later. |
| Product Search Results | `product-search-results` | Uses `product-search-results.html` for searches scoped to products. |
| Page: Coming soon | `coming-soon` | Uses `coming-soon.html` for the store's Coming soon experience. |
| Page: Cart | `page-cart` | Uses `page-cart.html` around the assigned Cart page's content. |
| Page: Checkout | `page-checkout` | Uses `page-checkout.html` around the assigned Checkout page's content. |
| Order Confirmation | `order-confirmation` | Uses `order-confirmation.html` for the order-received endpoint. |

> [!NOTE]
> The Coming soon template file is `coming-soon.html`, not `page-coming-soon.html`. **Page: Coming soon** is its title in the Site Editor.

## Product and taxonomy fallback chains

WordPress chooses the most specific matching template and then moves through broader fallbacks.

For an individual product, the useful theme-level chain is:

1. `single-product-{slug}.html`, for example `single-product-cap.html`.
2. `single-product.html`.
3. WooCommerce's registered Single Product template when the theme supplies neither file.

For a product category, the chain is:

1. `taxonomy-product_cat-{term}.html`, for example `taxonomy-product_cat-clothing.html`.
2. `taxonomy-product_cat.html`.
3. `archive-product.html`.
4. WooCommerce's registered Product Catalog template when the theme supplies no matching file.

Product tags and brands use the same pattern with `taxonomy-product_tag` and `taxonomy-product_brand`. Product attributes use WooCommerce's generic `taxonomy-product_attribute.html` template, while a theme can still provide more specific taxonomy templates when needed.

A theme that wants one design across all product archives only needs `archive-product.html`. Add a taxonomy-specific file when a category, tag, brand, or attribute archive genuinely needs a different block composition.

## Cart, Checkout, and Order Confirmation

Cart and Checkout are assigned WordPress pages. Their templates should render the assigned page's content rather than placing the Cart or Checkout block directly in the template.

The default Cart template uses `woocommerce/page-content-wrapper`, notices, a page title, and `core/post-content`:

```html
<!-- wp:woocommerce/page-content-wrapper {"page":"cart"} -->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:woocommerce/store-notices /-->
	<!-- wp:post-title {"align":"wide","level":1} /-->
	<!-- wp:post-content {"align":"wide"} /-->
</main>
<!-- /wp:group -->
<!-- /wp:woocommerce/page-content-wrapper -->
```

The Checkout template follows the same pattern but omits the page title:

```html
<!-- wp:woocommerce/page-content-wrapper {"page":"checkout"} -->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:woocommerce/store-notices /-->
	<!-- wp:post-content {"align":"wide"} /-->
</main>
<!-- /wp:group -->
<!-- /wp:woocommerce/page-content-wrapper -->
```

> [!IMPORTANT]
> Keep the Cart or Checkout block in its assigned page content. Replacing `core/post-content` with `woocommerce/cart` or `woocommerce/checkout` in a template can put the page editor out of sync with the front end and bypass functionality that inspects the assigned page.

Order Confirmation is endpoint-specific rather than an assigned page. Its template contains the order status, summary, totals, customer information, and related order blocks directly.

See [Cart and Checkout blocks theming](/docs/theming/block-theme-development/cart-and-checkout/) for styles and responsive behavior inside those blocks.

## Registered template parts

Template parts are reusable sections stored in a theme's `/parts` directory. WooCommerce registers up to six:

| Template part | Slug | Purpose |
| --- | --- | --- |
| Mini-Cart | `mini-cart` | Supplies the drawer opened by the Mini-Cart block. |
| Checkout header | `checkout-header` | Supplies a checkout-specific header separate from the site's standard header. |
| Simple product Add to Cart + Options | `simple-product-add-to-cart-with-options` | Supplies the add-to-cart form for simple products. |
| External product Add to Cart + Options | `external-product-add-to-cart-with-options` | Supplies the add-to-cart form for external products. |
| Variable product Add to Cart + Options | `variable-product-add-to-cart-with-options` | Supplies selectors and the add-to-cart form for variable products. |
| Grouped product Add to Cart + Options | `grouped-product-add-to-cart-with-options` | Supplies selectors and the add-to-cart form for grouped products. |

The four Add to Cart + Options parts are registered on block themes for product types available on the site. A template part has no output until a template or block includes it.

```html
<!-- wp:template-part {"slug":"mini-cart","theme":"your-theme"} /-->
```

> [!WARNING]
> Add to Cart + Options and its inner blocks are marked Beta. Their structure can change between WooCommerce releases, so retest template-part overrides during upgrades.

## Override a template or template part

Add a file with the matching name to the active theme's `/templates` or `/parts` directory:

```text
your-theme/
├── parts/
│   └── mini-cart.html
└── templates/
    ├── archive-product.html
    ├── page-cart.html
    └── single-product.html
```

No PHP registration is required for an override. WordPress loads the theme file before WooCommerce's default as long as there is no higher-priority Site Editor customization.

Start from the current WooCommerce template when practical, then make the smallest necessary changes. Preserve blocks that supply functional behavior, such as store notices, product context, `core/post-content` on Cart and Checkout, and the Add to Cart flow.

If a merchant has already customized the template in the Site Editor, adding or changing a theme file will not update that saved database copy. Document significant template changes and explain when a merchant may need to review or reset a customization.

## Check which template is active

### Use WP-CLI

Current WP-CLI versions include `wp block template` commands for inspecting templates and template parts:

```bash
# List templates registered by plugins.
wp block template list --source=plugin

# List templates supplied by the active theme.
wp block template list --source=theme

# List customized templates saved in the database.
wp block template list --source=custom

# List registered template parts.
wp block template list --type=wp_template_part

# Inspect the block markup for a template.
wp block template get your-theme//single-product --field=content
```

Use `--slug` to narrow a list while debugging one template:

```bash
wp block template list --slug=single-product
```

A missing plugin template can indicate that the current WordPress or WooCommerce version does not register it, or that its registration conditions are not met.

### Check the file system and Site Editor

List the theme's override directories to see which files it provides:

```bash
ls your-theme/templates
ls your-theme/parts
```

In the Site Editor:

- Open **Appearance > Editor > Templates** and select the template.
- Use the **Code editor** to inspect its block markup.
- A single `woocommerce/legacy-template` block indicates the compatibility version.
- A **Customized** label indicates a database copy that takes precedence over theme and plugin files.

## References

- [Theming for Woo blocks](/docs/theming/block-theme-development/theming-woo-blocks/)
- [WordPress template hierarchy](https://developer.wordpress.org/themes/templates/template-hierarchy/)
- [WordPress `wp block template` commands](https://developer.wordpress.org/cli/commands/block/template/)
- [WooCommerce block template registry](https://woocommerce.github.io/code-reference/files/woocommerce-src-blocks-blocktemplatesregistry.html)
- [WooCommerce block template utilities](https://woocommerce.github.io/code-reference/files/woocommerce-src-blocks-utils-blocktemplateutils.html)

## Next steps

- [Global styles for WooCommerce](/docs/theming/block-theme-development/global-styles/)
- [Cart and Checkout blocks theming](/docs/theming/block-theme-development/cart-and-checkout/)
- [CSS styling for themes](/docs/theming/block-theme-development/css-styling/)
