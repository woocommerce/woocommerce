# Classic Template Block <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Usage](#usage)
    -   [Props](#props)
-   [Internals](#internals)
-   [Extensibility](#extensibility)
    -   [PHP actions and filters](#php-actions-and-filters)
    -   [PHP template parts](#php-template-parts)

The Classic Template block is a placeholder block for specific WooCommerce block templates which are rendered on the server side when a block theme is active. In the editor UI, it's presented with these names:

- `Product (Classic)`,
- `Product Attribute (Classic)`,
- `Product Taxonomy (Classic)`,
- `Product Tag (Classic)`,
- `Product Search Results (Classic)`,
- `Product Grid (Classic)`.

When the Classic Template block is clicked, a button appears to 'Transform into blocks'. Clicking it updates the template so it's composed of more granular blocks. That's what we commonly refer to as the 'blockified template'. The blockified template has several advantages over using the Classic Template block, like allowing users to hide some blocks from the template, changing their order or making more granular modifications.

<p align="center"><!-- markdownlint-disable-line no-inline-html -->
  <img src="https://github.com/woocommerce/woocommerce/assets/3616980/c996d4e8-8839-4542-b6a3-9f01627c482d" alt="Classic Template block in the Single Product template" width="400"/><!-- markdownlint-disable-line no-inline-html -->
</p><!-- markdownlint-disable-line no-inline-html -->

Once the user has switched to the blockified template, a button will appear on the 'Template' sidebar allowing them to revert to the classic template, which adds back the Classic Template block.

## Usage

This block does not have any customizable options available, so any style or customization updates will not be reflected on the placeholder.

### Props

-   `attributes`
    -   `template`: `single-product` | `archive-product` | `taxonomy-product_cat` | `taxonomy-product_tag` | `taxonomy-product_attribute`
    -   `align`: `wide` | `full`

```html
<!-- wp:woocommerce/legacy-template {"template":"single-product"} /-->
```

By assigning a template identifier to the attribute prop, the block will render that specific template on the front-end, and a placeholder for said template in the Site Editor.

It's worth noting that the placeholder in the Site Editor is merely an approximate representation of a template's front-end view.

## Internals

The `ClassicTemplate` class is used to set up the Classic Template block server-side. This class takes care of rendering the correct template and is also responsible of enqueuing the front-end scripts necessary to enable dynamic functionality, such as the product gallery, add to cart, etc.

From the `render()` method we inspect the `$attributes` object for a `template` property which helps determine which core PHP templating code to execute (e.g. `single-product`) for the front-end views.

## Extensibility

> [!IMPORTANT]
> Before customizing or extending the Classic Template block, keep in mind that users can easily switch from the Classic Template block to the blockified version of the templates, so any customizations or extensibility changes should cover both paradigms.

### PHP actions and filters

> [!NOTE]
> Using PHP actions and filters to customize the look-and-feel of a store with a block theme is discouraged. We recommend using [blocks](https://developer.wordpress.org/block-editor/), [global styles](https://developer.wordpress.org/themes/global-settings-and-styles/), [block hooks](https://make.wordpress.org/core/2023/10/15/introducing-block-hooks-for-dynamic-blocks/), and other block-based APIs. However, these filters can be useful to port some customizations from the blocks into the Classic Template block.

Internally, the `ClassicTemplate` class triggers several PHP hooks that are shared with classic themes. Those are:

- `woocommerce_before_main_content`
- [`woocommerce_show_page_title`](https://github.com/woocommerce/woocommerce/blob/f040e3acf7df9420a09d37b84358ac7d2e03b8a3/plugins/woocommerce/src/Blocks/BlockTypes/ClassicTemplate.php#L264) (only in Product archive templates)
- [`woocommerce_archive_description`](https://github.com/woocommerce/woocommerce/blob/f040e3acf7df9420a09d37b84358ac7d2e03b8a3/plugins/woocommerce/src/Blocks/BlockTypes/ClassicTemplate.php#L281) (only in Product archive templates)
- [`woocommerce_before_shop_loop`](https://github.com/woocommerce/woocommerce/blob/f040e3acf7df9420a09d37b84358ac7d2e03b8a3/plugins/woocommerce/src/Blocks/BlockTypes/ClassicTemplate.php#L296) (only in Product archive templates)
- [`woocommerce_shop_loop`](https://github.com/woocommerce/woocommerce/blob/f040e3acf7df9420a09d37b84358ac7d2e03b8a3/plugins/woocommerce/src/Blocks/BlockTypes/ClassicTemplate.php#L309) (only in Product archive templates)
- [`woocommerce_after_shop_loop`](https://github.com/woocommerce/woocommerce/blob/f040e3acf7df9420a09d37b84358ac7d2e03b8a3/plugins/woocommerce/src/Blocks/BlockTypes/ClassicTemplate.php#L324) (only in Product archive templates)
- [`woocommerce_no_products_found`](https://github.com/woocommerce/woocommerce/blob/f040e3acf7df9420a09d37b84358ac7d2e03b8a3/plugins/woocommerce/src/Blocks/BlockTypes/ClassicTemplate.php#L333) (only in Product archive templates)
- `woocommerce_after_main_content`

Those hooks except `woocommerce_show_page_title` and `woocommerce_shop_loop` are also applied to the blockified version of templates thanks to the [compatibility layer](../../../../docs/internal-developers/blockified-templates/compatibility-layer.md).

### PHP template parts

> [!NOTE]
> Using PHP template parts to customize the look-and-feel of a store with a block theme is discouraged. We recommend using [blocks](https://developer.wordpress.org/block-editor/), [global styles](https://developer.wordpress.org/themes/global-settings-and-styles/), [block hooks](https://make.wordpress.org/core/2023/10/15/introducing-block-hooks-for-dynamic-blocks/), and other block-based APIs. However, these template parts can be useful to port some customizations from the blocks into the Classic Template block.

> [!CAUTION]
> Unlike most PHP actions and filters mentioned above, the PHP template parts are not applied in the blockified version of the templates, so you should make sure to build a version of the same changes that works with blocks.

The `ClassicTemplate` class renders a couple of PHP template parts. Those are [`content-single-product` template part](https://github.com/woocommerce/woocommerce/blob/f040e3acf7df9420a09d37b84358ac7d2e03b8a3/plugins/woocommerce/src/Blocks/BlockTypes/ClassicTemplate.php#L213) in the Single Product version of the Classic Template block and the [`content-product` template part](https://github.com/woocommerce/woocommerce/blob/f040e3acf7df9420a09d37b84358ac7d2e03b8a3/plugins/woocommerce/src/Blocks/BlockTypes/ClassicTemplate.php#L311) when rendering the Product archive templates. Themes can override those template parts as they would normally do in classic themes, with `content-single-product.php` and `content-product.php` files. However, remember that these templates parts are not used in the blockified version of the templates, so any changes to them won't be applied to the granular blocks.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./docs/README.md)

<!-- /FEEDBACK -->
