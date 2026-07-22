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

- **A current version of WooCommerce.** This guide reflects current registrations, including Products by Brand introduced in WooCommerce 9.6.
- **A Block theme.** The template registrations and fallback behavior covered here apply to Block themes. Classic themes use a separate, PHP-based template system, covered in the [classic theme developer handbook](https://developer.woocommerce.com/docs/theming/theme-development/classic-theme-developer-handbook/).
- **Familiarity with the Site Editor.** You should know how to open templates and view Block markup through the **Code editor**.
- **Familiarity with WordPress's Block template hierarchy.** This guide assumes you already know how WordPress resolves a template file to a request; it focuses on what WooCommerce adds on top of that system.

## The three layers behind every WooCommerce template

WooCommerce ships up to three versions of each individual template. It's a common source of confusion if you don't get how they work together.

First, **Classic PHP templates** live under /woocommerce/templates/ as .php files and predate Block themes entirely. A classic theme overrides one by copying it into a woocommerce/ directory within the theme.

Some of these files exist purely to redirect to another. For example, taxonomy-product-cat.php is a single line:

```php
<?php
wc_get_template( 'archive-product.php' );
```

If you go looking for a taxonomy template and find one of these, you've found the classic theme system. The two are unrelated and the classic template structure is [documented separately](https://developer.woocommerce.com/docs/theming/theme-development/template-structure/).

When it comes to **Block templates**, you have two types. The **classic-compatibility version** is where WooCommerce also registers a Block template edition of every core page. Part of this is a version built from a single woocommerce/legacy-template Block, which renders the classic PHP template inside a wrapper:

```html
<!-- wp:woocommerce/legacy-template {"template":"single-product"} /-->
```

This exists so a Block theme still renders correctly even before it opts into fully 'Blockified' markup.

A **Blockified version** of a Block template is what most Block themes use. The template is made up of individual WooCommerce Blocks such as Product Image Gallery or Product Price rather than one wrapper Block.

WooCommerce decides which of the last two to serve based on wp_is_block_theme(). The Blockified version is the default for Block themes. It only falls back to the legacy template wrapper if a store switches to a classic theme without any other changes. Every template in this documentation refers to this unless stated otherwise.

> [!TIP]
> If a template looks different to what you expect, open it through the Site Editor's **Code editor** view. A single woocommerce/legacy-template Block at the top of the file means you're looking at the classic compatibility version, not the Blockified one.

## The core store templates

WooCommerce registers 11 templates by default to cover every stage of the shopping experience.

| Template | Slug | Notes |
| :---- | :---- | :---- |
| Single Product | single-product | Renders an individual product page. |
| Product Catalog | archive-product | Renders the main shop page. Acts as the fallback for the four taxonomy templates below. |
| Products by Category | taxonomy-product_cat | No dedicated file; falls back to Product Catalog. |
| Products by Tag | taxonomy-product_tag | No dedicated file; falls back to Product Catalog. |
| Products by Attribute | taxonomy-product_attribute | No dedicated file; falls back to Product Catalog. |
| Products by Brand | taxonomy-product_brand | No dedicated file; falls back to Product Catalog. Default since WooCommerce 9.6. |
| Product Search Results | product-search-results | Renders search results scoped to products. |
| Page: Coming soon | coming-soon | Only registers when Launch Your Store is active. |
| Page: Cart | page-cart | Wraps the page a merchant assigns as the Cart page. |
| Page: Checkout | page-checkout | Wraps the page a merchant assigns as the Checkout page. |
| Order Confirmation | order-confirmation | Renders after a successful purchase. |

Seven of these 11 exist as real files you can open and copy into your theme's /templates folder to override:

- Single Product
- Product Catalog
- Product Search Results
- Coming soon
- Cart
- Checkout
- Order Confirmation

With the four taxonomy templates, WordPress resolves them automatically once it recognizes a request as a taxonomy archive. WooCommerce only needs to declare a fallback rather than ship a file, which is set directly in the template's registration class:

```php
class ProductBrandTemplate extends AbstractTemplateWithFallback {
 const SLUG = 'taxonomy-product_brand';
 public string $fallback_template = ProductCatalogTemplate::SLUG;
 public bool $is_taxonomy_template = true;
 // ...
}
```

As such, a theme overriding archive-product.html gets the design applied automatically to every taxonomy archive, up until the theme or a merchant creates a specific template for one of them.

**Products by Brand** is featured in WooCommerce 9.6 and migrates in from the standalone WooCommerce Brands extension. It means it may not be present on an older WooCommerce install and might not have it configured even where it exists.

> [!NOTE]
> The **Coming soon** template's file is coming-soon.html, not page-coming-soon.html. The "Page:" wording belongs to the template's title in the Site Editor, not its filename. It only registers when Launch Your Store is active.

## Cart, checkout, and order confirmation templates {#cart-checkout-and-order-confirmation}

The **Cart**, **Checkout**, and **Order Confirmation** templates behave differently from the browsing templates. This is due to how they wrap the content a merchant assigns through the **Pages** screen, rather than the content WooCommerce will query.

Both page-cart.html and page-checkout.html have a similar structure:

```html
<!-- wp:woocommerce/page-content-wrapper -->
 <!-- wp:woocommerce/store-notices /-->
 <!-- wp:post-title {"level":1} /-->
 <!-- wp:post-content /-->
<!-- /wp:woocommerce/page-content-wrapper -->
```

However, the Checkout template doesn't use the Title Block. Regardless, core/post-content is what actually renders the Cart or Checkout Block assigned to that page.

> [!NOTE]
> If you override either template, keep the Cart or Checkout Block inside the content rendered by core/post-content rather than hardcoding woocommerce/cart or woocommerce/checkout directly into the template. Bypassing the assigned page content this way desyncs the page editor from the frontend. Some WooCommerce functionality that checks for the presence of those Blocks on the assigned page could break.

Styling the Cart and Checkout Blocks (including the mobile checkout button and associated CSS containers) is covered in [Cart and Checkout blocks theming](https://developer.woocommerce.com/docs/theming/block-theme-development/cart-and-checkout/).

## Template parts

Template parts live in a theme's /parts folder, separate from full templates in /templates. WooCommerce registers several of these smaller, reusable sections that a template pulls into a Template Part Block rather than owning the markup directly.

A template part only renders once something includes it, so registering one changes nothing on its own until a template references it:

```html
<!-- wp:template-part {"slug":"mini-cart","theme":"your-theme"} /-->
```

WooCommerce registers six template parts in total, covering three areas of a store:

- **Mini-Cart** (mini-cart) supplies the contents of the Mini-Cart drawer, the panel that opens without navigating away from the current page when a shopper adds an item to cart.
- **Checkout header** (checkout-header) supplies the header used specifically on the Checkout template (separate from the site's main header), so a merchant can strip distracting navigation away from the payment step.
- **Simple, External, Variable, and Grouped product 'add-to-cart-with-options'** parts handle the add-to-cart form on the product page (one per product type). WooCommerce only registers the parts matching product types that actually exist in the store, so a catalog with no grouped products never registers that part.

The four add-to-cart-with-options parts sit inside its own template part area, separate from the standard header and footer areas, and back the Add to Cart and Options Block. This lets a theme or merchant give each product type its own layout, such as a stepper for a simple product's quantity field, instead of one form that adapts its own contents internally.

> [!NOTE]
> The Add to Cart and Options Block was introduced in WooCommerce 10.0 and is still in Beta at the time of writing. As such, treat the underlying template parts as subject to change.

A theme overrides any template part similarly to a template: by adding a matching file to its own /parts folder. WordPress also recognizes the older /template-parts folder name for backward compatibility, so check which one your theme uses before assuming an override isn't working.

## Overriding templates and template parts

WooCommerce's default templates are built to work for any store, regardless of catalog size, product mix, or industry. These generalities mean the defaults need tweaking often: a theme built around a particular grid, typography scale, or Block arrangement usually wants its own version of at least the Single Product and Product Catalog templates, even if it never touches the rest.

Overriding a template lets you change the Block composition and layout while keeping WooCommerce's underlying logic (such as pricing, availability, and the 'add-to-cart' flow) intact.

You override a WooCommerce template or template part by placing a file with the matching name in its own /templates or /parts folder:

```text
your-theme/
├── templates/
│   ├── single-product.html
│   ├── archive-product.html
│   └── page-cart.html
└── parts/
    └── mini-cart.html
```

There's no registration step required beyond placing the file. WordPress checks the active theme's folder first for each request and only falls back to WooCommerce's own version when nothing matches.

Beyond a plain override, WordPress's template hierarchy supports two narrower levels of targeting:

- **Product-specific templates.** `Single-product-{slug}.html` (where `{slug}` is a specific product's slug) takes priority over the general `single-product.html` template whenever the product is viewed. This suits a flagship product or seasonal item that needs a different layout from the rest of the catalog.
- **Term-specific templates.** `taxonomy-product_cat-{term}.html` renders only when a shopper views the specific category, falling back to `taxonomy-product_cat.html` if it exists, then to `archive-product.html` if neither more specific file does.

The latter gives a theme three levels of specificity for any taxonomy-driven page without needing conditional logic to detect the category being displayed. WordPress resolves the correct file on its own, based on the request.

### Visualizing the fallback chain

Seeing both fallback chains side-by-side makes the specificity easier to follow than reading them as separate bullet points:

**[DIAGRAM PLACEHOLDER]**

In a nutshell, build overrides with this fallback chain in mind rather than against it. A theme that wants one consistent design across every taxonomy archive only needs to touch archive-product.html; a theme that wants Products by Category to diverge from Products by Tag only needs to add the one file that should differ. The rest can inherit the parent.

> [!NOTE]
> Merchants can customize any of these templates from the Site Editor regardless of what a theme provides. These customizations save to the database rather than a file and take priority over both the theme's file and WooCommerce's default. As such, document any custom template you ship with enough context so that merchants understand what it's built around.

## Checking your work

Which template loads and the fallback that's applied depends on the WooCommerce version and theme in use. To confirm this rather than assume once you've built an override, you can use the wp block template commands.

These are bundled with WP-CLI by default and let you carry out checks without opening the Site Editor:

```bash
# List every template WooCommerce or another plugin registers
# independent of theme overrides
wp block template list --source=plugin

# List the templates your theme is currently overriding
wp block template list --source=theme

# Confirm registered template parts for the store's product types
wp block template list --type=wp_template_part

# Dump the Block markup for a given template to confirm
# whether it's the Blockified version or the legacy-template wrapper
wp block template get your-theme//single-product --field=content
```

wp block template list --source=plugin is the most direct way to find out whether a conditionally registered template or part is present on a store. For instance, this could be Products by Brand or the 'add-to-cart-with-options' parts for a product type the catalog doesn't carry.

A missing entry here usually means the condition isn't met, either through an older WooCommerce version, disabled functionality, or an empty product type rather than a bug. Filtering by --slug narrows any of these to one template at a time, which is faster than scrolling a full list while debugging a single override.

### Filesystem and Site Editor checking

Checking a theme's own file system is the simplest confirmation of all: `ls` your-theme/templates and ls your-theme/parts tell you exactly which overrides exist without loading WordPress.

If you don't have shell access to the environment, the Site Editor covers the same ground:

- **Open the template through Appearance > Editor > Templates**, select the template, and use the **Code editor** option to view Block markup. A single woocommerce/legacy-template Block at the top means you're looking at the classic-compatibility version rather than the Blockified one.
- **Check the full Templates list** to see which core templates and parts are registered for the active theme. For example, a missing entry for Products by Brand usually means the store is running a WooCommerce version older than 9.6.
- **Look for a Customized label** next to a template's name in the list. It means a merchant has edited the template directly from the Site Editor and that saved version in the database is what's rendering rather than your theme's file or WooCommerce's default.

These checks ultimately give you a reliable path from a bare Block theme to a fully customized WooCommerce store: knowing the relationship between template rendering for a given page and understanding its fallback chain, then overriding it.

## References

- [Theming for Woo Blocks](https://developer.woocommerce.com/docs/theming/block-theme-development/theming-woo-blocks/)
- [Template structure and overriding templates via a theme](https://developer.woocommerce.com/docs/theming/theme-development/template-structure/)
- [Cart and Checkout Blocks theming](https://developer.woocommerce.com/docs/theming/block-theme-development/cart-and-checkout/)
- [Add to Cart + Options Block](https://woocommerce.com/document/add-to-cart-options-block/)
- [Introducing Brands](https://developer.woocommerce.com/2024/10/01/introducing-brands/)
- [WooCommerce Code Reference: BlockTemplatesRegistry.php](https://woocommerce.github.io/code-reference/files/woocommerce-src-blocks-blocktemplatesregistry.html)
- [WooCommerce Code Reference: BlockTemplateUtils.php](https://woocommerce.github.io/code-reference/files/woocommerce-src-blocks-utils-blocktemplateutils.html)
- [WP-CLI: wp block template](https://developer.wordpress.org/cli/commands/block/template/)

## Next steps

This guide covers which templates and template parts exist and how to override them. The rest of the section builds on it with styling and page-level customization:

1. [Global styles for WooCommerce](/docs/theming/block-theme-development/global-styles/). Using theme.json to style the Blocks a template renders.
2. Product page theming. A Block-by-Block breakdown of the Single Product template's contents.
3. Shop and archive page theming. The same treatment applied to the Product Catalog and taxonomy templates.
4. [Cart and Checkout theming](https://developer.woocommerce.com/docs/theming/block-theme-development/cart-and-checkout/). Styling the Cart and Checkout Blocks, including buttons and the mobile submit container.
5. [CSS styling](https://developer.woocommerce.com/docs/theming/block-theme-development/css-styling/). CSS approaches and class name conventions for what theme.json doesn't cover.
6. [Theming WooCommerce Blocks](https://developer.woocommerce.com/docs/theming/block-theme-development/theming-woo-blocks/). An overview of core concepts, Block templates, and template structure.
