---
post_title: Conditional tags in WooCommerce
menu_title: Conditional tags
tags: reference
---

## What are "conditional tags"?

The conditional tags of WooCommerce and WordPress can be used in your template files to change what content is displayed based on what *conditions* the page matches. For example, you may want to display a snippet of text above the shop page. With the `is_shop()` conditional tag, you can.

Because WooCommerce uses custom post types, you can also use many of WordPress' conditional tags. See [codex.wordpress.org/Conditional_Tags](https://codex.wordpress.org/Conditional_Tags) for a list of the tags included with WordPress.

**Note**: You can only use conditional query tags after the `posts_selection` [action hook](https://codex.wordpress.org/Plugin_API/Action_Reference#Actions_Run_During_a_Typical_Request) in WordPress (the `wp` action hook is the first one through which you can use these conditionals). For themes, this means the conditional tag will never work properly if you are using it in the body of functions.php.

## Available conditional tags

All conditional tags test whether a condition is met, and then return either `TRUE` or `FALSE`. **Conditions under which tags output `TRUE` are listed below the conditional tags**.

The list below holds the main conditional tags. To see all conditional tags, visit the [WooCommerce API Docs](https://woo.com/wc-apidocs/).

### WooCommerce page

- `is_woocommerce()`  
  Returns true if on a page which uses WooCommerce templates (cart and checkout are standard pages with shortcodes and thus are not included).

### Main shop page

- `is_shop()`  
  Returns true when on the product archive page (shop).

### Product category page

- `is_product_category()`  
  Returns true when viewing a product category archive.
- `is_product_category( 'shirts' )`  
  When the product category page for the 'shirts' category is being displayed.
- `is_product_category( array( 'shirts', 'games' ) )`  
  When the product category page for the 'shirts' or 'games' category is being displayed.

### Product tag page

- `is_product_tag()`  
  Returns true when viewing a product tag archive
- `is_product_tag( 'shirts' )`  
  When the product tag page for the 'shirts' tag is being displayed.
- `is_product_tag( array( 'shirts', 'games' ) )`  
  When the product tag page for the 'shirts' or 'games' tags is being displayed.

### Single product page

- `is_product()`  
  Returns true on a single product page. Wrapper for is_singular.

### Cart page

- `is_cart()`  
  Returns true on the cart page.

### Checkout page

- `is_checkout()`  
  Returns true on the checkout page.

### Customer account pages

- `is_account_page()`  
  Returns true on the customer's account pages.

### Endpoint

- `is_wc_endpoint_url()`  
  Returns true when viewing a WooCommerce endpoint
- `is_wc_endpoint_url( 'order-pay' )`  
  When the endpoint page for order pay is being displayed.
- And so on for other endpoints...

### Ajax request

- `is_ajax()`  
  Returns true when the page is loaded via ajax.

## Working example

The example illustrates how you would display different content for different categories.

```php
if ( is_product_category() ) {

  if ( is_product_category( 'shirts' ) ) {
    echo 'Hi! Take a look at our sweet t-shirts below.';
  } elseif ( is_product_category( 'games' ) ) {
    echo 'Hi! Hungry for some gaming?';
  } else {
    echo 'Hi! Check out our products below.';
  }

}
```
