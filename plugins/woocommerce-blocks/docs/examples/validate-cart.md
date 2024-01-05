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
