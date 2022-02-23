<!-- DO NOT UPDATE THIS DOC DIRECTLY -->

<!-- Use `npm run build:docs` to automatically build hook documentation -->

# Filters

## Table of Contents


 - [__experimental_woocommerce_blocks_add_data_attributes_to_block](#__experimental_woocommerce_blocks_add_data_attributes_to_block)
 - [__experimental_woocommerce_blocks_add_data_attributes_to_namespace](#__experimental_woocommerce_blocks_add_data_attributes_to_namespace)
 - [__experimental_woocommerce_blocks_payment_gateway_features_list](#__experimental_woocommerce_blocks_payment_gateway_features_list)
 - [woocommerce_add_cart_item](#woocommerce_add_cart_item)
 - [woocommerce_add_cart_item_data](#woocommerce_add_cart_item_data)
 - [woocommerce_add_to_cart_sold_individually_quantity](#woocommerce_add_to_cart_sold_individually_quantity)
 - [woocommerce_add_to_cart_validation](#-woocommerce_add_to_cart_validation)
 - [woocommerce_adjust_non_base_location_prices](#woocommerce_adjust_non_base_location_prices)
 - [woocommerce_apply_individual_use_coupon](#woocommerce_apply_individual_use_coupon)
 - [woocommerce_apply_with_individual_use_coupon](#woocommerce_apply_with_individual_use_coupon)
 - [woocommerce_blocks_product_grid_is_cacheable](#woocommerce_blocks_product_grid_is_cacheable)
 - [woocommerce_blocks_product_grid_item_html](#woocommerce_blocks_product_grid_item_html)
 - [woocommerce_blocks_register_script_dependencies](#woocommerce_blocks_register_script_dependencies)
 - [woocommerce_cart_contents_changed](#woocommerce_cart_contents_changed)
 - [woocommerce_ga_disable_tracking](#woocommerce_ga_disable_tracking)
 - [woocommerce_get_item_data](#woocommerce_get_item_data)
 - [woocommerce_new_customer_data](#woocommerce_new_customer_data)
 - [woocommerce_registration_errors](#woocommerce_registration_errors)
 - [woocommerce_shared_settings](#-woocommerce_shared_settings)
 - [woocommerce_shipping_package_name](#woocommerce_shipping_package_name)
 - [woocommerce_show_page_title](#woocommerce_show_page_title)
 - [woocommerce_store_api_disable_nonce_check](#woocommerce_store_api_disable_nonce_check)
 - [woocommerce_store_api_product_quantity_limit](#woocommerce_store_api_product_quantity_limit)
 - [woocommerce_store_api_product_quantity_{$value_type}](#woocommerce_store_api_product_quantity_-value_type)
 - [woocommerce_variation_option_name](#woocommerce_variation_option_name)

---

## __experimental_woocommerce_blocks_add_data_attributes_to_block


Filters the list of allowed Block Names

```php
apply_filters( '__experimental_woocommerce_blocks_add_data_attributes_to_block', array $allowed_namespaces )
```

### Description

<p>This hook defines which block names should have block name and attribute data- attributes appended on render.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $allowed_namespaces | array | List of namespaces. |

### Source


 - [BlockTypesController.php](../src/BlockTypesController.php)

---

## __experimental_woocommerce_blocks_add_data_attributes_to_namespace


Filters the list of allowed block namespaces.

```php
apply_filters( '__experimental_woocommerce_blocks_add_data_attributes_to_namespace', array $allowed_namespaces )
```

### Description

<p>This hook defines which block namespaces should have block name and attribute <code>data-</code> attributes appended on render.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $allowed_namespaces | array | List of namespaces. |

### Source


 - [BlockTypesController.php](../src/BlockTypesController.php)

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
<!-- FEEDBACK -->
---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/extensibility/filters.md)
<!-- /FEEDBACK -->

