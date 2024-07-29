---
post_title: Displaying Custom Fields in Your Theme or Site
menu_title: Displaying custom fields in theme
tags: code-snippet
current wccom url: https://woocommerce.com/document/custom-product-fields/
---

## Displaying Custom Fields in Your Theme or Site

You can use the metadata from custom fields you add to your products to display the added information within your theme or site.

To display the custom fields for each product, you have to edit your theme’s files. Here’s an example of how you might display a custom field within the single product pages after the short description:

![image](https://github.com/woocommerce/woocommerce-developer-advocacy/assets/15178758/ed417ed8-4462-45b9-96b6-c0141afaeb2b)

```php
<?php

// Display a product custom field within single product pages after the short description 

function woocommerce_custom_field_example() {

    if ( ! is_product() ) {
        return;
    }
   
    global $product;

    if ( ! is_object( $product ) ) {
        $product = wc_get_product( get_the_ID() );
    }

    $custom_field_value = get_post_meta( $product->get_id(), 'woo_custom_field', true );
    
    if ( ! empty( $custom_field_value ) ) {
       echo '<div class="custom-field">' . esc_html( $custom_field_value ) . '</div>';
    }
}

add_action( 'woocommerce_before_add_to_cart_form', 'woocommerce_custom_field_example', 10 );
```
