<!-- DO NOT UPDATE THIS DOC DIRECTLY -->

<!-- Use `npm run build:docs` to automatically build hook documentation -->

# Filters

## Table of Contents

- [__experimental_woocommerce_blocks_add_data_attributes_to_block](#__experimental_woocommerce_blocks_add_data_attributes_to_block)
- [__experimental_woocommerce_blocks_add_data_attributes_to_namespace](#__experimental_woocommerce_blocks_add_data_attributes_to_namespace)
- [__experimental_woocommerce_blocks_payment_gateway_features_list](#__experimental_woocommerce_blocks_payment_gateway_features_list)
- [__experimental_woocommerce_store_api_batch_request_methods](#__experimental_woocommerce_store_api_batch_request_methods)
- [`__experimental_woocommerce_{$product_type}_add_to_cart_with_options_block_template_part`](#__experimental_woocommerce_product_type_add_to_cart_with_options_block_template_part)
- [comment_text](#comment_text)
- [deprecated_function_trigger_error](#deprecated_function_trigger_error)
- [wc_session_expiration](#wc_session_expiration)
- [woocommerce_add_cart_item](#woocommerce_add_cart_item)
- [woocommerce_add_cart_item_data](#woocommerce_add_cart_item_data)
- [woocommerce_add_to_cart_form_action](#woocommerce_add_to_cart_form_action)
- [woocommerce_add_to_cart_quantity](#woocommerce_add_to_cart_quantity)
- [woocommerce_add_to_cart_sold_individually_quantity](#woocommerce_add_to_cart_sold_individually_quantity)
- [woocommerce_add_to_cart_validation](#woocommerce_add_to_cart_validation)
- [woocommerce_adjust_non_base_location_prices](#woocommerce_adjust_non_base_location_prices)
- [woocommerce_apply_individual_use_coupon](#woocommerce_apply_individual_use_coupon)
- [woocommerce_apply_with_individual_use_coupon](#woocommerce_apply_with_individual_use_coupon)
- [woocommerce_blocks_hook_compatibility_additional_data](#woocommerce_blocks_hook_compatibility_additional_data)
- [woocommerce_blocks_pre_get_routes_from_namespace](#woocommerce_blocks_pre_get_routes_from_namespace)
- [woocommerce_blocks_product_filters_selected_items](#woocommerce_blocks_product_filters_selected_items)
- [woocommerce_blocks_product_grid_add_to_cart_attributes](#woocommerce_blocks_product_grid_add_to_cart_attributes)
- [woocommerce_blocks_product_grid_is_cacheable](#woocommerce_blocks_product_grid_is_cacheable)
- [woocommerce_blocks_product_grid_item_html](#woocommerce_blocks_product_grid_item_html)
- [woocommerce_blocks_register_script_dependencies](#woocommerce_blocks_register_script_dependencies)
- [woocommerce_breadcrumb_home_url](#woocommerce_breadcrumb_home_url)
- [woocommerce_breadcrumb_main_term](#woocommerce_breadcrumb_main_term)
- [woocommerce_breadcrumb_product_terms_args](#woocommerce_breadcrumb_product_terms_args)
- [woocommerce_cart_contents_changed](#woocommerce_cart_contents_changed)
- [woocommerce_cart_item_permalink](#woocommerce_cart_item_permalink)
- [woocommerce_checkout_must_be_logged_in_message](#woocommerce_checkout_must_be_logged_in_message)
- [woocommerce_delete_expired_draft_orders_batch_size](#woocommerce_delete_expired_draft_orders_batch_size)
- [woocommerce_disable_compatibility_layer](#woocommerce_disable_compatibility_layer)
- [woocommerce_filter_fields_for_order_confirmation](#woocommerce_filter_fields_for_order_confirmation)
- [woocommerce_ga_disable_tracking](#woocommerce_ga_disable_tracking)
- [woocommerce_get_block_types](#woocommerce_get_block_types)
- [woocommerce_get_breadcrumb](#woocommerce_get_breadcrumb)
- [`woocommerce_get_default_value_for_{$key}`](#woocommerce_get_default_value_for_key)
- [`woocommerce_get_default_value_for_{$missing_field}`](#woocommerce_get_default_value_for_missing_field)
- [woocommerce_get_item_data](#woocommerce_get_item_data)
- [woocommerce_hooked_blocks_pattern_exclude_list](#woocommerce_hooked_blocks_pattern_exclude_list)
- [woocommerce_hydration_dispatch_request](#woocommerce_hydration_dispatch_request)
- [woocommerce_hydration_request_after_callbacks](#woocommerce_hydration_request_after_callbacks)
- [woocommerce_loop_add_to_cart_args](#woocommerce_loop_add_to_cart_args)
- [woocommerce_loop_add_to_cart_link](#woocommerce_loop_add_to_cart_link)
- [woocommerce_order_email_verification_grace_period](#woocommerce_order_email_verification_grace_period)
- [woocommerce_order_email_verification_required](#woocommerce_order_email_verification_required)
- [woocommerce_order_received_verify_known_shoppers](#woocommerce_order_received_verify_known_shoppers)
- [woocommerce_order_shipping_to_display_tax_label](#woocommerce_order_shipping_to_display_tax_label)
- [woocommerce_pay_order_product_has_enough_stock](#woocommerce_pay_order_product_has_enough_stock)
- [woocommerce_pay_order_product_in_stock](#woocommerce_pay_order_product_in_stock)
- [woocommerce_product_details_hooked_blocks](#woocommerce_product_details_hooked_blocks)
- [woocommerce_product_image_loading_attr](#woocommerce_product_image_loading_attr)
- [woocommerce_product_review_comment_form_args](#woocommerce_product_review_comment_form_args)
- [woocommerce_product_tabs](#woocommerce_product_tabs)
- [woocommerce_quantity_input_placeholder](#woocommerce_quantity_input_placeholder)
- [woocommerce_return_previous_exceptions](#woocommerce_return_previous_exceptions)
- [woocommerce_sale_badge_text](#woocommerce_sale_badge_text)
- [woocommerce_sanitize_additional_field](#woocommerce_sanitize_additional_field)
- [woocommerce_shared_settings](#woocommerce_shared_settings)
- [woocommerce_should_register_blocks](#woocommerce_should_register_blocks)
- [woocommerce_show_page_title](#woocommerce_show_page_title)
- [woocommerce_sortable_taxonomies](#woocommerce_sortable_taxonomies)
- [woocommerce_store_api_add_to_cart_data](#woocommerce_store_api_add_to_cart_data)
- [woocommerce_store_api_cart_item_images](#woocommerce_store_api_cart_item_images)
- [woocommerce_store_api_cart_item_quantity_validation](#woocommerce_store_api_cart_item_quantity_validation)
- [woocommerce_store_api_disable_nonce_check](#woocommerce_store_api_disable_nonce_check)
- [`woocommerce_store_api_product_quantity_{$value_type}`](#woocommerce_store_api_product_quantity_value_type)
- [woocommerce_store_api_rate_limit_id](#woocommerce_store_api_rate_limit_id)
- [woocommerce_store_api_rate_limit_options](#woocommerce_store_api_rate_limit_options)
- [woocommerce_thankyou_order_received_title](#woocommerce_thankyou_order_received_title)
- [woocommerce_use_block_notices_in_classic_theme](#woocommerce_use_block_notices_in_classic_theme)
- [woocommerce_variation_option_name](#woocommerce_variation_option_name)

---

## __experimental_woocommerce_blocks_add_data_attributes_to_block


Filters the list of allowed Block Names

```php
apply_filters( '__experimental_woocommerce_blocks_add_data_attributes_to_block', array $allowed_namespaces )
```

### Description

This hook defines which block names should have block name and attribute data- attributes appended on render.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $allowed_namespaces | array | List of namespaces. |

### Source

- [Blocks/BlockTypesController.php](../../../../../../src/Blocks/BlockTypesController.php)

---

## __experimental_woocommerce_blocks_add_data_attributes_to_namespace


Filters the list of allowed block namespaces.

```php
apply_filters( '__experimental_woocommerce_blocks_add_data_attributes_to_namespace', array $allowed_namespaces )
```

### Description

This hook defines which block namespaces should have block name and attribute `data-` attributes appended on render.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $allowed_namespaces | array | List of namespaces. |

### Source

- [Blocks/BlockTypesController.php](../../../../../../src/Blocks/BlockTypesController.php)

---

## __experimental_woocommerce_blocks_payment_gateway_features_list


Filter to control what features are available for each payment gateway.

```php
apply_filters( '__experimental_woocommerce_blocks_payment_gateway_features_list', array $features, string $name )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $features | array | List of supported features. |
| $name | string | Gateway name. |

### Returns


`array` Updated list of supported features.

### Example

#### Payment Gateway Featured List

```php
// The action callback function.
function my_function_callback( $features, $gateway ) {
    if ( 'my-gateway' !== $gateway->id ) {
			return $features;
		}
    $features[] = 'some-feature';
    return $features;
}

add_filter( '__experimental_woocommerce_blocks_payment_gateway_features_list', 'my_function_callback', 10, 2 );
```


### Source

- [Blocks/Payments/Integrations/PayPal.php](../../../../../../src/Blocks/Payments/Integrations/PayPal.php)

---

## __experimental_woocommerce_store_api_batch_request_methods


Filters the allowed methods for store API batch requests.

```php
apply_filters( '__experimental_woocommerce_store_api_batch_request_methods', string[] $methods )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $methods | string[] | Allowed methods. |

### Source

- [StoreApi/Routes/V1/Batch.php](../../../../../../src/StoreApi/Routes/V1/Batch.php)

---

## `__experimental_woocommerce_{$product_type}_add_to_cart_with_options_block_template_part`


Experimental filter for extensions to register a block template part for a product type.

```php
apply_filters( '__experimental_woocommerce_{$product_type}_add_to_cart_with_options_block_template_part', string|bool $template_part_path, string $product_type )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $template_part_path | string, bool | The template part path if it exists |
| $product_type | string | The product type |

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## comment_text


This filter is documented in wp-includes/comment-template.php

```php
apply_filters( 'comment_text' )
```

### Source

- [Blocks/BlockTypes/Reviews/ProductReviewContent.php](../../../../../../src/Blocks/BlockTypes/Reviews/ProductReviewContent.php)

---

## deprecated_function_trigger_error


Filters whether to trigger an error for deprecated functions. (Same as WP core)

```php
apply_filters( 'deprecated_function_trigger_error', bool $trigger )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $trigger | bool | Whether to trigger the error for deprecated functions. Default true. |

### Source

- [Blocks/Domain/Bootstrap.php](../../../../../../src/Blocks/Domain/Bootstrap.php)

---

## wc_session_expiration


Filters the session expiration.

```php
apply_filters( 'wc_session_expiration', int $expiration )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $expiration | int | Expiration in seconds. |

### Source

- [StoreApi/Utilities/CartTokenUtils.php](../../../../../../src/StoreApi/Utilities/CartTokenUtils.php)

---

## woocommerce_add_cart_item


Filters the item being added to the cart.

```php
apply_filters( 'woocommerce_add_cart_item', array $cart_item_data, string $cart_id )
```


**Note:** Matches filter name in WooCommerce core.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $cart_item_data | array | Array of cart item data being added to the cart. |
| $cart_id | string | Id of the item in the cart. |

### Returns


`array` Updated cart item data.

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_add_cart_item_data


Filter cart item data for add to cart requests.

```php
apply_filters( 'woocommerce_add_cart_item_data', array $cart_item_data, int $product_id, int $variation_id, int $quantity )
```


**Note:** Matches filter name in WooCommerce core.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $cart_item_data | array | Array of other cart item data. |
| $product_id | int | ID of the product added to the cart. |
| $variation_id | int | Variation ID of the product added to the cart. |
| $quantity | int | Quantity of the item added to the cart. |

### Returns


`array`

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_add_to_cart_form_action


Filter the add to cart form action.

```php
apply_filters( 'woocommerce_add_to_cart_form_action', string $action_url )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $action_url | string | The add to cart form action URL, defaulting to the current page. |

### Returns


`string` The add to cart form action URL.

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_add_to_cart_quantity


Filters the change the quantity to add to cart.

```php
apply_filters( 'woocommerce_add_to_cart_quantity', int|float $default_quantity, int $product_id, int $variation_id )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $default_quantity | int, float | The default quantity. |
| $product_id | int | The product id. |
| $variation_id | int | The variation ID. Always 0 in this context. |

### Source

- [Blocks/BlockTypes/ProductButton.php](../../../../../../src/Blocks/BlockTypes/ProductButton.php)

---

## woocommerce_add_to_cart_sold_individually_quantity


Filter sold individually quantity for add to cart requests.

```php
apply_filters( 'woocommerce_add_to_cart_sold_individually_quantity', int $sold_individually_quantity, int $quantity, int $product_id, int $variation_id, array $cart_item_data )
```


**Note:** Matches filter name in WooCommerce core.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $sold_individually_quantity | int | Defaults to 1. |
| $quantity | int | Quantity of the item added to the cart. |
| $product_id | int | ID of the product added to the cart. |
| $variation_id | int | Variation ID of the product added to the cart. |
| $cart_item_data | array | Array of other cart item data. |

### Returns


`int`

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## ~~woocommerce_add_to_cart_validation~~


Filters if an item being added to the cart passed validation checks.

```php
apply_filters( 'woocommerce_add_to_cart_validation', bool $passed_validation, int $product_id, int $quantity, int $variation_id, array $variation )
```


**Deprecated:** This hook is deprecated and will be removed

### Description

Allow 3rd parties to validate if an item can be added to the cart. This is a legacy hook from Woo core. This filter will be deprecated because it encourages usage of wc_add_notice. For the API we need to capture notices and convert to exceptions instead.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $passed_validation | bool | True if the item passed validation. |
| $product_id | int | Product ID being validated. |
| $quantity | int | Quantity added to the cart. |
| $variation_id | int | Variation ID being added to the cart. |
| $variation | array | Variation data. |

### Returns


`bool`

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_adjust_non_base_location_prices


Filters if taxes should be removed from locations outside the store base location.

```php
apply_filters( 'woocommerce_adjust_non_base_location_prices', bool $adjust_non_base_location_prices )
```


**Note:** Matches filter name in WooCommerce core.

### Description

The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations. e.g. If a product costs 10 including tax, all users will pay 10 regardless of location and taxes.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $adjust_non_base_location_prices | bool | True by default. |

### Returns


`bool`

### Source

- [Blocks/BlockTypes/ProductCollection/QueryBuilder.php](../../../../../../src/Blocks/BlockTypes/ProductCollection/QueryBuilder.php)
- [StoreApi/Utilities/ProductQuery.php](../../../../../../src/StoreApi/Utilities/ProductQuery.php)

---

## woocommerce_apply_individual_use_coupon


Filter coupons to remove when applying an individual use coupon.

```php
apply_filters( 'woocommerce_apply_individual_use_coupon', array $coupons, \WC_Coupon $coupon, array $applied_coupons )
```


**Note:** Matches filter name in WooCommerce core.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $coupons | array | Array of coupons to remove from the cart. |
| $coupon | \WC_Coupon | Coupon object applied to the cart. |
| $applied_coupons | array | Array of applied coupons already applied to the cart. |

### Returns


`array`

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_apply_with_individual_use_coupon


Filters if a coupon can be applied alongside other individual use coupons.

```php
apply_filters( 'woocommerce_apply_with_individual_use_coupon', bool $apply_with_individual_use_coupon, \WC_Coupon $coupon, \WC_Coupon $individual_use_coupon, array $applied_coupons )
```


**Note:** Matches filter name in WooCommerce core.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $apply_with_individual_use_coupon | bool | Defaults to false. |
| $coupon | \WC_Coupon | Coupon object applied to the cart. |
| $individual_use_coupon | \WC_Coupon | Individual use coupon already applied to the cart. |
| $applied_coupons | array | Array of applied coupons already applied to the cart. |

### Returns


`bool`

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_blocks_hook_compatibility_additional_data


When extensions implement their equivalent blocks of the template hook functions, they can use this filter to register their old hooked data here, so in the blockified template, the old hooked functions can be removed in favor of the new blocks while keeping the old hooked functions working in classic templates.

```php
apply_filters( 'woocommerce_blocks_hook_compatibility_additional_data', array $data, string $class_name )
```

### Description

Accepts an array of hooked data. The array should be in the following format: [ [ hook => `<hook-name>`, function => `<function-name>`, priority => `<priority>`, ], ... ] Where:

- hook-name is the name of the hook that have the functions hooked to.
- function-name is the hooked function name.
- priority is the priority of the hooked function.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $data | array | Additional hooked data. Default to empty |
| $class_name | string | Class name within which the hook is called. Either ArchiveProductTemplatesCompatibility or SingleProductTemplateCompatibility. |

### Source

- [Blocks/Templates/AbstractTemplateCompatibility.php](../../../../../../src/Blocks/Templates/AbstractTemplateCompatibility.php)

---

## woocommerce_blocks_pre_get_routes_from_namespace


Gives opportunity to return routes without invoking the compute intensive REST API.

```php
apply_filters( 'woocommerce_blocks_pre_get_routes_from_namespace', array $routes, string $namespace, string $context )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $routes | array | Array of routes. |
| $namespace | string | Namespace for routes. |
| $context | string | Context, can be edit or view. |

### Source

- [Blocks/BlockTypes/AbstractBlock.php](../../../../../../src/Blocks/BlockTypes/AbstractBlock.php)

---

## woocommerce_blocks_product_filters_selected_items


Filter hook to modify the selected filter items.

```php
apply_filters( 'woocommerce_blocks_product_filters_selected_items' )
```

### Source

- [Blocks/BlockTypes/ProductFilters.php](../../../../../../src/Blocks/BlockTypes/ProductFilters.php)

---

## woocommerce_blocks_product_grid_add_to_cart_attributes


Filter to manipulate (add/modify/remove) attributes in the HTML code of the generated add to cart button.

```php
apply_filters( 'woocommerce_blocks_product_grid_add_to_cart_attributes', array $attributes, \WC_Product $product )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $attributes | array | An associative array containing default HTML attributes of the add to cart button. |
| $product | \WC_Product | The WC_Product instance of the product that will be added to the cart once the button is pressed. |

### Returns


`array` Returns an associative array derived from the default array passed as an argument and added the extra HTML attributes.

### Source

- [Blocks/BlockTypes/AbstractProductGrid.php](../../../../../../src/Blocks/BlockTypes/AbstractProductGrid.php)

---

## woocommerce_blocks_product_grid_is_cacheable


Filters whether or not the product grid is cacheable.

```php
apply_filters( 'woocommerce_blocks_product_grid_is_cacheable', bool $is_cacheable, array $query_args )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $is_cacheable | bool | The list of script dependencies. |
| $query_args | array | Query args for the products query passed to BlocksWpQuery. |

### Returns


`array` True to enable cache, false to disable cache.

### Source

- [Blocks/BlockTypes/AbstractProductGrid.php](../../../../../../src/Blocks/BlockTypes/AbstractProductGrid.php)

---

## woocommerce_blocks_product_grid_item_html


Filters the HTML for products in the grid.

```php
apply_filters( 'woocommerce_blocks_product_grid_item_html', string $html, array $data, \WC_Product $product )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $html | string | Product grid item HTML. |
| $data | array | Product data passed to the template. |
| $product | \WC_Product | Product object. |

### Returns


`string` Updated product grid item HTML.

### Source

- [Blocks/BlockTypes/AbstractProductGrid.php](../../../../../../src/Blocks/BlockTypes/AbstractProductGrid.php)

---

## woocommerce_blocks_register_script_dependencies


Filters the list of script dependencies.

```php
apply_filters( 'woocommerce_blocks_register_script_dependencies', array $dependencies, string $handle )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $dependencies | array | The list of script dependencies. |
| $handle | string | The script's handle. |

### Returns


`array`

### Source

- [Blocks/Assets/Api.php](../../../../../../src/Blocks/Assets/Api.php)

---

## woocommerce_breadcrumb_home_url


Filters the Home breadcrumb URL.

```php
apply_filters( 'woocommerce_breadcrumb_home_url', string $url )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $url | string | The Home breadcrumb URL. |

### Source

- [Blocks/CoreBreadcrumbsCompatibility.php](../../../../../../src/Blocks/CoreBreadcrumbsCompatibility.php)

---

## woocommerce_breadcrumb_main_term


Filters the main term used in product breadcrumbs.

```php
apply_filters( 'woocommerce_breadcrumb_main_term', \WP_Term $main_term, \WP_Term[] $terms )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $main_term | \WP_Term | The main term to be used in breadcrumbs. |
| $terms | \WP_Term[] | Array of all product category terms. |

### Source

- [Blocks/CoreBreadcrumbsCompatibility.php](../../../../../../src/Blocks/CoreBreadcrumbsCompatibility.php)

---

## woocommerce_breadcrumb_product_terms_args


Filters the arguments used to fetch product terms for breadcrumbs.

```php
apply_filters( 'woocommerce_breadcrumb_product_terms_args', array $args )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $args | array | Array of arguments for `wc_get_product_terms()`. |

### Source

- [Blocks/CoreBreadcrumbsCompatibility.php](../../../../../../src/Blocks/CoreBreadcrumbsCompatibility.php)

---

## woocommerce_cart_contents_changed


Filters the entire cart contents when the cart changes.

```php
apply_filters( 'woocommerce_cart_contents_changed', array $cart_contents )
```


**Note:** Matches filter name in WooCommerce core.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $cart_contents | array | Array of all cart items. |

### Returns


`array` Updated array of all cart items.

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_cart_item_permalink


Filter the product permalink.

```php
apply_filters( 'woocommerce_cart_item_permalink', string $product_permalink, array $cart_item, string $cart_item_key )
```

### Description

This is a hook taken from the legacy cart/mini-cart templates that allows the permalink to be changed for a product. This is specific to the cart endpoint.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $product_permalink | string | Product permalink. |
| $cart_item | array | Cart item array. |
| $cart_item_key | string | Cart item key. |

### Source

- [StoreApi/Schemas/V1/CartItemSchema.php](../../../../../../src/StoreApi/Schemas/V1/CartItemSchema.php)

---

## woocommerce_checkout_must_be_logged_in_message


Filter to customize the checkout message when a user must be logged in.

```php
apply_filters( 'woocommerce_checkout_must_be_logged_in_message', string $message )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $message | string | Message to display when a user must be logged in to check out. |

### Source

- [StoreApi/Routes/V1/Checkout.php](../../../../../../src/StoreApi/Routes/V1/Checkout.php)

---

## woocommerce_delete_expired_draft_orders_batch_size


Filters the number of draft orders deleted per batch during cleanup.

```php
apply_filters( 'woocommerce_delete_expired_draft_orders_batch_size', int $batch_size )
```

### Description

Increasing this value can help improve deletion throughput for high-volume or busy stores when the cleanup task cannot keep up with the draft orders backlog.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $batch_size | int | Number of draft orders to delete per batch. Default 20. |

### Source

- [Blocks/Domain/Services/DraftOrders.php](../../../../../../src/Blocks/Domain/Services/DraftOrders.php)

---

## woocommerce_disable_compatibility_layer


Filter to disable the compatibility layer for the blockified templates.

```php
apply_filters( 'woocommerce_disable_compatibility_layer', bool $is_disabled_compatibility_layer )
```

### Description

This hook allows to disable the compatibility layer for the blockified templates.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $is_disabled_compatibility_layer | bool | Whether the compatibility layer should be disabled. |

### Source

- [Blocks/Templates/AbstractTemplateCompatibility.php](../../../../../../src/Blocks/Templates/AbstractTemplateCompatibility.php)
- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)
- [Blocks/BlockTypes/ProductDetails.php](../../../../../../src/Blocks/BlockTypes/ProductDetails.php)

---

## woocommerce_filter_fields_for_order_confirmation


Filter fields for order confirmation (thank you page, email).

```php
apply_filters( 'woocommerce_filter_fields_for_order_confirmation', bool $show_field, array $field, array $fields, array $context, \CheckoutFields $instance )
```

### Description

Used in methods: WC_Email::additional_checkout_fields WC_Email::additional_address_fields CheckoutFieldsFrontend::render_order_other_fields CheckoutFieldsFrontend::render_order_address_fields AdditionalFields::render_content BillingAddress::render_content ShippingAddress::render_content

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $show_field | bool | Whether the field should be shown. |
| $field | array | Field data. |
| $fields | array | All fields for better context when field should be shown or hidden based on other fields values. |
| $context | array | Additional context for the filter. Data depends in which method filter_fields_for_order_confirmation is called. |
| $instance | \CheckoutFields | The CheckoutFields instance. |

### Source

- [Blocks/Domain/Services/CheckoutFields.php](../../../../../../src/Blocks/Domain/Services/CheckoutFields.php)

---

## woocommerce_ga_disable_tracking


Filter to disable Google Analytics tracking.

```php
apply_filters( 'woocommerce_ga_disable_tracking', bool $disable_tracking )
```


**Note:** Matches filter name in GA extension.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $disable_tracking | bool | If true, tracking will be disabled. |

### Source

- [Blocks/Domain/Services/GoogleAnalytics.php](../../../../../../src/Blocks/Domain/Services/GoogleAnalytics.php)

---

## woocommerce_get_block_types


Filters the list of allowed block types.

```php
apply_filters( 'woocommerce_get_block_types', array $block_types )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $block_types | array | List of block types. |

### Source

- [Blocks/BlockTypesController.php](../../../../../../src/Blocks/BlockTypesController.php)

---

## woocommerce_get_breadcrumb


Filters the breadcrumb trail array.

```php
apply_filters( 'woocommerce_get_breadcrumb', array $crumbs, \WC_Breadcrumb|null $breadcrumb )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $crumbs | array | The breadcrumb trail. |
| $breadcrumb | \WC_Breadcrumb, null | The breadcrumb object (null when called from Core block). |

### Source

- [Blocks/CoreBreadcrumbsCompatibility.php](../../../../../../src/Blocks/CoreBreadcrumbsCompatibility.php)

---

## `woocommerce_get_default_value_for_{$key}`


Allow providing a default value for additional fields if no value is already set.

```php
apply_filters( 'woocommerce_get_default_value_for_{$key}', null $value, string $group, \WC_Data $wc_object )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $value | null | The default value for the filter, always null. |
| $group | string | The group of this key (shipping\|billing\|other). |
| $wc_object | \WC_Data | The object to get the field value for. |

### Source

- [Blocks/Domain/Services/CheckoutFields.php](../../../../../../src/Blocks/Domain/Services/CheckoutFields.php)

---

## `woocommerce_get_default_value_for_{$missing_field}`


Allow providing a default value for additional fields if no value is already set.

```php
apply_filters( 'woocommerce_get_default_value_for_{$missing_field}', null $value, string $group, \WC_Data $wc_object )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $value | null | The default value for the filter, always null. |
| $group | string | The group of this key (shipping\|billing\|other). |
| $wc_object | \WC_Data | The object to get the field value for. |

### Source

- [Blocks/Domain/Services/CheckoutFields.php](../../../../../../src/Blocks/Domain/Services/CheckoutFields.php)

---

## woocommerce_get_item_data


Filters cart item data.

```php
apply_filters( 'woocommerce_get_item_data', array $item_data, array $cart_item )
```


**Note:** Matches filter name in WooCommerce core.

### Description

Filters the variation option name for custom option slugs.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $item_data | array | Cart item data. Empty by default. |
| $cart_item | array | Cart item array. |

### Returns


`array`

### Source

- [StoreApi/Schemas/V1/CartItemSchema.php](../../../../../../src/StoreApi/Schemas/V1/CartItemSchema.php)

---

## woocommerce_hooked_blocks_pattern_exclude_list


A list of pattern slugs to exclude from auto-insert (useful when there are patterns that have a very specific location for the block) Note: The patterns that are currently excluded are the ones that don't work well with the mini-cart block or customer-account block.

```php
apply_filters( 'woocommerce_hooked_blocks_pattern_exclude_list' )
```

### Source

- [Blocks/Utils/BlockHooksTrait.php](../../../../../../src/Blocks/Utils/BlockHooksTrait.php)

---

## woocommerce_hydration_dispatch_request


Similar to WP core's `rest_dispatch_request` filter, this allows plugin to override hydrating the request.

```php
apply_filters( 'woocommerce_hydration_dispatch_request', mixed $hydration_result, \WP_REST_Request $request, string $path, array $handler )
```

### Description

Allows backward compatibility with the `rest_dispatch_request` filter by providing the same arguments.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $hydration_result | mixed | Result of the hydration. If not null, this will be used as the response. |
| $request | \WP_REST_Request | Request used to generate the response. |
| $path | string | Request path matched for the request.. |
| $handler | array | Route handler used for the request. |

### Source

- [Blocks/Domain/Services/Hydration.php](../../../../../../src/Blocks/Domain/Services/Hydration.php)

---

## woocommerce_hydration_request_after_callbacks


Similar to WP core's `rest_request_after_callbacks` filter, this allows to modify the response after it has been generated.

```php
apply_filters( 'woocommerce_hydration_request_after_callbacks', \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed $response, array $handler, \WP_REST_Request $request )
```

### Description

Allows backward compatibility with the `rest_request_after_callbacks` filter by providing the same arguments.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $response | \WP_REST_Response, \WP_HTTP_Response, \WP_Error, mixed | Result to send to the client. Usually a WP_REST_Response or WP_Error. |
| $handler | array | Route handler used for the request. |
| $request | \WP_REST_Request | Request used to generate the response. |

### Source

- [Blocks/Domain/Services/Hydration.php](../../../../../../src/Blocks/Domain/Services/Hydration.php)

---

## woocommerce_loop_add_to_cart_args


Allow filtering of the add to cart button arguments.

```php
apply_filters( 'woocommerce_loop_add_to_cart_args' )
```

### Source

- [Blocks/BlockTypes/ProductButton.php](../../../../../../src/Blocks/BlockTypes/ProductButton.php)

---

## woocommerce_loop_add_to_cart_link


Filters the add to cart button class.

```php
apply_filters( 'woocommerce_loop_add_to_cart_link', string $class )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $class | string | The class. |

### Source

- [Blocks/BlockTypes/ProductButton.php](../../../../../../src/Blocks/BlockTypes/ProductButton.php)

---

## woocommerce_order_email_verification_grace_period


Controls the grace period within which we do not require any sort of email verification step before rendering the 'order received' or 'order pay' pages.

```php
apply_filters( 'woocommerce_order_email_verification_grace_period', int $grace_period, \WC_Order $order, string $context )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $grace_period | int | Time in seconds after an order is placed before email verification may be required. |
| $order | \WC_Order | The order for which this grace period is being assessed. |
| $context | string | Indicates the context in which we might verify the email address. Typically 'order-pay' or 'order-received'. |

### See

- WC_Shortcode_Checkout::order_received()

### Source

- [Blocks/BlockTypes/OrderConfirmation/AbstractOrderConfirmationBlock.php](../../../../../../src/Blocks/BlockTypes/OrderConfirmation/AbstractOrderConfirmationBlock.php)

---

## woocommerce_order_email_verification_required


Provides an opportunity to override the (potential) requirement for shoppers to verify their email address before we show information such as the order summary, or order payment page.

```php
apply_filters( 'woocommerce_order_email_verification_required', bool $email_verification_required, \WC_Order $order, string $context )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $email_verification_required | bool | If email verification is required. |
| $order | \WC_Order | The relevant order. |
| $context | string | The context under which we are performing this check. |

### See

- WC_Shortcode_Checkout::order_received()

### Source

- [Blocks/BlockTypes/OrderConfirmation/AbstractOrderConfirmationBlock.php](../../../../../../src/Blocks/BlockTypes/OrderConfirmation/AbstractOrderConfirmationBlock.php)

---

## woocommerce_order_received_verify_known_shoppers


Indicates if known (non-guest) shoppers need to be logged in before we let them access the order received page.

```php
apply_filters( 'woocommerce_order_received_verify_known_shoppers', bool $verify_known_shoppers )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $verify_known_shoppers | bool | If verification is required. |

### Source

- [Blocks/BlockTypes/OrderConfirmation/AbstractOrderConfirmationBlock.php](../../../../../../src/Blocks/BlockTypes/OrderConfirmation/AbstractOrderConfirmationBlock.php)

---

## woocommerce_order_shipping_to_display_tax_label


Hook to add tax label to pickup cost.

```php
apply_filters( 'woocommerce_order_shipping_to_display_tax_label', string $tax_label, \WC_Order $order, string $tax_display )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $tax_label | string | Tax label. |
| $order | \WC_Order | Order object. |
| $tax_display | string | Tax display. |

### Returns


`string`

### Source

- [Blocks/Shipping/ShippingController.php](../../../../../../src/Blocks/Shipping/ShippingController.php)

---

## woocommerce_pay_order_product_has_enough_stock


Filters whether or not the product has enough stock.

```php
apply_filters( 'woocommerce_pay_order_product_has_enough_stock', bool $has_enough_stock, \WC_Product $product, \WC_Order|\WC_Order_Refund|false $order )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $has_enough_stock | bool | True if has enough stock. |
| $product | \WC_Product | Product. |
| $order | \WC_Order, \WC_Order_Refund, false | Order. |

### Source

- [StoreApi/Utilities/OrderController.php](../../../../../../src/StoreApi/Utilities/OrderController.php)

---

## woocommerce_pay_order_product_in_stock


Filters whether or not the product is in stock for this pay for order.

```php
apply_filters( 'woocommerce_pay_order_product_in_stock', bool $is_in_stock, \WC_Product $product, \WC_Order|\WC_Order_Refund|false $order )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $is_in_stock | bool | True if in stock. |
| $product | \WC_Product | Product. |
| $order | \WC_Order, \WC_Order_Refund, false | Order. |

### Source

- [StoreApi/Utilities/OrderController.php](../../../../../../src/StoreApi/Utilities/OrderController.php)

---

## woocommerce_product_details_hooked_blocks


Filter the blocks that are hooked into the Product Details block.

```php
apply_filters( 'woocommerce_product_details_hooked_blocks', array $hooked_blocks )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $hooked_blocks | array | The blocks that are hooked into the Product Details block. |

### Returns


`array` The blocks that are hooked into the Product Details block.

### Source

- [Blocks/BlockTypes/ProductDetails.php](../../../../../../src/Blocks/BlockTypes/ProductDetails.php)

---

## woocommerce_product_image_loading_attr


Filters the loading attribute for product images.

```php
apply_filters( 'woocommerce_product_image_loading_attr', string $loading_attr, int $image_id )
```

### Description

Allowed values are 'lazy', 'eager', and 'auto'. Any other value will result in default browser behavior.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $loading_attr | string | The loading attribute. Default 'lazy'. |
| $image_id | int | Target image ID. |

### Source

- [Blocks/BlockTypes/ProductImage.php](../../../../../../src/Blocks/BlockTypes/ProductImage.php)

---

## woocommerce_product_review_comment_form_args


Filters the comment form arguments.

```php
apply_filters( 'woocommerce_product_review_comment_form_args', array $comment_form, int $post_id )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $comment_form | array | The comment form arguments. |
| $post_id | int | The post ID. |

### Source

- [Blocks/BlockTypes/Reviews/ProductReviewForm.php](../../../../../../src/Blocks/BlockTypes/Reviews/ProductReviewForm.php)

---

## woocommerce_product_tabs


Filter the product tabs in the product details block.

```php
apply_filters( 'woocommerce_product_tabs', array $tabs )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $tabs | array | Array of product tabs. |

### Source

- [Blocks/BlockTypes/ProductDetails.php](../../../../../../src/Blocks/BlockTypes/ProductDetails.php)

---

## woocommerce_quantity_input_placeholder


Filter the placeholder value allowed for the product.

```php
apply_filters( 'woocommerce_quantity_input_placeholder', int $max_value, \WC_Product $product )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $max_value | int | Maximum quantity value. |
| $product | \WC_Product | Product object. |

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/GroupedProductItemSelector.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/GroupedProductItemSelector.php)

---

## woocommerce_return_previous_exceptions


Allows to check if WP_DEBUG mode is enabled before returning previous Exception.

```php
apply_filters( 'woocommerce_return_previous_exceptions', bool $ )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $ | bool | The WP_DEBUG mode. |

### Source

- [StoreApi/Utilities/CheckoutTrait.php](../../../../../../src/StoreApi/Utilities/CheckoutTrait.php)

---

## woocommerce_sale_badge_text


Filters the product sale badge text.

```php
apply_filters( 'woocommerce_sale_badge_text', string $sale_text, \WC_Product $product )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $sale_text | string | The sale badge text. |
| $product | \WC_Product | The product object. |

### Returns


`string` The filtered sale badge text.

### Source

- [Blocks/BlockTypes/ProductSaleBadge.php](../../../../../../src/Blocks/BlockTypes/ProductSaleBadge.php)

---

## woocommerce_sanitize_additional_field


Allow custom sanitization of an additional field.

```php
apply_filters( 'woocommerce_sanitize_additional_field', mixed $field_value, string $field_key )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $field_value | mixed | The value of the field being sanitized. |
| $field_key | string | Key of the field being sanitized. |

### Source

- [Blocks/Domain/Services/CheckoutFields.php](../../../../../../src/Blocks/Domain/Services/CheckoutFields.php)

---

## ~~woocommerce_shared_settings~~


Filters the array of shared settings.

```php
apply_filters( 'woocommerce_shared_settings', array $data )
```


**Deprecated:** This hook is deprecated and will be removed

### Description

Low level hook for registration of new data late in the cycle. This is deprecated. Instead, use the data api:

```php
Automattic\WooCommerce\Blocks\Package::container()->get( Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class )->add( $key, $value )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $data | array | Settings data. |

### Returns


`array`

### Source

- [Blocks/Assets/AssetDataRegistry.php](../../../../../../src/Blocks/Assets/AssetDataRegistry.php)

---

## woocommerce_should_register_blocks


Filters whether WooCommerce should register its block types and patterns for the current request.

```php
apply_filters( 'woocommerce_should_register_blocks', bool $should_register )
```

### Description

Registration is skipped on known non-rendering contexts (the Store API and other WooCommerce REST namespaces, cron, AJAX, XML-RPC, favicon, robots.txt and XML sitemaps) as a performance optimisation. Product and variation descriptions rendered through do_blocks are already handled on demand (see the woocommerce_short_description hook in Bootstrap), so this filter is only needed to opt back in when an extension renders WooCommerce blocks some other way in one of those contexts.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $should_register | bool | Whether block types and patterns should be registered for this request. |

### Source

- [Blocks/Domain/BlockRegistrationContext.php](../../../../../../src/Blocks/Domain/BlockRegistrationContext.php)

---

## woocommerce_show_page_title


Hook: woocommerce_show_page_title

```php
apply_filters( 'woocommerce_show_page_title' )
```

### Description

Allows controlling the display of the page title.

### Source

- [Blocks/BlockTypes/ClassicTemplate.php](../../../../../../src/Blocks/BlockTypes/ClassicTemplate.php)

---

## woocommerce_sortable_taxonomies


Filters the list of taxonomies that support custom ordering. Filter was introduced long ago is only documented in 10.6.0.

```php
apply_filters( 'woocommerce_sortable_taxonomies', array $sortable_taxonomies )
```

### Description

First instance in plugins/woocommerce/includes/admin/class-wc-admin-assets.php.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $sortable_taxonomies | array | List of taxonomy slugs that support custom ordering. |

### Returns


`array` List of taxonomy slugs that support custom ordering.

### Source

- [Blocks/BlockTypes/ProductFilterTaxonomy.php](../../../../../../src/Blocks/BlockTypes/ProductFilterTaxonomy.php)

---

## woocommerce_store_api_add_to_cart_data


Filters cart item data sent via the API before it is passed to the cart controller.

```php
apply_filters( 'woocommerce_store_api_add_to_cart_data', array $add_to_cart_data )
```

### Description

This hook filters cart items. It allows the request data to be changed, for example, quantity, or supplemental cart item data, before it is passed into CartController::add_to_cart and stored to session.

CartController::add_to_cart only expects the keys id, quantity, variation, and cart_item_data, so other values may be ignored. CartController::add_to_cart (and core) do already have a filter hook called woocommerce_add_cart_item, but this does not have access to the original Store API request like this hook does.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $add_to_cart_data | array | An array of cart item data. |

### Returns


`array`

### Source

- [StoreApi/Routes/V1/CartAddItem.php](../../../../../../src/StoreApi/Routes/V1/CartAddItem.php)

---

## woocommerce_store_api_cart_item_images


Filter the cart product images.

```php
apply_filters( 'woocommerce_store_api_cart_item_images', array $product_images, array $cart_item, string $cart_item_key )
```

### Description

This hook allows the cart item images to be changed. This is specific to the cart endpoint.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $product_images | array | Array of image objects, as defined in ImageAttachmentSchema. |
| $cart_item | array | Cart item array. |
| $cart_item_key | string | Cart item key. |

### Source

- [StoreApi/Schemas/V1/CartItemSchema.php](../../../../../../src/StoreApi/Schemas/V1/CartItemSchema.php)

---

## woocommerce_store_api_cart_item_quantity_validation


Filters the validation result for a cart item quantity being updated via the Store API.

```php
apply_filters( 'woocommerce_store_api_cart_item_quantity_validation', true $valid, int|float $quantity, \WC_Product $product, array $cart_item )
```

### Description

Return a \WP_Error to reject the new quantity; the Store API sends its code and message in a 400 response. Throwing a RouteException works too. Any other return value, including false, is ignored and the quantity is accepted. Notices added with wc_add_notice() are not read here. Core validation failures (min, max, multiple_of, read-only), and cart items whose data key is not a WC_Product, return early and never reach this filter.

This does not run when a product is first added to the cart; use the woocommerce_store_api_validate_add_to_cart action for that. When an already-in-cart item is topped up, $quantity is the new total while $cart_item['quantity'] is still the pre-existing quantity.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $valid | true | Always true; core validation failures bypass this filter. |
| $quantity | int, float | The new quantity, already normalized through wc_stock_amount(). |
| $product | \WC_Product | The product object. |
| $cart_item | array | Cart item. |

### Returns


`\WP_Error, true`

### Source

- [StoreApi/Utilities/QuantityLimits.php](../../../../../../src/StoreApi/Utilities/QuantityLimits.php)

---

## woocommerce_store_api_disable_nonce_check


Filters the Store API nonce check.

```php
apply_filters( 'woocommerce_store_api_disable_nonce_check', bool $disable_nonce_check )
```

### Description

This can be used to disable the nonce check when testing API endpoints via a REST API client.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $disable_nonce_check | bool | If true, nonce checks will be disabled. |

### Returns


`bool`

### Source

- [StoreApi/Routes/V1/AbstractCartRoute.php](../../../../../../src/StoreApi/Routes/V1/AbstractCartRoute.php)
- [StoreApi/Routes/V1/ShopperListsNonceCheck.php](../../../../../../src/StoreApi/Routes/V1/ShopperListsNonceCheck.php)

---

## `woocommerce_store_api_product_quantity_{$value_type}`


Filters a quantity for a cart item in Store API. This allows extensions to control the qty of items.

```php
apply_filters( 'woocommerce_store_api_product_quantity_{$value_type}', mixed $value, \WC_Product $product, array|null $cart_item )
```

### Description

The suffix of the hook will vary depending on the value being filtered. For example, minimum, maximum, multiple_of, editable.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $value | mixed | The value being filtered. |
| $product | \WC_Product | The product object. |
| $cart_item | array, null | The cart item if the product exists in the cart, or null. |

### Returns


`mixed`

### Source

- [StoreApi/Utilities/QuantityLimits.php](../../../../../../src/StoreApi/Utilities/QuantityLimits.php)

---

## woocommerce_store_api_rate_limit_id


Filters the rate limiting identifier.

```php
apply_filters( 'woocommerce_store_api_rate_limit_id', string $id )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $id | string | The rate limiting identifier. |

### Source

- [StoreApi/Authentication.php](../../../../../../src/StoreApi/Authentication.php)

---

## woocommerce_store_api_rate_limit_options


Filters options for Rate Limits.

```php
apply_filters( 'woocommerce_store_api_rate_limit_options', array $rate_limit_options )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $rate_limit_options | array | Array of option values. |

### Returns


`array`

### Source

- [StoreApi/Utilities/RateLimits.php](../../../../../../src/StoreApi/Utilities/RateLimits.php)

---

## woocommerce_thankyou_order_received_title


Filter the title shown after a checkout is complete.

```php
apply_filters( 'woocommerce_thankyou_order_received_title', string $title, \WC_Order|false $order )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $title | string | The title. |
| $order | \WC_Order, false | The order created during checkout, or false if order data is not available. |

### Source

- [Blocks/BlockTypes/OrderConfirmation/Status.php](../../../../../../src/Blocks/BlockTypes/OrderConfirmation/Status.php)

---

## woocommerce_use_block_notices_in_classic_theme


Allow classic theme developers to opt-in to using block notices.

```php
apply_filters( 'woocommerce_use_block_notices_in_classic_theme', bool $use_block_notices_in_classic_theme )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $use_block_notices_in_classic_theme | bool | Whether to use block notices in classic theme. |

### Returns


`bool`

### Source

- [Blocks/Domain/Services/Notices.php](../../../../../../src/Blocks/Domain/Services/Notices.php)

---

## woocommerce_variation_option_name


Filter the variation option name.

```php
apply_filters( 'woocommerce_variation_option_name', string $option_label, \WP_Term|string|null $item, string $attribute_name, \WC_Product $product )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $option_label | string | The option label. |
| $item | \WP_Term, string, null | Term object for taxonomies, option string for custom attributes. |
| $attribute_name | string | Name of the attribute. |
| $product | \WC_Product | Product object. |

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/VariationSelectorAttribute.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/VariationSelectorAttribute.php)
- [StoreApi/Utilities/ProductItemTrait.php](../../../../../../src/StoreApi/Utilities/ProductItemTrait.php)

---
<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/hooks/filters.md)

<!-- /FEEDBACK -->
