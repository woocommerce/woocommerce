<!-- DO NOT UPDATE THIS DOC DIRECTLY -->

<!-- Use `npm run build:docs` to automatically build hook documentation -->

# Actions

## Table of Contents


 - [deprecated_function_run](#deprecated_function_run)
 - [woocommerce_add_to_cart](#woocommerce_add_to_cart)
 - [woocommerce_after_main_content](#woocommerce_after_main_content)
 - [woocommerce_after_shop_loop](#woocommerce_after_shop_loop)
 - [woocommerce_applied_coupon](#woocommerce_applied_coupon)
 - [woocommerce_archive_description](#woocommerce_archive_description)
 - [woocommerce_before_main_content](#woocommerce_before_main_content)
 - [woocommerce_before_shop_loop](#woocommerce_before_shop_loop)
 - [woocommerce_blocks_cart_enqueue_data](#woocommerce_blocks_cart_enqueue_data)
 - [woocommerce_blocks_checkout_enqueue_data](#woocommerce_blocks_checkout_enqueue_data)
 - [woocommerce_blocks_enqueue_cart_block_scripts_after](#woocommerce_blocks_enqueue_cart_block_scripts_after)
 - [woocommerce_blocks_enqueue_cart_block_scripts_before](#woocommerce_blocks_enqueue_cart_block_scripts_before)
 - [woocommerce_blocks_enqueue_checkout_block_scripts_after](#woocommerce_blocks_enqueue_checkout_block_scripts_after)
 - [woocommerce_blocks_enqueue_checkout_block_scripts_before](#woocommerce_blocks_enqueue_checkout_block_scripts_before)
 - [woocommerce_blocks_loaded](#woocommerce_blocks_loaded)
 - [woocommerce_blocks_{$this->registry_identifier}_registration](#woocommerce_blocks_this-registry_identifier_registration)
 - [woocommerce_check_cart_items](#woocommerce_check_cart_items)
 - [woocommerce_created_customer](#woocommerce_created_customer)
 - [woocommerce_no_products_found](#woocommerce_no_products_found)
 - [woocommerce_register_post](#woocommerce_register_post)
 - [woocommerce_shop_loop](#woocommerce_shop_loop)
 - [woocommerce_store_api_cart_errors](#woocommerce_store_api_cart_errors)
 - [woocommerce_store_api_cart_select_shipping_rate](#woocommerce_store_api_cart_select_shipping_rate)
 - [woocommerce_store_api_cart_update_customer_from_request](#woocommerce_store_api_cart_update_customer_from_request)
 - [woocommerce_store_api_cart_update_order_from_request](#woocommerce_store_api_cart_update_order_from_request)
 - [woocommerce_store_api_checkout_order_processed](#woocommerce_store_api_checkout_order_processed)
 - [woocommerce_store_api_checkout_update_customer_from_request](#woocommerce_store_api_checkout_update_customer_from_request)
 - [woocommerce_store_api_checkout_update_order_meta](#woocommerce_store_api_checkout_update_order_meta)
 - [woocommerce_store_api_rate_limit_exceeded](#woocommerce_store_api_rate_limit_exceeded)
 - [woocommerce_store_api_validate_add_to_cart](#woocommerce_store_api_validate_add_to_cart)
 - [woocommerce_store_api_validate_cart_item](#woocommerce_store_api_validate_cart_item)
 - [woocommerce_{$product->get_type()}_add_to_cart](#woocommerce_product-get_type_add_to_cart)
 - [{$hook}](#hook)

---

## deprecated_function_run


Fires when a deprecated function is called.

```php
do_action( 'deprecated_function_run' )
```

### Source


 - [Domain/Bootstrap.php](../../../../src/Domain/Bootstrap.php)

---

## woocommerce_add_to_cart


Fires when an item is added to the cart.

```php
do_action( 'woocommerce_add_to_cart', string $cart_id, integer $product_id, integer $request_quantity, integer $variation_id, array $variation, array $cart_item_data )
```


**Note: Matches action name in WooCommerce core.**

### Description

<p>This hook fires when an item is added to the cart. This is triggered from the Store API in this context, but WooCommerce core add to cart events trigger the same hook.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $cart_id | string | ID of the item in the cart. |
| $product_id | integer | ID of the product added to the cart. |
| $request_quantity | integer | Quantity of the item added to the cart. |
| $variation_id | integer | Variation ID of the product added to the cart. |
| $variation | array | Array of variation data. |
| $cart_item_data | array | Array of other cart item data. |

### Source


 - [StoreApi/Utilities/CartController.php](../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_after_main_content


Hook: woocommerce_after_main_content

```php
do_action( 'woocommerce_after_main_content' )
```

### Description

<p>Called after rendering the main content for a product.</p>

### See


 - woocommerce_output_content_wrapper_end() - Outputs closing DIV for the content (priority 10)

### Source


 - [BlockTypes/ClassicTemplate.php](../../../../src/BlockTypes/ClassicTemplate.php)
 - [BlockTypes/ClassicTemplate.php](../../../../src/BlockTypes/ClassicTemplate.php)

---

## woocommerce_after_shop_loop


Hook: woocommerce_after_shop_loop.

```php
do_action( 'woocommerce_after_shop_loop' )
```

### See


 - woocommerce_pagination() - Renders pagination (priority 10)

### Source


 - [BlockTypes/ClassicTemplate.php](../../../../src/BlockTypes/ClassicTemplate.php)

---

## woocommerce_applied_coupon


Fires after a coupon has been applied to the cart.

```php
do_action( 'woocommerce_applied_coupon', string $coupon_code )
```


**Note: Matches action name in WooCommerce core.**

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $coupon_code | string | The coupon code that was applied. |

### Source


 - [StoreApi/Utilities/CartController.php](../../../../src/StoreApi/Utilities/CartController.php)

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


 - [BlockTypes/ClassicTemplate.php](../../../../src/BlockTypes/ClassicTemplate.php)

---

## woocommerce_before_main_content


Hook: woocommerce_before_main_content

```php
do_action( 'woocommerce_before_main_content' )
```

### Description

<p>Called before rendering the main content for a product.</p>

### See


 - woocommerce_output_content_wrapper() - Outputs opening DIV for the content (priority 10)
 - woocommerce_breadcrumb() - Outputs breadcrumb trail to the current product (priority 20)
 - WC_Structured_Data::generate_website_data() - Outputs schema markup (priority 30)

### Source


 - [BlockTypes/ClassicTemplate.php](../../../../src/BlockTypes/ClassicTemplate.php)
 - [BlockTypes/ClassicTemplate.php](../../../../src/BlockTypes/ClassicTemplate.php)

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


 - [BlockTypes/ClassicTemplate.php](../../../../src/BlockTypes/ClassicTemplate.php)

---

## woocommerce_blocks_cart_enqueue_data


Fires after cart block data is registered.

```php
do_action( 'woocommerce_blocks_cart_enqueue_data' )
```

### Source


 - [BlockTypes/MiniCart.php](../../../../src/BlockTypes/MiniCart.php)
 - [BlockTypes/Cart.php](../../../../src/BlockTypes/Cart.php)

---

## woocommerce_blocks_checkout_enqueue_data


Fires after checkout block data is registered.

```php
do_action( 'woocommerce_blocks_checkout_enqueue_data' )
```

### Source


 - [BlockTypes/Checkout.php](../../../../src/BlockTypes/Checkout.php)

---

## woocommerce_blocks_enqueue_cart_block_scripts_after


Fires after cart block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after' )
```

### Source


 - [BlockTypes/Cart.php](../../../../src/BlockTypes/Cart.php)

---

## woocommerce_blocks_enqueue_cart_block_scripts_before


Fires before cart block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_cart_block_scripts_before' )
```

### Source


 - [BlockTypes/Cart.php](../../../../src/BlockTypes/Cart.php)

---

## woocommerce_blocks_enqueue_checkout_block_scripts_after


Fires after checkout block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after' )
```

### Source


 - [BlockTypes/Checkout.php](../../../../src/BlockTypes/Checkout.php)

---

## woocommerce_blocks_enqueue_checkout_block_scripts_before


Fires before checkout block scripts are enqueued.

```php
do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before' )
```

### Source


 - [BlockTypes/Checkout.php](../../../../src/BlockTypes/Checkout.php)

---

## woocommerce_blocks_loaded


Fires when the woocommerce blocks are loaded and ready to use.

```php
do_action( 'woocommerce_blocks_loaded' )
```

### Description

<p>This hook is intended to be used as a safe event hook for when the plugin has been loaded, and all dependency requirements have been met.</p> <p>To ensure blocks are initialized, you must use the <code>woocommerce_blocks_loaded</code> hook instead of the <code>plugins_loaded</code> hook. This is because the functions hooked into plugins_loaded on the same priority load in an inconsistent and unpredictable manner.</p>

### Source


 - [Domain/Bootstrap.php](../../../../src/Domain/Bootstrap.php)

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


 - [Integrations/IntegrationRegistry.php](../../../../src/Integrations/IntegrationRegistry.php)

---

## ~~woocommerce_check_cart_items~~


Fires when cart items are being validated.

```php
do_action( 'woocommerce_check_cart_items' )
```


**Deprecated: This hook is deprecated and will be removed**


**Note: Matches action name in WooCommerce core.**

### Description

<p>Allow 3rd parties to validate cart items. This is a legacy hook from Woo core. This filter will be deprecated because it encourages usage of wc_add_notice. For the API we need to capture notices and convert to wp errors instead.</p>

### Source


 - [StoreApi/Utilities/CartController.php](../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_created_customer


Fires after a customer account has been registered.

```php
do_action( 'woocommerce_created_customer', integer $customer_id, array $new_customer_data, string $password_generated )
```


**Note: Matches filter name in WooCommerce core.**

### Description

<p>This hook fires after customer accounts are created and passes the customer data.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $customer_id | integer | New customer (user) ID. |
| $new_customer_data | array | Array of customer (user) data. |
| $password_generated | string | The generated password for the account. |

### Source


 - [StoreApi/Routes/V1/Checkout.php](../../../../src/StoreApi/Routes/V1/Checkout.php)

---

## woocommerce_no_products_found


Hook: woocommerce_no_products_found.

```php
do_action( 'woocommerce_no_products_found' )
```

### See


 - wc_no_products_found() - Default no products found content (priority 10)

### Source


 - [BlockTypes/ClassicTemplate.php](../../../../src/BlockTypes/ClassicTemplate.php)

---

## woocommerce_register_post


Fires before a customer account is registered.

```php
do_action( 'woocommerce_register_post', string $username, string $user_email, \WP_Error $errors )
```


**Note: Matches filter name in WooCommerce core.**

### Description

<p>This hook fires before customer accounts are created and passes the form data (username, email) and an array of errors.</p> <p>This could be used to add extra validation logic and append errors to the array.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $username | string | Customer username. |
| $user_email | string | Customer email address. |
| $errors | \WP_Error | Error object. |

### Source


 - [StoreApi/Routes/V1/Checkout.php](../../../../src/StoreApi/Routes/V1/Checkout.php)

---

## woocommerce_shop_loop


Hook: woocommerce_shop_loop.

```php
do_action( 'woocommerce_shop_loop' )
```

### Source


 - [BlockTypes/ClassicTemplate.php](../../../../src/BlockTypes/ClassicTemplate.php)

---

## woocommerce_store_api_cart_errors


Fires an action to validate the cart.

```php
do_action( 'woocommerce_store_api_cart_errors', \WP_Error $errors, \WC_Cart $cart )
```

### Description

<p>Functions hooking into this should add custom errors using the provided WP_Error instance.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $errors | \WP_Error | WP_Error object. |
| $cart | \WC_Cart | Cart object. |

### Example

# Validate Cart

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


 - [StoreApi/Utilities/CartController.php](../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_store_api_cart_select_shipping_rate


Fires an action after a shipping method has been chosen for package(s) via the Store API.

```php
do_action( 'woocommerce_store_api_cart_select_shipping_rate', string|null $package_id, string $rate_id, \WP_REST_Request $request )
```

### Description

<p>This allows extensions to perform addition actions after a shipping method has been chosen, but before the cart totals are recalculated.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $package_id | string, null | The sanitized ID of the package being updated. Null if all packages are being updated. |
| $rate_id | string | The sanitized chosen rate ID for the package. |
| $request | \WP_REST_Request | Full details about the request. |

### Source


 - [StoreApi/Routes/V1/CartSelectShippingRate.php](../../../../src/StoreApi/Routes/V1/CartSelectShippingRate.php)

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


 - [StoreApi/Routes/V1/CartUpdateCustomer.php](../../../../src/StoreApi/Routes/V1/CartUpdateCustomer.php)

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


 - [StoreApi/Routes/V1/AbstractCartRoute.php](../../../../src/StoreApi/Routes/V1/AbstractCartRoute.php)

---

## woocommerce_store_api_checkout_order_processed


Fires before an order is processed by the Checkout Block/Store API.

```php
do_action( 'woocommerce_store_api_checkout_order_processed', \WC_Order $order )
```

### Description

<p>This hook informs extensions that $order has completed processing and is ready for payment.</p> <p>This is similar to existing core hook woocommerce_checkout_order_processed. We're using a new action:</p> <ul> <li>To keep the interface focused (only pass $order, not passing request data).</li> <li>This also explicitly indicates these orders are from checkout block/StoreAPI.</li> </ul>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $order | \WC_Order | Order object. |

### Example

# Checkout Order Processed

```php
// The action callback function.
function my_function_callback( $order ) {
  // Do something with the $order object.
  $order->save();
}

add_action( 'woocommerce_blocks_checkout_order_processed', 'my_function_callback', 10 );
```


### See


 - https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3238

### Source


 - [StoreApi/Routes/V1/CheckoutOrder.php](../../../../src/StoreApi/Routes/V1/CheckoutOrder.php)
 - [StoreApi/Routes/V1/Checkout.php](../../../../src/StoreApi/Routes/V1/Checkout.php)

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


 - [StoreApi/Routes/V1/CheckoutOrder.php](../../../../src/StoreApi/Routes/V1/CheckoutOrder.php)
 - [StoreApi/Routes/V1/Checkout.php](../../../../src/StoreApi/Routes/V1/Checkout.php)

---

## woocommerce_store_api_checkout_update_order_meta


Fires when the Checkout Block/Store API updates an order's meta data.

```php
do_action( 'woocommerce_store_api_checkout_update_order_meta', \WC_Order $order )
```

### Description

<p>This hook gives extensions the chance to add or update meta data on the $order. Throwing an exception from a callback attached to this action will make the Checkout Block render in a warning state, effectively preventing checkout.</p> <p>This is similar to existing core hook woocommerce_checkout_update_order_meta. We're using a new action:</p> <ul> <li>To keep the interface focused (only pass $order, not passing request data).</li> <li>This also explicitly indicates these orders are from checkout block/StoreAPI.</li> </ul>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $order | \WC_Order | Order object. |

### See


 - https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3686

### Source


 - [StoreApi/Routes/V1/Checkout.php](../../../../src/StoreApi/Routes/V1/Checkout.php)

---

## woocommerce_store_api_rate_limit_exceeded


Fires when the rate limit is exceeded.

```php
do_action( 'woocommerce_store_api_rate_limit_exceeded', string $ip_address )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $ip_address | string | The IP address of the request. |

### Source


 - [StoreApi/Authentication.php](../../../../src/StoreApi/Authentication.php)

---

## woocommerce_store_api_validate_add_to_cart


Fires during validation when adding an item to the cart via the Store API.

```php
do_action( 'woocommerce_store_api_validate_add_to_cart', \WC_Product $product, array $request )
```

### Description

<p>Fire action to validate add to cart. Functions hooking into this should throw an \Exception to prevent add to cart from happening.</p>

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $product | \WC_Product | Product object being added to the cart. |
| $request | array | Add to cart request params including id, quantity, and variation attributes. |

### Source


 - [StoreApi/Utilities/CartController.php](../../../../src/StoreApi/Utilities/CartController.php)

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


 - [StoreApi/Utilities/CartController.php](../../../../src/StoreApi/Utilities/CartController.php)

---

## woocommerce_{$product->get_type()}_add_to_cart


Trigger the single product add to cart action for each product type.

```php
do_action( 'woocommerce_{$product->get_type()}_add_to_cart' )
```

### Source


 - [BlockTypes/AddToCartForm.php](../../../../src/BlockTypes/AddToCartForm.php)

---

## {$hook}


Action to render the content of a hook.

```php
do_action( '{$hook}' )
```

### Source


 - [Templates/AbstractTemplateCompatibility.php](../../../../src/Templates/AbstractTemplateCompatibility.php)

---
<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/hooks/actions.md)

<!-- /FEEDBACK -->

