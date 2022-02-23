<!-- DO NOT UPDATE THIS DOC DIRECTLY -->

<!-- Use `npm run build:docs` to automatically build hook documentation -->

# Actions

## Table of Contents


 - [woocommerce_add_to_cart](#woocommerce_add_to_cart)
 - [woocommerce_after_main_content](#woocommerce_after_main_content)
 - [woocommerce_after_shop_loop](#woocommerce_after_shop_loop)
 - [woocommerce_applied_coupon](#woocommerce_applied_coupon)
 - [woocommerce_archive_description](#woocommerce_archive_description)
 - [woocommerce_before_main_content](#woocommerce_before_main_content)
 - [woocommerce_before_shop_loop](#woocommerce_before_shop_loop)
 - [woocommerce_blocks_cart_enqueue_data](#woocommerce_blocks_cart_enqueue_data)
 - [woocommerce_blocks_cart_update_customer_from_request](#woocommerce_blocks_cart_update_customer_from_request)
 - [woocommerce_blocks_cart_update_order_from_request](#woocommerce_blocks_cart_update_order_from_request)
 - [woocommerce_blocks_checkout_enqueue_data](#woocommerce_blocks_checkout_enqueue_data)
 - [woocommerce_blocks_checkout_order_processed](#woocommerce_blocks_checkout_order_processed)
 - [woocommerce_blocks_checkout_update_order_from_request](#woocommerce_blocks_checkout_update_order_from_request)
 - [woocommerce_blocks_checkout_update_order_meta](#woocommerce_blocks_checkout_update_order_meta)
 - [woocommerce_blocks_enqueue_cart_block_scripts_after](#woocommerce_blocks_enqueue_cart_block_scripts_after)
 - [woocommerce_blocks_enqueue_cart_block_scripts_before](#woocommerce_blocks_enqueue_cart_block_scripts_before)
 - [woocommerce_blocks_enqueue_checkout_block_scripts_after](#woocommerce_blocks_enqueue_checkout_block_scripts_after)
 - [woocommerce_blocks_enqueue_checkout_block_scripts_before](#woocommerce_blocks_enqueue_checkout_block_scripts_before)
 - [woocommerce_blocks_loaded](#woocommerce_blocks_loaded)
 - [woocommerce_blocks_{$this->registry_identifier}_registration](#woocommerce_blocks_-this--registry_identifier-_registration)
 - [woocommerce_check_cart_items](#woocommerce_check_cart_items)
 - [woocommerce_created_customer](#woocommerce_created_customer)
 - [woocommerce_no_products_found](#woocommerce_no_products_found)
 - [woocommerce_register_post](#woocommerce_register_post)
 - [woocommerce_rest_checkout_process_payment_with_context](#woocommerce_rest_checkout_process_payment_with_context)
 - [woocommerce_shop_loop](#woocommerce_shop_loop)
 - [wooocommerce_store_api_validate_add_to_cart](#wooocommerce_store_api_validate_add_to_cart)
 - [wooocommerce_store_api_validate_cart_item](#wooocommerce_store_api_validate_cart_item)

---

## woocommerce_add_to_cart


Fires when an item is added to the cart.

```php
do_action( 'woocommerce_add_to_cart', string $cart_id, integer $product_id, integer $request_quantity, integer $variation_id, array $variation, array $cart_item_data )
```

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


 - [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

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


 - [BlockTypes/LegacyTemplate.php](../src/BlockTypes/LegacyTemplate.php)
 - [BlockTypes/LegacyTemplate.php](../src/BlockTypes/LegacyTemplate.php)

---

## woocommerce_after_shop_loop


Hook: woocommerce_after_shop_loop.

```php
do_action( 'woocommerce_after_shop_loop' )
```

### See


 - woocommerce_pagination() - Renders pagination (priority 10)

### Source


 - [BlockTypes/LegacyTemplate.php](../src/BlockTypes/LegacyTemplate.php)

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


 - [StoreApi/Utilities/CartController.php](../src/StoreApi/Utilities/CartController.php)

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


 - [BlockTypes/LegacyTemplate.php](../src/BlockTypes/LegacyTemplate.php)

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


 - [BlockTypes/LegacyTemplate.php](../src/BlockTypes/LegacyTemplate.php)
 - [BlockTypes/LegacyTemplate.php](../src/BlockTypes/LegacyTemplate.php)

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


 - [BlockTypes/LegacyTemplate.php](../src/BlockTypes/LegacyTemplate.php)

---

## woocommerce_blocks_cart_enqueue_data


Fires after cart block data is registered.

```php
do_action( 'woocommerce_blocks_cart_enqueue_data' )
```

### Source


 - [BlockTypes/MiniCart.php](../src/BlockTypes/MiniCart.php)
 - [BlockTypes/Cart.php](../src/BlockTypes/Cart.php)

---

## woocommerce_blocks_cart_update_customer_from_request


Fires when the Checkout Block/Store API updates a customer from the API request data.

```php
do_action( 'woocommerce_blocks_cart_update_customer_from_request', \WC_Customer $customer, \WP_REST_Request $request )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $customer | \WC_Customer | Customer object. |
| $request | \WP_REST_Request | Full details about the request. |

### Source


 - [StoreApi/Routes/V1/CartUpdateCustomer.php](../src/StoreApi/Routes/V1/CartUpdateCustomer.php)

---

## woocommerce_blocks_cart_update_order_from_request


Fires when the order is synced with cart data from a cart route.

```php
do_action( 'woocommerce_blocks_cart_update_order_from_request', \WC_Order $draft_order, \WC_Customer $customer, \WP_REST_Request $request )
```

### Parameters

| Argument | Type | Description |
| -------- | ---- | ----------- |
| $draft_order | \WC_Order | Order object. |
| $customer | \WC_Customer | Customer object. |
| $request | \WP_REST_Request | Full details about the request. |

### Source


 - [StoreApi/Routes/V1/AbstractCartRoute.php](../src/StoreApi/Routes/V1/AbstractCartRoute.php)

---

## woocommerce_blocks_checkout_enqueue_data


Fires after checkout block data is registered.

```php
do_action( 'woocommerce_blocks_checkout_enqueue_data' )
```

### Source


 - [BlockTypes/Checkout.php](../src/BlockTypes/Checkout.php)

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
<!-- FEEDBACK -->
---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/extensibility/actions.md)
<!-- /FEEDBACK -->

