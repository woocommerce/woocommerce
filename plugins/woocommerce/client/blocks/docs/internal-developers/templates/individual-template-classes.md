# Individual template classes <!-- omit in toc -->

Each WooCommerce template has its own individual PHP class, which are all registered in BlockTemplatesRegistry.

## Overview

These classes have several purposes:

* define the template metadata like title and description
* hook into `template_redirect` to decide when to render the template (more on this below)
* any other hook or logic specific to that template

## render_block_template()

This method is applied to the filter `template_redirect` and executed before WordPress determines which template to load.

This allows us to hook into WooCommerce core through the filter `woocommerce_has_block_template` where we can determine if the block template should be loaded.

**Typically executed when:**

* A user loads a page on the front-end.

**This method is responsible for:**

* Determining if the block template has to be rendered in the current page. If so, we override the value through `woocommerce_has_block_template` to resolve `true`.
* Determining if the page that will be rendered contains a Legacy Template block, in which case we disable the compatibility layer through the `woocommerce_disable_compatibility_layer` filter.

**Return value:**

Void. This method does not return a value but rather sets up hooks to render block templates on the front-end.
