# BlockTemplateController.php <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Overview](#overview)
-   [add_block_templates( $query_result, $query, \$template_type )](#add_block_templates-query_result-query-template_type-)
-   [get_block_file_template( $template, $id, \$template_type )](#get_block_file_template-template-id-template_type-)

The `BlockTemplateController` class contains all the business logic which loads the templates into the Site Editor or on the front-end through various hooks available in WordPress & WooCommerce core. Without documenting every method individually, I will look to provide some insight into key functionality.

## Overview

In the initialization of the class, we hook into the three hooks listed below. These provide us with all of the extensibility points necessary in order to load our own block templates alongside the themes.

Within each method section, I will explain in what scenarios they are executed.

-   filter: `get_block_templates` with `add_block_templates`.
-   filter: `pre_get_block_file_template` with `get_block_file_template`.
-   action: `template_redirect` with `render_block_template`.

## add_block_templates( $query_result, $query, \$template_type )

This method is applied to the filter `get_block_templates`, which is executed before returning a unified list of template objects based on a query.

**Typically executed when:**

-   Loading the "All Templates" view in the Site Editor
-   Loading one of the templates on the front-end where the query would build a list of relevant templates based on a hierarchy (for example, the product page hierarchy could be an array containing `single-product-[product-name].html`, `single-product.html`, `single.html`).
-   Loading the "Edit Product" view.

**This method is responsible for:**

-   Giving our templates a user-friendly title (e.g. turning "single-product" into "Product Page").
-   It collects all the WooCommerce templates from both the filesystem and the database (customized templates are stored in the database as posts) and adds them to the returned list.
-   In the event the theme has a `archive-product.html` template file, but not category/tag/attribute template files, it is eligible to use the `archive-product.html` file in their place. So we trick Gutenberg in thinking some templates (e.g. category/tag/attribute) have a theme file available if it is using the `archive-product.html` template, even though _technically_ the theme does not have a specific file for them.
-   Ensuring we do not add irrelevant WooCommerce templates in the returned list. For example, if `$query['post_type']` has a value (e.g. `product`) this means the query is requesting templates related to that specific post type, so we filter out any irrelevant ones. This _could_ be used to show/hide templates from the template dropdown on the "Edit Product" screen in WP Admin.

**Return value:**

This method will return an array of `WP_Block_Template` values

## get_block_file_template( $template, $id, \$template_type )

This method is applied to the filter `pre_get_block_file_template` inside the WordPress core function `get_block_file_template` (not to be confused with this method from the `BlockTemplateController` class, which has the same name).

The order of execution is as follows:

1. `get_block_template()` from WordPress core will execute, and attempt to retrieve a customized version of the template from the database.
2. If it fails to retrieve one, it will execute the `get_block_file_template()` function from WordPress core which will apply the filter `pre_get_block_file_template`. This is where we hook in to to return our template file, and trigger an early return to prevent WordPress from continuing its query.

During step 2 it's important we hook into the `pre_get_block_file_template`. If we don't, the function will check if the first part of the template ID (e.g. `woocommerce/woocommerce`) is the same as the current themes ID (e.g. `twentytwentytwo`), which will resolve `false` and return `null` instead of the expected `WP_Block_Template` object.

**Typically executed when:**

-   A user clears the customizations of a WooCommerce template.

**This method is responsible for:**

-   Loading the template files from the filesystem, and building a `WP_Block_Template` version of it.

**Return value:**

This method will return `WP_Block_Template` or `null`.
