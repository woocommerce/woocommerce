<!-- DO NOT UPDATE THIS DOC DIRECTLY -->

<!-- Use `npm run build:docs` to automatically build hook documentation -->

# Filters

## Table of Contents


 - [__experimental_woocommerce_blocks_add_data_attributes_to_block](#__experimental_woocommerce_blocks_add_data_attributes_to_block)
 - [__experimental_woocommerce_blocks_add_data_attributes_to_namespace](#__experimental_woocommerce_blocks_add_data_attributes_to_namespace)
 - [__experimental_woocommerce_blocks_payment_gateway_features_list](#__experimental_woocommerce_blocks_payment_gateway_features_list)
 - [wc_stripe_allow_prepaid_card](#wc_stripe_allow_prepaid_card)
 - [wc_stripe_display_save_payment_method_checkbox](#wc_stripe_display_save_payment_method_checkbox)
 - [wc_stripe_payment_request_button_locale](#wc_stripe_payment_request_button_locale)
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


File: [BlockTypesController.php](../src/BlockTypesController.php)

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


File: [BlockTypesController.php](../src/BlockTypesController.php)

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


### Source


File: [Payments/Integrations/PayPal.php](../src/Payments/Integrations/PayPal.php)

---

## wc_stripe_allow_prepaid_card


Filters if prepaid cards are supported by Stripe.

```php
apply_filters( 'wc_stripe_allow_prepaid_card', boolean $allow_prepaid_card )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $allow_prepaid_card | boolean | True if prepaid cards are allowed. |

### Returns


`boolean` 

### Source


File: [Payments/Integrations/Stripe.php](../src/Payments/Integrations/Stripe.php)

---

## wc_stripe_display_save_payment_method_checkbox


Filters if the save payment method checkbox is shown for Stripe.

```php
apply_filters( 'wc_stripe_display_save_payment_method_checkbox', boolean $saved_cards )
```

### Description

<p>This assumes that Stripe supports <code>tokenization</code> - currently this is true, based on <a href="https://github.com/woocommerce/woocommerce-gateway-stripe/blob/master/includes/class-wc-gateway-stripe.php#L95">https://github.com/woocommerce/woocommerce-gateway-stripe/blob/master/includes/class-wc-gateway-stripe.php#L95</a></p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $saved_cards | boolean | True if saved cards functionality is enabled. |

### Returns


`boolean` 

### Related


 - https://github.com/woocommerce/woocommerce-gateway-stripe/blob/ad19168b63df86176cbe35c3e95203a245687640/includes/class-wc-gateway-stripe.php#L271
 - https://github.com/woocommerce/woocommerce/wiki/Payment-Token-API

### Source


File: [Payments/Integrations/Stripe.php](../src/Payments/Integrations/Stripe.php)

---

## wc_stripe_payment_request_button_locale


Filters the payment request button locale.

```php
apply_filters( 'wc_stripe_payment_request_button_locale', string $locale )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $locale | string | Current locale. Defaults to en_US. |

### Returns


`string` 

### Source


File: [Payments/Integrations/Stripe.php](../src/Payments/Integrations/Stripe.php)

---

## woocommerce_add_cart_item


Filters the item being added to the cart.

```php
apply_filters( 'woocommerce_add_cart_item', array $cart_item_data, string $cart_id )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $cart_item_data | array | Array of cart item data being added to the cart. |
| $cart_id | string | Id of the item in the cart. |

### Returns


`array` Updated cart item data.

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_add_cart_item_data


Filter cart item data for add to cart requests.

```php
apply_filters( 'woocommerce_add_cart_item_data', array $cart_item_data, integer $product_id, integer $variation_id, integer $quantity )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $cart_item_data | array | Array of other cart item data. |
| $product_id | integer | ID of the product added to the cart. |
| $variation_id | integer | Variation ID of the product added to the cart. |
| $quantity | integer | Quantity of the item added to the cart. |

### Returns


`array` 

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_add_to_cart_sold_individually_quantity


Filter sold individually quantity for add to cart requests.

```php
apply_filters( 'woocommerce_add_to_cart_sold_individually_quantity', integer $sold_individually_quantity, integer $quantity, integer $product_id, integer $variation_id, array $cart_item_data )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $sold_individually_quantity | integer | Defaults to 1. |
| $quantity | integer | Quantity of the item added to the cart. |
| $product_id | integer | ID of the product added to the cart. |
| $variation_id | integer | Variation ID of the product added to the cart. |
| $cart_item_data | array | Array of other cart item data. |

### Returns


`integer` 

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## ~~woocommerce_add_to_cart_validation~~


Filters if an item being added to the cart passed validation checks.

```php
apply_filters( 'woocommerce_add_to_cart_validation', boolean $passed_validation, integer $product_id, integer $quantity, integer $variation_id, array $variation )
```


**Deprecated: This hook is deprecated and will be removed**

### Description

<p>Allow 3rd parties to validate if an item can be added to the cart. This is a legacy hook from Woo core. This filter will be deprecated because it encourages usage of wc_add_notice. For the API we need to capture notices and convert to exceptions instead.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $passed_validation | boolean | True if the item passed validation. |
| $product_id | integer | Product ID being validated. |
| $quantity | integer | Quantity added to the cart. |
| $variation_id | integer | Variation ID being added to the cart. |
| $variation | array | Variation data. |

### Returns


`boolean` 

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_adjust_non_base_location_prices


Filters if taxes should be removed from locations outside the store base location.

```php
apply_filters( 'woocommerce_adjust_non_base_location_prices', boolean $adjust_non_base_location_prices )
```

### Description

<p>The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations. e.g. If a product costs 10 including tax, all users will pay 10 regardless of location and taxes.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $adjust_non_base_location_prices | boolean | True by default. |

### Returns


`boolean` 

### Source


File: [StoreApi/Utilities/ProductQuery.php](../src/StoreApi/Utilities/ProductQuery.php)

---

## woocommerce_apply_individual_use_coupon


Filter coupons to remove when applying an individual use coupon.

```php
apply_filters( 'woocommerce_apply_individual_use_coupon', array $coupons, \WC_Coupon $coupon, array $applied_coupons )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $coupons | array | Array of coupons to remove from the cart. |
| $coupon | \WC_Coupon | Coupon object applied to the cart. |
| $applied_coupons | array | Array of applied coupons already applied to the cart. |

### Returns


`array` 

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_apply_with_individual_use_coupon


Filters if a coupon can be applied alongside other individual use coupons.

```php
apply_filters( 'woocommerce_apply_with_individual_use_coupon', boolean $apply_with_individual_use_coupon, \WC_Coupon $coupon, \WC_Coupon $individual_use_coupon, array $applied_coupons )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $apply_with_individual_use_coupon | boolean | Defaults to false. |
| $coupon | \WC_Coupon | Coupon object applied to the cart. |
| $individual_use_coupon | \WC_Coupon | Individual use coupon already applied to the cart. |
| $applied_coupons | array | Array of applied coupons already applied to the cart. |

### Returns


`boolean` 

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_blocks_product_grid_is_cacheable


Filters whether or not the product grid is cacheable.

```php
apply_filters( 'woocommerce_blocks_product_grid_is_cacheable', boolean $is_cacheable, array $query_args )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $is_cacheable | boolean | The list of script dependencies. |
| $query_args | array | Query args for the products query passed to BlocksWpQuery. |

### Returns


`array` True to enable cache, false to disable cache.

### Source


File: [BlockTypes/AbstractProductGrid.php](../src/BlockTypes/AbstractProductGrid.php)

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


File: [BlockTypes/AbstractProductGrid.php](../src/BlockTypes/AbstractProductGrid.php)

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


File: [Assets/Api.php](../src/Assets/Api.php)

---

## woocommerce_cart_contents_changed


Filters the entire cart contents when the cart changes.

```php
apply_filters( 'woocommerce_cart_contents_changed', array $cart_contents )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $cart_contents | array | Array of all cart items. |

### Returns


`array` Updated array of all cart items.

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_ga_disable_tracking


Filter to disable Google Analytics tracking.

```php
apply_filters( 'woocommerce_ga_disable_tracking', boolean $disable_tracking )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $disable_tracking | boolean | If true, tracking will be disabled. |

### Source


File: [Domain/Services/GoogleAnalytics.php](../src/Domain/Services/GoogleAnalytics.php)

---

## woocommerce_get_item_data


Filters cart item data.

```php
apply_filters( 'woocommerce_get_item_data', array $item_data, array $cart_item )
```

### Description

<p>Filters the variation option name for custom option slugs.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $item_data | array | Cart item data. Empty by default. |
| $cart_item | array | Cart item array. |

### Returns


`array` 

### Source


File: [StoreApi/Schemas/CartItemSchema.php](../src/StoreApi/Schemas/CartItemSchema.php)

---

## woocommerce_new_customer_data


Filters customer data before a customer account is registered.

```php
apply_filters( 'woocommerce_new_customer_data', array $customer_data )
```

### Description

<p>This hook filters customer data. It allows user data to be changed, for example, username, password, email, first name, last name, and role.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $customer_data | array | An array of customer (user) data. |

### Returns


`array` 

### Source


File: [Domain/Services/CreateAccount.php](../src/Domain/Services/CreateAccount.php)

---

## woocommerce_registration_errors


Filters registration errors before a customer account is registered.

```php
apply_filters( 'woocommerce_registration_errors', \WP_Error $errors, string $username, string $user_email )
```

### Description

<p>This hook filters registration errors. This can be used to manipulate the array of errors before they are displayed.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $errors | \WP_Error | Error object. |
| $username | string | Customer username. |
| $user_email | string | Customer email address. |

### Returns


`\WP_Error` 

### Source


File: [Domain/Services/CreateAccount.php](../src/Domain/Services/CreateAccount.php)

---

## ~~woocommerce_shared_settings~~


Filters the array of shared settings.

```php
apply_filters( 'woocommerce_shared_settings', array $data )
```


**Deprecated: This hook is deprecated and will be removed**

### Description

<p>Low level hook for registration of new data late in the cycle. This is deprecated. Instead, use the data api:</p> <pre><code class="language-php">Automattic\WooCommerce\Blocks\Package::container()-&gt;get( Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class )-&gt;add( $key, $value )</code></pre>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $data | array | Settings data. |

### Returns


`array` 

### Source


File: [Assets/AssetDataRegistry.php](../src/Assets/AssetDataRegistry.php)

---

## woocommerce_shipping_package_name


Filters the shipping package name.

```php
apply_filters( 'woocommerce_shipping_package_name', string $shipping_package_name, string $package_id, array $package )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $shipping_package_name | string | Shipping package name. |
| $package_id | string | Shipping package ID. |
| $package | array | Shipping package from WooCommerce. |

### Returns


`string` Shipping package name.

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_show_page_title




```php
apply_filters( 'woocommerce_show_page_title' )
```

### Source


File: [BlockTypes/LegacyTemplate.php](../src/BlockTypes/LegacyTemplate.php)

---

## woocommerce_store_api_disable_nonce_check


Filters the Store API nonce check.

```php
apply_filters( 'woocommerce_store_api_disable_nonce_check', boolean $disable_nonce_check )
```

### Description

<p>This can be used to disable the nonce check when testing API endpoints via a REST API client.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $disable_nonce_check | boolean | If true, nonce checks will be disabled. |

### Returns


`boolean` 

### Source


File: [StoreApi/Routes/AbstractCartRoute.php](../src/StoreApi/Routes/AbstractCartRoute.php)

---

## woocommerce_store_api_product_quantity_limit


Filters the quantity limit for a product being added to the cart via the Store API.

```php
apply_filters( 'woocommerce_store_api_product_quantity_limit', integer $quantity_limit, \WC_Product $product )
```

### Description

<p>Filters the variation option name for custom option slugs.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $quantity_limit | integer | Quantity limit which defaults to 99 unless sold individually. |
| $product | \WC_Product | Product instance. |

### Returns


`integer` 

### Source


File: [StoreApi/Schemas/ProductSchema.php](../src/StoreApi/Schemas/ProductSchema.php)

---

## woocommerce_variation_option_name


Filters the variation option name.

```php
apply_filters( 'woocommerce_variation_option_name', string $value, null $unused, string $taxonomy, \WC_Product $product )
```

### Description

<p>Filters the variation option name for custom option slugs.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $value | string | The name to display. |
| $unused | null | Unused because this is not a variation taxonomy. |
| $taxonomy | string | Taxonomy or product attribute name. |
| $product | \WC_Product | Product data. |

### Returns


`string` 

### Source


File: [StoreApi/Schemas/CartItemSchema.php](../src/StoreApi/Schemas/CartItemSchema.php)

---
