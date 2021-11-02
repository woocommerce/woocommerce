<!-- DO NOT UPDATE THIS DOC DIRECTLY -->

<!-- Use `npm run build:docs` to automatically build hook documentation -->

# Actions

## Table of Contents


 - [__experimental_woocommerce_blocks_checkout_update_order_from_request](#__experimental_woocommerce_blocks_checkout_update_order_from_request)
 - [__experimental_woocommerce_blocks_checkout_update_order_meta](#__experimental_woocommerce_blocks_checkout_update_order_meta)
 - [woocommerce_add_to_cart](#woocommerce_add_to_cart)
 - [woocommerce_applied_coupon](#woocommerce_applied_coupon)
 - [woocommerce_blocks_cart_enqueue_data](#woocommerce_blocks_cart_enqueue_data)
 - [woocommerce_blocks_cart_enqueue_data](#woocommerce_blocks_cart_enqueue_data-1)
 - [woocommerce_blocks_checkout_enqueue_data](#woocommerce_blocks_checkout_enqueue_data)
 - [woocommerce_blocks_checkout_order_processed](#woocommerce_blocks_checkout_order_processed)
 - [woocommerce_blocks_enqueue_cart_block_scripts_after](#woocommerce_blocks_enqueue_cart_block_scripts_after)
 - [woocommerce_blocks_enqueue_cart_block_scripts_before](#woocommerce_blocks_enqueue_cart_block_scripts_before)
 - [woocommerce_blocks_enqueue_checkout_block_scripts_after](#woocommerce_blocks_enqueue_checkout_block_scripts_after)
 - [woocommerce_blocks_enqueue_checkout_block_scripts_before](#woocommerce_blocks_enqueue_checkout_block_scripts_before)
 - [woocommerce_blocks_loaded](#woocommerce_blocks_loaded)
 - [woocommerce_blocks_{$this->registry_identifier}_registration](#woocommerce_blocks_-this--registry_identifier-_registration)
 - [woocommerce_check_cart_items](#woocommerce_check_cart_items)
 - [woocommerce_created_customer](#woocommerce_created_customer)
 - [woocommerce_register_post](#woocommerce_register_post)
 - [woocommerce_rest_checkout_process_payment_with_context](#woocommerce_rest_checkout_process_payment_with_context)
 - [wooocommerce_store_api_validate_add_to_cart](#wooocommerce_store_api_validate_add_to_cart)
 - [wooocommerce_store_api_validate_cart_item](#wooocommerce_store_api_validate_cart_item)

---

## __experimental_woocommerce_blocks_checkout_update_order_from_request


Fires when the Checkout Block/Store API updates an order's from the API request data.

```php
do_action( '__experimental_woocommerce_blocks_checkout_update_order_from_request', \WC_Order $order, \WP_REST_Request $request )
```


**Note: This Hook is experimental and may change or be removed.**

### Description

<p>This hook gives extensions the chance to update orders based on the data in the request. This can be used in conjunction with the ExtendRestAPI class to post custom data and then process it.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $order | \WC_Order | Order object. |
| $request | \WP_REST_Request | Full details about the request. |

### Source


File: [StoreApi/Routes/Checkout.php](../src/StoreApi/Routes/Checkout.php)

---

## __experimental_woocommerce_blocks_checkout_update_order_meta


Fires when the Checkout Block/Store API updates an order's meta data.

```php
do_action( '__experimental_woocommerce_blocks_checkout_update_order_meta', \WC_Order $order )
```


**Note: This Hook is experimental and may change or be removed.**

### Description

<p>This hook gives extensions the chance to add or update meta data on the $order.</p> <p>This is similar to existing core hook woocommerce_checkout_update_order_meta. We're using a new action:</p> <ul> <li>To keep the interface focused (only pass $order, not passing request data).</li> <li>This also explicitly indicates these orders are from checkout block/StoreAPI.</li> </ul>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $order | \WC_Order | Order object. |

### Related


 - https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3686

### Source


File: [StoreApi/Routes/Checkout.php](../src/StoreApi/Routes/Checkout.php)

---

## woocommerce_add_to_cart


Fires when an item is added to the cart.

```php
do_action( 'woocommerce_add_to_cart', string $cart_id, integer $product_id, integer $quantity, integer $variation_id, array $variation, array $cart_item_data )
```

### Description

<p>This hook fires when an item is added to the cart. This is triggered from the Store API in this context, but WooCommerce core add to cart events trigger the same hook.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $cart_id | string | ID of the item in the cart. |
| $product_id | integer | ID of the product added to the cart. |
| $quantity | integer | Quantity of the item added to the cart. |
| $variation_id | integer | Variation ID of the product added to the cart. |
| $variation | array | Array of variation data. |
| $cart_item_data | array | Array of other cart item data. |

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_applied_coupon


Fires after a coupon has been applied to the cart.

```php
do_action( 'woocommerce_applied_coupon', string $coupon_code )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $coupon_code | string | The coupon code that was applied. |

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_blocks_cart_enqueue_data


Fires after cart block data is registered.

```php
do_action( 'woocommerce_blocks_cart_enqueue_data' )
```

### Source


File: [BlockTypes/Cart.php](../src/BlockTypes/Cart.php)

---

## woocommerce_blocks_cart_enqueue_data


Fires after cart block data is registered.

```php
do_action( 'woocommerce_blocks_cart_enqueue_data' )
```

### Source


File: [BlockTypes/MiniCart.php](../src/BlockTypes/MiniCart.php)

---

## woocommerce_blocks_checkout_enqueue_data


Fires after checkout block data is registered.

```php
do_action( 'woocommerce_blocks_checkout_enqueue_data' )
```

### Source


File: [BlockTypes/Checkout.php](../src/BlockTypes/Checkout.php)

---

## woocommerce_blocks_checkout_order_processed


Fires before an order is processed by the Checkout Block/Store API.

```php
do_action( 'woocommerce_blocks_checkout_order_processed', \WC_Order $order )
```

### Description

<p>This hook informs extensions that $order has completed processing and is ready for payment.</p> <p>This is similar to existing core hook woocommerce_checkout_order_processed. We're using a new action:</p> <ul> <li>To keep the interface focused (only pass $order, not passing request data).</li> <li>This also explicitly indicates these orders are from checkout block/StoreAPI.</li> </ul>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $order | \WC_Order | Order object. |

### Example

```php
// The action callback function.
function my_function_callback( $order ) {
  // Do something with the $order object.
  $order->save();
}

add_action( 'woocommerce_blocks_checkout_order_processed', 'my_function_callback', 10 );
```


### Related


 - https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3238

### Source


File: [StoreApi/Routes/Checkout.php](../src/StoreApi/Routes/Checkout.php)

---

## woocommerce_blocks_enqueue_cart_block_scripts_after


Fires after cart block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after' )
```

### Source


File: [BlockTypes/Cart.php](../src/BlockTypes/Cart.php)

---

## woocommerce_blocks_enqueue_cart_block_scripts_before


Fires before cart block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_cart_block_scripts_before' )
```

### Source


File: [BlockTypes/Cart.php](../src/BlockTypes/Cart.php)

---

## woocommerce_blocks_enqueue_checkout_block_scripts_after


Fires after checkout block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after' )
```

### Source


File: [BlockTypes/Checkout.php](../src/BlockTypes/Checkout.php)

---

## woocommerce_blocks_enqueue_checkout_block_scripts_before


Fires before checkout block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before' )
```

### Source


File: [BlockTypes/Checkout.php](../src/BlockTypes/Checkout.php)

---

## woocommerce_blocks_loaded


Fires after WooCommerce Blocks plugin has loaded.

```php
do_action( 'woocommerce_blocks_loaded' )
```

### Description

<p>This hook is intended to be used as a safe event hook for when the plugin has been loaded, and all dependency requirements have been met.</p>

### Source


File: [Domain/Bootstrap.php](../src/Domain/Bootstrap.php)

---

## woocommerce_blocks_{$this->registry_identifier}_registration


Fires when the IntegrationRegistry is initialized.

```php
do_action( 'woocommerce_blocks_{$this->registry_identifier}_registration', \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry $this )
```

### Description

<p>Runs before integrations are initialized allowing new integration to be registered for use. This should be used as the primary hook for integrations to include their scripts, styles, and other code extending the blocks.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $this | \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry | Instance of the IntegrationRegistry class which exposes the IntegrationRegistry::register() method. |

### Source


File: [Integrations/IntegrationRegistry.php](../src/Integrations/IntegrationRegistry.php)

---

## woocommerce_check_cart_items


Fires when cart items are being validated.

```php
do_action( 'woocommerce_check_cart_items' )
```

### Description

<p>Allow 3rd parties to validate cart items. This is a legacy hook from Woo core. This filter will be deprecated because it encourages usage of wc_add_notice. For the API we need to capture notices and convert to exceptions instead.</p>

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_created_customer


Fires after a customer account has been registered.

```php
do_action( 'woocommerce_created_customer', integer $customer_id, array $new_customer_data, string $password_generated )
```

### Description

<p>This hook fires after customer accounts are created and passes the customer data.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $customer_id | integer | New customer (user) ID. |
| $new_customer_data | array | Array of customer (user) data. |
| $password_generated | string | The generated password for the account. |

### Source


File: [Domain/Services/CreateAccount.php](../src/Domain/Services/CreateAccount.php)

---

## woocommerce_register_post


Fires before a customer account is registered.

```php
do_action( 'woocommerce_register_post', string $username, string $user_email, \WP_Error $errors )
```

### Description

<p>This hook fires before customer accounts are created and passes the form data (username, email) and an array of errors.</p> <p>This could be used to add extra validation logic and append errors to the array.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $username | string | Customer username. |
| $user_email | string | Customer email address. |
| $errors | \WP_Error | Error object. |

### Source


File: [Domain/Services/CreateAccount.php](../src/Domain/Services/CreateAccount.php)

---

## woocommerce_rest_checkout_process_payment_with_context


Process payment with context.

```php
do_action_ref_array( 'woocommerce_rest_checkout_process_payment_with_context', [ \Automattic\WooCommerce\Blocks\Payments\PaymentContext $context, \Automattic\WooCommerce\Blocks\Payments\PaymentResult $payment_result ] )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $context | \Automattic\WooCommerce\Blocks\Payments\PaymentContext | Holds context for the payment, including order ID and payment method. |
| $payment_result | \Automattic\WooCommerce\Blocks\Payments\PaymentResult | Result object for the transaction. |

### Exceptions


`\Exception` If there is an error taking payment, an \Exception object can be thrown with an error message.

### Source


File: [StoreApi/Routes/Checkout.php](../src/StoreApi/Routes/Checkout.php)

---

## wooocommerce_store_api_validate_add_to_cart


Fires during validation when adding an item to the cart via the Store API.

```php
do_action( 'wooocommerce_store_api_validate_add_to_cart', \WC_Product $product, array $request )
```

### Description

<p>Fire action to validate add to cart. Functions hooking into this should throw an \Exception to prevent add to cart from happening.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $product | \WC_Product | Product object being added to the cart. |
| $request | array | Add to cart request params including id, quantity, and variation attributes. |

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---

## wooocommerce_store_api_validate_cart_item


Fire action to validate add to cart. Functions hooking into this should throw an \Exception to prevent add to cart from occurring.

```php
do_action( 'wooocommerce_store_api_validate_cart_item', \WC_Product $product, array $cart_item )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $product | \WC_Product | Product object being added to the cart. |
| $cart_item | array | Cart item array. |

### Source


File: [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

---
