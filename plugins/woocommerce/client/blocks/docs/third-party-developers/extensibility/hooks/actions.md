<!-- DO NOT UPDATE THIS DOC DIRECTLY -->

<!-- Use `npm run build:docs` to automatically build hook documentation -->

# Actions

## Table of Contents

- [deprecated_function_run](#deprecated_function_run)
- [woocommerce_add_to_cart](#woocommerce_add_to_cart)
- [woocommerce_after_add_to_cart_button](#woocommerce_after_add_to_cart_button)
- [woocommerce_after_add_to_cart_form](#woocommerce_after_add_to_cart_form)
- [woocommerce_after_add_to_cart_quantity](#woocommerce_after_add_to_cart_quantity)
- [woocommerce_after_main_content](#woocommerce_after_main_content)
- [woocommerce_after_shop_loop](#woocommerce_after_shop_loop)
- [woocommerce_after_single_variation](#woocommerce_after_single_variation)
- [woocommerce_after_variations_form](#woocommerce_after_variations_form)
- [woocommerce_after_variations_table](#woocommerce_after_variations_table)
- [woocommerce_applied_coupon](#woocommerce_applied_coupon)
- [woocommerce_archive_description](#woocommerce_archive_description)
- [woocommerce_before_add_to_cart_button](#woocommerce_before_add_to_cart_button)
- [woocommerce_before_add_to_cart_form](#woocommerce_before_add_to_cart_form)
- [woocommerce_before_add_to_cart_quantity](#woocommerce_before_add_to_cart_quantity)
- [woocommerce_before_main_content](#woocommerce_before_main_content)
- [woocommerce_before_shop_loop](#woocommerce_before_shop_loop)
- [woocommerce_before_single_variation](#woocommerce_before_single_variation)
- [woocommerce_before_variations_form](#woocommerce_before_variations_form)
- [woocommerce_blocks_cart_enqueue_data](#woocommerce_blocks_cart_enqueue_data)
- [woocommerce_blocks_checkout_enqueue_data](#woocommerce_blocks_checkout_enqueue_data)
- [woocommerce_blocks_enqueue_cart_block_scripts_after](#woocommerce_blocks_enqueue_cart_block_scripts_after)
- [woocommerce_blocks_enqueue_cart_block_scripts_before](#woocommerce_blocks_enqueue_cart_block_scripts_before)
- [woocommerce_blocks_enqueue_checkout_block_scripts_after](#woocommerce_blocks_enqueue_checkout_block_scripts_after)
- [woocommerce_blocks_enqueue_checkout_block_scripts_before](#woocommerce_blocks_enqueue_checkout_block_scripts_before)
- [woocommerce_blocks_loaded](#woocommerce_blocks_loaded)
- [`woocommerce_blocks_validate_location_{$location}_fields`](#woocommerce_blocks_validate_location_location_fields)
- [`woocommerce_blocks_{$this->registry_identifier}_registration`](#woocommerce_blocks_this-registry_identifier_registration)
- [woocommerce_check_cart_items](#woocommerce_check_cart_items)
- [woocommerce_checkout_validate_order_before_payment](#woocommerce_checkout_validate_order_before_payment)
- [woocommerce_no_products_found](#woocommerce_no_products_found)
- [woocommerce_rest_checkout_process_payment_with_context](#woocommerce_rest_checkout_process_payment_with_context)
- [woocommerce_set_additional_field_value](#woocommerce_set_additional_field_value)
- [woocommerce_shop_loop](#woocommerce_shop_loop)
- [woocommerce_single_variation](#woocommerce_single_variation)
- [woocommerce_store_api_cart_errors](#woocommerce_store_api_cart_errors)
- [woocommerce_store_api_cart_select_shipping_rate](#woocommerce_store_api_cart_select_shipping_rate)
- [woocommerce_store_api_cart_update_customer_from_request](#woocommerce_store_api_cart_update_customer_from_request)
- [woocommerce_store_api_cart_update_order_from_request](#woocommerce_store_api_cart_update_order_from_request)
- [woocommerce_store_api_checkout_order_created](#woocommerce_store_api_checkout_order_created)
- [woocommerce_store_api_checkout_order_processed](#woocommerce_store_api_checkout_order_processed)
- [woocommerce_store_api_checkout_update_customer_from_request](#woocommerce_store_api_checkout_update_customer_from_request)
- [woocommerce_store_api_checkout_update_draft](#woocommerce_store_api_checkout_update_draft)
- [woocommerce_store_api_checkout_update_order_from_request](#woocommerce_store_api_checkout_update_order_from_request)
- [woocommerce_store_api_checkout_update_order_meta](#woocommerce_store_api_checkout_update_order_meta)
- [woocommerce_store_api_rate_limit_exceeded](#woocommerce_store_api_rate_limit_exceeded)
- [woocommerce_store_api_validate_add_to_cart](#woocommerce_store_api_validate_add_to_cart)
- [woocommerce_store_api_validate_cart_item](#woocommerce_store_api_validate_cart_item)
- [woocommerce_validate_additional_field](#woocommerce_validate_additional_field)
- [`woocommerce_{$product->get_type()}_add_to_cart`](#woocommerce_product-get_type_add_to_cart)
- [`woocommerce_{$product_type}_add_to_cart`](#woocommerce_product_type_add_to_cart)

---

## deprecated_function_run


Fires when a deprecated function is called.

```php
do_action( 'deprecated_function_run' )
```

### Source

- [Blocks/Domain/Bootstrap.php](../../../../../../src/Blocks/Domain/Bootstrap.php)

---

## woocommerce_add_to_cart


Fires when an item is added to the cart.

```php
do_action( 'woocommerce_add_to_cart', string $cart_id, int $product_id, int $request_quantity, int $variation_id, array $variation, array $cart_item_data )
```


**Note:** Matches action name in WooCommerce core.

### Description

This hook fires when an item is added to the cart. This is triggered from the Store API in this context, but WooCommerce core add to cart events trigger the same hook.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $cart_id | string | ID of the item in the cart. |
| $product_id | int | ID of the product added to the cart. |
| $request_quantity | int | Quantity of the item added to the cart. |
| $variation_id | int | Variation ID of the product added to the cart. |
| $variation | array | Array of variation data. |
| $cart_item_data | array | Array of other cart item data. |

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_after_add_to_cart_button


Hook: woocommerce_after_add_to_cart_button.

```php
do_action( 'woocommerce_after_add_to_cart_button' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_after_add_to_cart_form


Hook: woocommerce_after_add_to_cart_form.

```php
do_action( 'woocommerce_after_add_to_cart_form' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_after_add_to_cart_quantity


Hook: woocommerce_after_add_to_cart_quantity.

```php
do_action( 'woocommerce_after_add_to_cart_quantity' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_after_main_content


Hook: woocommerce_after_main_content

```php
do_action( 'woocommerce_after_main_content' )
```

### Description

Called after rendering the main content for a product.

### See

- woocommerce_output_content_wrapper_end() - Outputs closing DIV for the content (priority 10)

### Source

- [Blocks/BlockTypes/ClassicTemplate.php](../../../../../../src/Blocks/BlockTypes/ClassicTemplate.php)

---

## woocommerce_after_shop_loop


Hook: woocommerce_after_shop_loop.

```php
do_action( 'woocommerce_after_shop_loop' )
```

### See

- woocommerce_pagination() - Renders pagination (priority 10)

### Source

- [Blocks/BlockTypes/ClassicTemplate.php](../../../../../../src/Blocks/BlockTypes/ClassicTemplate.php)

---

## woocommerce_after_single_variation


Hook: woocommerce_after_single_variation.

```php
do_action( 'woocommerce_after_single_variation' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_after_variations_form


Hook: woocommerce_after_variations_form.

```php
do_action( 'woocommerce_after_variations_form' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_after_variations_table


Hook: woocommerce_after_variations_table.

```php
do_action( 'woocommerce_after_variations_table' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_applied_coupon


Fires after a coupon has been applied to the cart.

```php
do_action( 'woocommerce_applied_coupon', string $coupon_code )
```


**Note:** Matches action name in WooCommerce core.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $coupon_code | string | The coupon code that was applied. |

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_archive_description


Hook: woocommerce_archive_description.

```php
do_action( 'woocommerce_archive_description' )
```

### See

- woocommerce_taxonomy_archive_description() - Renders the taxonomy archive description (priority 10)
- woocommerce_product_archive_description() - Renders the product archive description (priority 10)

### Source

- [Blocks/BlockTypes/ClassicTemplate.php](../../../../../../src/Blocks/BlockTypes/ClassicTemplate.php)

---

## woocommerce_before_add_to_cart_button


Hook: woocommerce_before_add_to_cart_button.

```php
do_action( 'woocommerce_before_add_to_cart_button' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_before_add_to_cart_form


Hook: woocommerce_before_add_to_cart_form.

```php
do_action( 'woocommerce_before_add_to_cart_form' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_before_add_to_cart_quantity


Hook: woocommerce_before_add_to_cart_quantity.

```php
do_action( 'woocommerce_before_add_to_cart_quantity' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_before_main_content


Hook: woocommerce_before_main_content

```php
do_action( 'woocommerce_before_main_content' )
```

### Description

Called before rendering the main content for a product.

### See

- woocommerce_output_content_wrapper() - Outputs opening DIV for the content (priority 10)
- woocommerce_breadcrumb() - Outputs breadcrumb trail to the current product (priority 20)
- WC_Structured_Data::generate_website_data() - Outputs schema markup (priority 30)

### Source

- [Blocks/BlockTypes/ClassicTemplate.php](../../../../../../src/Blocks/BlockTypes/ClassicTemplate.php)

---

## woocommerce_before_shop_loop


Hook: woocommerce_before_shop_loop.

```php
do_action( 'woocommerce_before_shop_loop' )
```

### See

- woocommerce_output_all_notices() - Render error notices (priority 10)
- woocommerce_result_count() - Show number of results found (priority 20)
- woocommerce_catalog_ordering() - Show form to control sort order (priority 30)

### Source

- [Blocks/BlockTypes/ClassicTemplate.php](../../../../../../src/Blocks/BlockTypes/ClassicTemplate.php)

---

## woocommerce_before_single_variation


Hook: woocommerce_before_single_variation.

```php
do_action( 'woocommerce_before_single_variation' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_before_variations_form


Hook: woocommerce_before_variations_form.

```php
do_action( 'woocommerce_before_variations_form' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_blocks_cart_enqueue_data


Fires after cart block data is registered.

```php
do_action( 'woocommerce_blocks_cart_enqueue_data' )
```

### Source

- [Blocks/BlockTypes/Cart.php](../../../../../../src/Blocks/BlockTypes/Cart.php)
- [Blocks/BlockTypes/MiniCart.php](../../../../../../src/Blocks/BlockTypes/MiniCart.php)

---

## woocommerce_blocks_checkout_enqueue_data


Fires after checkout block data is registered.

```php
do_action( 'woocommerce_blocks_checkout_enqueue_data' )
```

### Source

- [Blocks/BlockTypes/Checkout.php](../../../../../../src/Blocks/BlockTypes/Checkout.php)

---

## woocommerce_blocks_enqueue_cart_block_scripts_after


Fires after cart block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after' )
```

### Source

- [Blocks/BlockTypes/Cart.php](../../../../../../src/Blocks/BlockTypes/Cart.php)

---

## woocommerce_blocks_enqueue_cart_block_scripts_before


Fires before cart block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_cart_block_scripts_before' )
```

### Source

- [Blocks/BlockTypes/Cart.php](../../../../../../src/Blocks/BlockTypes/Cart.php)

---

## woocommerce_blocks_enqueue_checkout_block_scripts_after


Fires after checkout block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after' )
```

### Source

- [Blocks/BlockTypes/Checkout.php](../../../../../../src/Blocks/BlockTypes/Checkout.php)

---

## woocommerce_blocks_enqueue_checkout_block_scripts_before


Fires before checkout block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before' )
```

### Source

- [Blocks/BlockTypes/Checkout.php](../../../../../../src/Blocks/BlockTypes/Checkout.php)

---

## woocommerce_blocks_loaded


Fires when the woocommerce blocks are loaded and ready to use.

```php
do_action( 'woocommerce_blocks_loaded' )
```

### Description

This hook is intended to be used as a safe event hook for when the plugin has been loaded, and all dependency requirements have been met.

To ensure blocks are initialized, you must use the `woocommerce_blocks_loaded` hook instead of the `plugins_loaded` hook. This is because the functions hooked into plugins_loaded on the same priority load in an inconsistent and unpredictable manner.

### Source

- [Blocks/Domain/Bootstrap.php](../../../../../../src/Blocks/Domain/Bootstrap.php)

---

## `woocommerce_blocks_validate_location_{$location}_fields`


Pass an error object to allow validation of an additional field.

```php
do_action( 'woocommerce_blocks_validate_location_{$location}_fields', \WP_Error $errors, mixed $fields, string $group )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $errors | \WP_Error | A WP_Error object that extensions may add errors to. |
| $fields | mixed | List of fields (key value pairs) in this location. |
| $group | string | The group of this location (shipping\|billing\|other). |

### Source

- [Blocks/Domain/Services/CheckoutFields.php](../../../../../../src/Blocks/Domain/Services/CheckoutFields.php)

---

## `woocommerce_blocks_{$this->registry_identifier}_registration`


Fires when the IntegrationRegistry is initialized.

```php
do_action( 'woocommerce_blocks_{$this->registry_identifier}_registration', \IntegrationRegistry $registry )
```

### Description

Runs before integrations are initialized allowing new integration to be registered for use. This should be used as the primary hook for integrations to include their scripts, styles, and other code extending the blocks.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $registry | \IntegrationRegistry | Instance of the IntegrationRegistry class which exposes the IntegrationRegistry::register() method. |

### Source

- [Blocks/Integrations/IntegrationRegistry.php](../../../../../../src/Blocks/Integrations/IntegrationRegistry.php)

---

## woocommerce_check_cart_items


Fires when cart items are being validated.

```php
do_action( 'woocommerce_check_cart_items' )
```


**Note:** Matches action name in WooCommerce core.

### Description

Allow 3rd parties to validate cart items. This is a legacy hook from Woo core.

This action will be deprecated in the Store API because it encourages wc_add_notice: the API has to capture those notices and convert them to WP_Error objects. Prefer `woocommerce_store_api_cart_errors`, which passes a WP_Error to callbacks directly. Core keeps firing this action from the classic cart and checkout, so it is not deprecated there.

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_checkout_validate_order_before_payment


Allow plugins to perform custom validation before payment.

```php
do_action( 'woocommerce_checkout_validate_order_before_payment', \WC_Order $order, \WP_Error $validation_errors )
```

### Description

Plugins can add errors to the $validation_errors object.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $order | \WC_Order | The order object. |
| $validation_errors | \WP_Error | WP_Error object to add custom errors to. |

### Source

- [StoreApi/Utilities/OrderController.php](../../../../../../src/StoreApi/Utilities/OrderController.php)

---

## woocommerce_no_products_found


Hook: woocommerce_no_products_found.

```php
do_action( 'woocommerce_no_products_found' )
```

### See

- wc_no_products_found() - Default no products found content (priority 10)

### Source

- [Blocks/BlockTypes/ClassicTemplate.php](../../../../../../src/Blocks/BlockTypes/ClassicTemplate.php)

---

## woocommerce_rest_checkout_process_payment_with_context


Process payment with context.

```php
do_action_ref_array( 'woocommerce_rest_checkout_process_payment_with_context', [ \PaymentContext $context, \PaymentResult $payment_result ] )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $context | \PaymentContext | Holds context for the payment, including order ID and payment method. |
| $payment_result | \PaymentResult | Result object for the transaction. |

### Exceptions


`\Exception` If there is an error taking payment, an \Exception object can be thrown with an error message.

### Source

- [StoreApi/Utilities/CheckoutTrait.php](../../../../../../src/StoreApi/Utilities/CheckoutTrait.php)

---

## woocommerce_set_additional_field_value


Allow reacting for saving an additional field value.

```php
do_action( 'woocommerce_set_additional_field_value', string $key, mixed $value, string $group, \WC_Customer|\WC_Order $wc_object )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $key | string | The key of the field being saved. |
| $value | mixed | The value of the field being saved. |
| $group | string | The group of this location (shipping\|billing\|other). |
| $wc_object | \WC_Customer, \WC_Order | The object to set the field value for. |

### Source

- [Blocks/Domain/Services/CheckoutFields.php](../../../../../../src/Blocks/Domain/Services/CheckoutFields.php)

---

## woocommerce_shop_loop


Hook: woocommerce_shop_loop.

```php
do_action( 'woocommerce_shop_loop' )
```

### Source

- [Blocks/BlockTypes/ClassicTemplate.php](../../../../../../src/Blocks/BlockTypes/ClassicTemplate.php)

---

## woocommerce_single_variation


Hook: woocommerce_single_variation.

```php
do_action( 'woocommerce_single_variation' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---

## woocommerce_store_api_cart_errors


Fires an action to validate the cart.

```php
do_action( 'woocommerce_store_api_cart_errors', \WP_Error $errors, \WC_Cart $cart )
```

### Description

Functions hooking into this should add custom errors using the provided WP_Error instance.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $errors | \WP_Error | WP_Error object. |
| $cart | \WC_Cart | Cart object. |

### Example

#### Validate Cart

```php
// The action callback function.
function my_function_callback( $errors, $cart ) {

  // Validate the $cart object and add errors. For example, to create an error if the cart contains more than 10 items:
  if ( $cart->get_cart_contents_count() > 10 ) {
    $errors->add( 'my_error_code', 'Too many cart items!' );
  }
}

add_action( 'woocommerce_store_api_cart_errors', 'my_function_callback', 10 );
```


### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_store_api_cart_select_shipping_rate


Fires an action after a shipping method has been chosen for package(s) via the Store API.

```php
do_action( 'woocommerce_store_api_cart_select_shipping_rate', string|null $package_id, string $rate_id, \WP_REST_Request $request )
```

### Description

This allows extensions to perform addition actions after a shipping method has been chosen, but before the cart totals are recalculated.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $package_id | string, null | The sanitized ID of the package being updated. Null if all packages are being updated. |
| $rate_id | string | The sanitized chosen rate ID for the package. |
| $request | \WP_REST_Request | Full details about the request. |

### Source

- [StoreApi/Routes/V1/CartSelectShippingRate.php](../../../../../../src/StoreApi/Routes/V1/CartSelectShippingRate.php)

---

## woocommerce_store_api_cart_update_customer_from_request


Fires when the Checkout Block/Store API updates a customer from the API request data.

```php
do_action( 'woocommerce_store_api_cart_update_customer_from_request', \WC_Customer $customer, \WP_REST_Request $request )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $customer | \WC_Customer | Customer object. |
| $request | \WP_REST_Request | Full details about the request. |

### Source

- [StoreApi/Routes/V1/CartUpdateCustomer.php](../../../../../../src/StoreApi/Routes/V1/CartUpdateCustomer.php)

---

## woocommerce_store_api_cart_update_order_from_request


Fires when the order is synced with cart data from a cart route.

```php
do_action( 'woocommerce_store_api_cart_update_order_from_request', \WC_Order $draft_order, \WC_Customer $customer, \WP_REST_Request $request )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $draft_order | \WC_Order | Order object. |
| $customer | \WC_Customer | Customer object. |
| $request | \WP_REST_Request | Full details about the request. |

### Source

- [StoreApi/Routes/V1/AbstractCartRoute.php](../../../../../../src/StoreApi/Routes/V1/AbstractCartRoute.php)

---

## woocommerce_store_api_checkout_order_created


Fires once when the Store API checkout draft order is first materialised.

```php
do_action( 'woocommerce_store_api_checkout_order_created', \WC_Order $order )
```

### Description

Use this hook for first-touch logic that should only run when the draft order is initially created (e.g. analytics, abandoned-cart trackers). As of WooCommerce 10.8.0 the Store API defers draft order creation to place-order time, so this action fires once at POST rather than on the first PATCH.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $order | \WC_Order | Order object. |

### Source

- [StoreApi/Routes/V1/Checkout.php](../../../../../../src/StoreApi/Routes/V1/Checkout.php)

---

## woocommerce_store_api_checkout_order_processed


Fires after the Checkout Block/Store API request has populated and validated the order.

```php
do_action( 'woocommerce_store_api_checkout_order_processed', \WC_Order $order )
```

### Description

The action runs before payment is processed, so callbacks can still act on the order on its way to the gateway.

This is similar to existing core hook woocommerce_checkout_order_processed. We're using a new action:

- To keep the interface focused (only pass $order, not passing request data).
- This also explicitly indicates these orders are from checkout block/StoreAPI.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $order | \WC_Order | Order object. |

### Example

#### Checkout Order Processed

```php
// The action callback function.
function my_function_callback( $order ) {
  // Do something with the $order object.
  $order->save();
}

add_action( 'woocommerce_blocks_checkout_order_processed', 'my_function_callback', 10 );
```


### See

- <https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3238>

### Source

- [StoreApi/Routes/V1/CheckoutOrder.php](../../../../../../src/StoreApi/Routes/V1/CheckoutOrder.php)
- [StoreApi/Routes/V1/Checkout.php](../../../../../../src/StoreApi/Routes/V1/Checkout.php)

---

## woocommerce_store_api_checkout_update_customer_from_request


Fires when the Checkout Block/Store API updates a customer from the API request data.

```php
do_action( 'woocommerce_store_api_checkout_update_customer_from_request', \WC_Customer $customer, \WP_REST_Request $request )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $customer | \WC_Customer | Customer object. |
| $request | \WP_REST_Request | Full details about the request. |

### Source

- [StoreApi/Routes/V1/CheckoutOrder.php](../../../../../../src/StoreApi/Routes/V1/CheckoutOrder.php)
- [StoreApi/Routes/V1/Checkout.php](../../../../../../src/StoreApi/Routes/V1/Checkout.php)

---

## woocommerce_store_api_checkout_update_draft


Fires after a Store API checkout PATCH request has been validated and live customer/session state has been updated, before the response is returned.

```php
do_action( 'woocommerce_store_api_checkout_update_draft', \WP_REST_Request $request )
```

### Description

Hook this action when an extension needs to observe live checkout state — e.g. abandoned-cart trackers, side-panel previews, conditional shipping or payment validators, or anything else that needs to react to every customer interaction with the form.

No `WC_Order` exists at this point under deferred draft order creation. Read checkout state from `WC()->cart`, `WC()->customer`, and the supplied `$request`, and persist any extension-owned state to `WC()->session`. To apply that state to the real order at place-order time, hook `woocommerce_store_api_checkout_update_order_meta` or `woocommerce_store_api_checkout_update_order_from_request` — both fire against the real, persisted order at POST exactly as they always have.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $request | \WP_REST_Request | The current PATCH request. |

### Source

- [StoreApi/Routes/V1/Checkout.php](../../../../../../src/StoreApi/Routes/V1/Checkout.php)

---

## woocommerce_store_api_checkout_update_order_from_request


Fires when the Checkout Block/Store API updates an order's from the API request data.

```php
do_action( 'woocommerce_store_api_checkout_update_order_from_request', \WC_Order $order, \WP_REST_Request $request )
```

### Description

This hook gives extensions the chance to update orders based on the data in the request. This can be used in conjunction with the ExtendSchema class to post custom data and then process it.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $order | \WC_Order | Order object. |
| $request | \WP_REST_Request | Full details about the request. |

### Source

- [StoreApi/Utilities/CheckoutTrait.php](../../../../../../src/StoreApi/Utilities/CheckoutTrait.php)

---

## woocommerce_store_api_checkout_update_order_meta


Fires when the Checkout Block/Store API updates an order's meta data.

```php
do_action( 'woocommerce_store_api_checkout_update_order_meta', \WC_Order $order )
```

### Description

This hook gives extensions the chance to add or update meta data on the $order. Throwing an exception from a callback attached to this action will make the Checkout Block render in a warning state, effectively preventing checkout.

This is similar to existing core hook woocommerce_checkout_update_order_meta. We're using a new action:

- To keep the interface focused (only pass $order, not passing request data).
- This also explicitly indicates these orders are from checkout block/StoreAPI.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $order | \WC_Order | Order object. |

### See

- <https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3686>

### Source

- [StoreApi/Routes/V1/Checkout.php](../../../../../../src/StoreApi/Routes/V1/Checkout.php)

---

## woocommerce_store_api_rate_limit_exceeded


Fires when the rate limit is exceeded.

```php
do_action( 'woocommerce_store_api_rate_limit_exceeded', string $ip_address, string $action_id )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $ip_address | string | The IP address of the request. |
| $action_id | string | The grouping identifier to the request. |

### Source

- [StoreApi/Authentication.php](../../../../../../src/StoreApi/Authentication.php)

---

## woocommerce_store_api_validate_add_to_cart


Fires during validation when adding an item to the cart via the Store API.

```php
do_action( 'woocommerce_store_api_validate_add_to_cart', \WC_Product $product, array $request )
```

### Description

Fire action to validate add to cart. Functions hooking into this should throw an \Exception to prevent add to cart from happening.

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $product | \WC_Product | Product object being added to the cart. |
| $request | array | Add to cart request params including id, quantity, and variation attributes. |

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_store_api_validate_cart_item


Fire action to validate add to cart. Functions hooking into this should throw an \Exception to prevent add to cart from occurring.

```php
do_action( 'woocommerce_store_api_validate_cart_item', \WC_Product $product, array $cart_item )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $product | \WC_Product | Product object being added to the cart. |
| $cart_item | array | Cart item array. |

### Source

- [StoreApi/Utilities/CartController.php](../../../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_validate_additional_field


Pass an error object to allow validation of an additional field.

```php
do_action( 'woocommerce_validate_additional_field', \WP_Error $errors, string $field_key, mixed $field_value )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $errors | \WP_Error | A WP_Error object that extensions may add errors to. |
| $field_key | string | Key of the field being sanitized. |
| $field_value | mixed | The value of the field being validated. |

### Source

- [Blocks/Domain/Services/CheckoutFields.php](../../../../../../src/Blocks/Domain/Services/CheckoutFields.php)

---

## `woocommerce_{$product->get_type()}_add_to_cart`


Trigger the single product add to cart action for each product type.

```php
do_action( 'woocommerce_{$product->get_type()}_add_to_cart' )
```

### Source

- [Blocks/BlockTypes/AddToCartForm.php](../../../../../../src/Blocks/BlockTypes/AddToCartForm.php)

---

## `woocommerce_{$product_type}_add_to_cart`


Trigger the single product add to cart action that prints the markup.

```php
do_action( 'woocommerce_{$product_type}_add_to_cart' )
```

### Source

- [Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php](../../../../../../src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php)

---
<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/hooks/actions.md)

<!-- /FEEDBACK -->
