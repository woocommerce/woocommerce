---
post_title: 'Block theme development: Template hierarchy reference'
sidebar_label: Template hierarchy
sidebar_position: 2
---

# Template hierarchy reference

Every WooCommerce store page renders from a template. In a Block theme, WooCommerce registers a full set of these by default, built from HTML Block templates rather than PHP. As such, a store works immediately after activation without a theme providing anything.

This reference maps every template and template part WooCommerce registers, when each one loads, and how a theme overrides or extends them. It builds directly on the other concepts in the [Block theme development](/docs/theming/block-theme-development/theming-woo-blocks/) section, so if any of the terms here feel unfamiliar, start there first.

## Requirements

Before you begin, make sure you have the following in place:

- **A Block theme.** The template registrations and fallback behavior covered here apply to Block themes. Classic themes use a separate, PHP-based template system, covered in the [classic theme developer handbook](https://developer.woocommerce.com/docs/theming/theme-development/classic-theme-developer-handbook/).
- **Familiarity with the Site Editor.** You should know how to open templates and view Block markup through the **Code editor**.
- **Familiarity with WordPress's Block template hierarchy.** This guide assumes you already know how WordPress resolves a template file to a request; it focuses on what WooCommerce adds on top of that system.

## The three layers behind every WooCommerce template

WooCommerce uses three related template systems, but not every store page has a distinct file in all three. It's a common source of confusion if you don't understand how they work together.

First, **Classic PHP templates** live under `plugins/woocommerce/templates/` as `.php` files and predate Block themes entirely. A classic theme overrides one by copying it into a `woocommerce/` directory within the theme.

Some of these files exist purely to redirect to another. For example, `taxonomy-product-cat.php` is a single line:

```php
<?php
wc_get_template( 'archive-product.php' );
```

If you go looking for a taxonomy template and find one of these, you've found the classic theme system. The two are unrelated and the classic template structure is [documented separately](https://developer.woocommerce.com/docs/theming/theme-development/template-structure/).

For Block themes, WooCommerce keeps two sets of HTML templates. The **classic-compatibility set** lives in `plugins/woocommerce/templates/templates/`. Its product catalog, product search, product attribute, and single product templates render the corresponding classic PHP template through a single `woocommerce/legacy-template` Block:

```html
<!-- wp:woocommerce/legacy-template {"template":"single-product"} /-->
```

The Cart, Checkout, Order Confirmation, and Coming Soon templates in this set already use Block markup instead of the legacy wrapper.

A **Blockified set** lives in `plugins/woocommerce/templates/templates/blockified/`. Its product-facing templates use individual WooCommerce and core WordPress Blocks, such as Product Image Gallery and Product Price, rather than the legacy wrapper.

WooCommerce only registers its standard Block templates when `wp_is_block_theme()` returns true. Blockified templates are the default for Block themes unless the store's `wc_blocks_use_blockified_product_grid_block_as_template` option explicitly retains the compatibility set. Every template in this documentation refers to the Blockified set unless stated otherwise.

:::tip
If a template looks different to what you expect, open it through the Site Editor's **Code editor** view. A single `woocommerce/legacy-template` Block at the top of the file means you're looking at the classic compatibility version, not the Blockified one.
:::

## The core store templates

WooCommerce registers 11 templates by default to cover every stage of the shopping experience.

| Template | Slug | Notes |
| :---- | :---- | :---- |
| Single Product | `single-product` | Renders an individual product page. |
| Product Catalog | `archive-product` | Renders the main shop page. Acts as the fallback for the four taxonomy templates below. |
| Products by Category | `taxonomy-product_cat` | No dedicated file; falls back to Product Catalog. |
| Products by Tag | `taxonomy-product_tag` | No dedicated file; falls back to Product Catalog. |
| Products by Attribute | `taxonomy-product_attribute` | WooCommerce ships a generic file, but Product Catalog remains the default fallback until a theme or merchant customizes the attribute template. |
| Products by Brand | `taxonomy-product_brand` | No dedicated file; falls back to Product Catalog. Brands were enabled by default starting in WooCommerce 9.6. |
| Product Search Results | `product-search-results` | Renders search results scoped to products. |
| Page: Coming soon | `coming-soon` | Registered regardless of store visibility; Launch Your Store determines when shoppers see it. |
| Page: Cart | `page-cart` | Wraps the page a merchant assigns as the Cart page. |
| Page: Checkout | `page-checkout` | Wraps the page a merchant assigns as the Checkout page. |
| Order Confirmation | `order-confirmation` | Renders after a successful purchase. |

Eight of these 11 exist as files you can open and copy into your theme's `/templates` folder to override:

- Single Product
- Product Catalog
- Products by Attribute
- Product Search Results
- Coming soon
- Cart
- Checkout
- Order Confirmation

Products by Category, Products by Tag, and Products by Brand do not need dedicated files. WordPress recognizes those requests as taxonomy archives, and WooCommerce inserts Product Catalog as their fallback. The fallback is set directly in each template's registration class:

```php
class ProductBrandTemplate extends AbstractTemplateWithFallback {
 const SLUG = 'taxonomy-product_brand';
 public string $fallback_template = ProductCatalogTemplate::SLUG;
 public bool $is_taxonomy_template = true;
 // ...
}
```

Products by Attribute is a special case. WooCommerce ships `taxonomy-product_attribute.html`, but its template class deliberately uses Product Catalog unless the active theme provides the attribute template or a merchant has customized it in the database. This prevents the plugin's generic attribute file from taking priority over a customized Product Catalog template.

As a result, a theme overriding `archive-product.html` gets the design applied automatically to all four taxonomy archives until the theme or a merchant creates a more specific template.

**Products by Brand** became part of WooCommerce core in 9.4 and was enabled by default starting in 9.6. Older stores can therefore differ depending on their WooCommerce version and whether the Brands feature was enabled.

:::info
The **Coming soon** template's file is `coming-soon.html`, not `page-coming-soon.html`. The "Page:" wording belongs to the template's title in the Site Editor, not its filename. WooCommerce registers the template even when Launch Your Store is inactive; store visibility determines when the template is used.
:::

## Cart, checkout, and order confirmation

The **Cart** and **Checkout** templates behave differently from the browsing templates. They wrap the content a merchant assigns through the **Pages** screen rather than content WooCommerce queries directly.

Both `page-cart.html` and `page-checkout.html` have a similar structure. The Cart template's main content is:

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

The Checkout template uses the same wrapper with `{"page":"checkout"}`, but it omits the Post Title Block. In both templates, `core/post-content` renders the Cart or Checkout Block assigned to that page.

:::info
If you override either template, keep the Cart or Checkout Block inside the content rendered by `core/post-content` rather than hardcoding `woocommerce/cart` or `woocommerce/checkout` directly into the template. Bypassing the assigned page content this way desyncs the page editor from the frontend. Some WooCommerce functionality that checks for the presence of those Blocks on the assigned page could break.
:::

The **Order Confirmation** template does not wrap an assigned page and has no placeholder page. Its dedicated template renders data from the order-received endpoint through Order Confirmation Blocks for the status, summary, totals, downloads, addresses, additional fields, and additional information.

Styling the Cart and Checkout Blocks (including the mobile checkout button and associated CSS containers) is covered in [Cart and Checkout blocks theming](https://developer.woocommerce.com/docs/theming/block-theme-development/cart-and-checkout/).

## Template parts

Template parts live in a theme's `/parts` folder, separate from full templates in `/templates`. WooCommerce registers several of these smaller, reusable sections that a template pulls into a Template Part Block rather than owning the markup directly.

A template part only renders once something includes it, so registering one changes nothing on its own until a template references it:

```html
<!-- wp:template-part {"slug":"mini-cart","theme":"your-theme"} /-->
```

WooCommerce supplies seven template-part files:

- **Mini-Cart** (`mini-cart`) supplies the contents of the Mini-Cart drawer, the panel that opens without navigating away from the current page when a shopper adds an item to cart.
- **Checkout header** (`checkout-header`) supplies the header used specifically on the Checkout template (separate from the site's main header), so a merchant can strip distracting navigation away from the payment step.
- **Coming soon social links** (`coming-soon-social-links`) supplies the reusable social-links section included by WooCommerce's Coming Soon patterns.
- **Simple, External, Variable, and Grouped product `add-to-cart-with-options`** parts handle the add-to-cart form on the product page, one per product type. Their registry entries depend on product types registered with WooCommerce, not on whether the catalog currently contains products of each type. WooCommerce's four core product types normally make all four parts available.

The four `add-to-cart-with-options` parts sit inside its own template part area, separate from the standard header and footer areas, and back the Add to Cart and Options Block. This lets a theme or merchant give each product type its own layout, such as a stepper for a simple product's quantity field, instead of one form that adapts its own contents internally.

:::info
The Add to Cart and Options Block was introduced in WooCommerce 10.0 and is still in Beta at the time of writing. As such, treat the underlying template parts as subject to change.
:::

A theme overrides any template part similarly to a template: by adding a matching file to its own `/parts` folder. WordPress also recognizes the older `/block-template-parts` folder name for backward compatibility, but new themes should use `/parts`.

## Overriding templates and template parts

WooCommerce's default templates are built to work for any store, regardless of catalog size, product mix, or industry. These generalities mean the defaults need tweaking often: a theme built around a particular grid, typography scale, or Block arrangement usually wants its own version of at least the Single Product and Product Catalog templates, even if it never touches the rest.

Overriding a template lets you change the Block composition and layout while keeping WooCommerce's underlying logic (such as pricing, availability, and the 'add-to-cart' flow) intact.

You override a WooCommerce template or template part by placing a file with the matching name in its own `/templates` or `/parts` folder:

```text
your-theme/
├── templates/
│   ├── single-product.html
│   ├── archive-product.html
│   └── page-cart.html
└── parts/
    └── mini-cart.html
```

There's no registration step required beyond placing the file. A merchant's database customization takes priority, followed by the active theme's file and then WooCommerce's default.

Beyond a plain override, WordPress's template hierarchy supports two narrower levels of targeting:

- **Product-specific templates.** `single-product-{slug}.html` (where `{slug}` is a specific product's slug) takes priority over the general `single-product.html` template whenever the product is viewed. This suits a flagship product or seasonal item that needs a different layout from the rest of the catalog.
- **Term-specific templates.** `taxonomy-product_cat-{term}.html` renders only when a shopper views the specific category, falling back to `taxonomy-product_cat.html` if it exists, then to `archive-product.html` if neither more specific file does.

The latter gives a theme three levels of specificity for any taxonomy-driven page without needing conditional logic to detect the category being displayed. WordPress resolves the correct file on its own, based on the request.

### Visualizing the fallback chain

Seeing both fallback chains side-by-side makes the specificity easier to follow than reading them as separate bullet points:

| Product request | Taxonomy request |
| :---- | :---- |
| `single-product-{slug}.html` | `taxonomy-{taxonomy}-{term}.html` |
| `single-product.html` | `taxonomy-{taxonomy}.html` |
| WooCommerce's Single Product template | `archive-product.html` |
| WordPress's broader singular fallbacks | WordPress's broader taxonomy and archive fallbacks |

In a nutshell, build overrides with this fallback chain in mind rather than against it. A theme that wants one consistent design across every taxonomy archive only needs to touch `archive-product.html`; a theme that wants Products by Category to diverge from Products by Tag only needs to add the one file that should differ. The rest can inherit the parent.

:::info
Merchants can customize any of these templates from the Site Editor regardless of what a theme provides. These customizations save to the database rather than a file and take priority over both the theme's file and WooCommerce's default. As such, document any custom template you ship with enough context so that merchants understand what it's built around.
:::

## Checking your work

Which template loads and the fallback that's applied depends on the WooCommerce version and theme in use. To confirm this rather than assume once you've built an override, you can use the `wp block template` commands.

These are bundled with WP-CLI 3.0.0 by default and let you carry out checks without opening the Site Editor.

:::note
At the time this documentation was written, WP-CLI 3.0.0 was still in prerelease. Until the stable release is available, use a nightly build with `wp cli update --nightly` or install the command separately.
:::

```bash
# List every template WooCommerce or another plugin registers
# independent of theme overrides
wp block template list --source=plugin

# List the templates your theme is currently overriding
wp block template list --source=theme

# List registered template parts
wp block template list --type=wp_template_part

# Dump the Block markup for a given template to confirm
# whether it's the Blockified version or the legacy-template wrapper
wp block template get your-theme//single-product --field=content
```

`wp block template list --source=plugin` is the most direct way to inspect which templates a plugin registers at runtime. Registration is not the same as active selection: for example, Coming Soon remains registered even when Launch Your Store is inactive.

A missing entry can reflect an older WooCommerce version, disabled functionality, the active theme type, or a source filter. Filtering by `--slug` narrows the list to one template at a time, which is faster than scrolling the full output while debugging a single override.

### Filesystem and Site Editor checking

Checking a theme's own file system is the simplest confirmation of all: `ls your-theme/templates` and `ls your-theme/parts` tell you exactly which overrides exist without loading WordPress.

If you don't have shell access to the environment, the Site Editor covers the same ground:

- **Open the template through Appearance > Editor > Templates**, select the template, and use the **Code editor** option to view Block markup. A single `woocommerce/legacy-template` Block at the top means you're looking at the classic-compatibility version rather than the Blockified one.
- **Check the full Templates list** to see which core templates and parts are registered for the active theme. A missing Products by Brand entry can indicate an older WooCommerce version or a store where the Brands feature is unavailable.
- **Look for a Customized label** next to a template's name in the list. It means a merchant has edited the template directly from the Site Editor and that saved version in the database is what's rendering rather than your theme's file or WooCommerce's default.

These checks ultimately give you a reliable path from a bare Block theme to a fully customized WooCommerce store: knowing the relationship between template rendering for a given page and understanding its fallback chain, then overriding it.
