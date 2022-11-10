# Templates <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Overview](#overview)
    -   [Requirements](#requirements)
-   [Technical Overview](#technical-overview)
    -   [The Problem](#the-problem)
    -   [The Solution](#the-solution)
    -   [Some things to be aware of](#some-things-to-be-aware-of)
-   [Related files](#related-files)

This page includes documentation related to WooCommerce Block Templates.

## Overview

WooCommerce Block Templates are a collection of WooCommerce Core templates for the WordPress Full Site Editing experience introduced in WordPress 5.9. You can customize these templates in the Site Editor.

You can read more about the Full Site Editing (FSE) experience [here](https://developer.wordpress.org/block-editor/getting-started/full-site-editing/).

### Requirements

| Software    | Minimum Version                                                                                                                  |
| ----------- | -------------------------------------------------------------------------------------------------------------------------------- |
| WordPress   | 5.9                                                                                                                              |
| WooCommerce | 6.0                                                                                                                              |
| Theme       | Any [block theme](https://developer.wordpress.org/block-editor/how-to-guides/themes/block-theme-overview/#what-is-a-block-theme) |

## Technical Overview

### The Problem

Currently, the FSE feature does not accommodate loading block templates from plugins such as WooCommerce (or WooCommerce Blocks). Instead, all template files loaded natively must be present within the active theme's templates folder. When the active block theme doesn't come with WooCommerce specific templates, we want to be able to provide sensible defaults, though.

### The Solution

We created a custom solution which involves a handful of files (listed in the below table) that are responsible for both making the templates available as a block template, and rendering it on the front-end.

The BlockTemplateController.php is primarily responsible for hooking into both WordPress core, and WooCommerce core filters to load WooCommerce [templates](https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/templates/templates) in the Site Editor and on the front-end. It is in this class where the majority of the logic is handled.

### Some things to be aware of

-   Individual templates are represented by a placeholder block within the Site Editor until we can 'block-ify' these. This is the long-term goal. Until then users will be able to customize templates by adding blocks above and below the placeholder.
-   At the beginning of the project, we unintentionally used the incorrect WooCommerce plugin slug. This has resulted in us maintaining both the incorrect and correct slugs. We reference these via `BlockTemplateUtils::DEPRECATED_PLUGIN_SLUG` and `BlockTemplateUtils::PLUGIN_SLUG`. More information on that [here](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/5423).
-   If a theme has a `archive-product.html` template file, but does not have any taxonomy related files. We will automatically duplicate their themes `archive-product.html` file in place of these taxonomy files within `BlockTemplateController->get_block_file_template()`.
-   In `BlockTemplateController->get_block_templates()` we filter out templates in production that are behind a feature flag. These will be available in development, but will be absent in production unless the feature flag is removed.

## Related files

| File                        | Description                                                                                                                                                                     | Source                                                                                                                           | Docs                                                           |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------- |
| templates/templates/\*      | Location in the filesystem where WooCommerce block template HTML files are stored.                                                                                              | [Source files](https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/templates/templates)               |                                                                |
| classic-template/\*         | The JavaScript block rendered in the Site Editor. This is a server-side rendered component which is handled by ClassicTemplate.php                                              | [Source file](https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/assets/js/blocks/classic-template)  | [README](../../../assets/js/blocks/classic-template/README.md) |
| ClassicTemplate.php         | Class used to setup the block on the server-side and render the correct template                                                                                                | [Source file](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/src/BlockTypes/ClassicTemplate.php) | [README](./classic-template.md)                                |
| BlockTemplateController.php | Class which contains all the business logic which loads the templates into the Site Editor or on the front-end through various hooks available in WordPress & WooCommerce core. | [Source file](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/src/BlockTemplatesController.php)   | [README](./block-template-controller.md)                       |
| BlockTemplateUtils.php      | Class containing a collection of useful utility methods.                                                                                                                        | [Source file](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/src/Utils/BlockTemplateUtils.php)   |                                                                |
