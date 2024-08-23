---
post_title: Advanced settings and customization for legacy Local Pickup
tags: code-snippet
current wccom url: https://woocommerce.com/document/local-pickup/#advanced-settings-customization
note: Docs links out to Skyverge's site for howto add a custom email - do we have our own alternative?
---

## Disable local taxes when using local pickup

Local Pickup calculates taxes based on your store's location (address) by default, and not the customer's address. Add this snippet at the end of your theme's `functions.php` to use your standard tax configuration instead:

```php
add_filter( 'woocommerce_apply_base_tax_for_local_pickup', '__return_false' );
```

Regular taxes is then used when local pickup is selected, instead of store-location-based taxes. 

## Changing the location for local taxes

To charge local taxes based on the postcode and city of the local pickup location, you need to define the shop's base city and post code using this example code:

```php
add_filter( 'woocommerce_countries_base_postcode', create_function( '', 'return "80903";' ) );
add_filter( 'woocommerce_countries_base_city', create_function( '', 'return "COLORADO SPRINGS";' ) );
```

Update `80903` to reflect your preferred postcode/zip, and `COLORADO SPRINGS` with your preferred town or city.

## Custom emails for local pickup

_Shipping Address_ is not displayed on the admin order emails when Local Pickup is used as the shipping method.

Since all core shipping options use the standard order flow, customers receive the same order confirmation email whether they select local pickup or any other shipping option. 
Use this guide to create custom emails for local pickup if you'd like to send a separate email for local pickup orders: [How to Add a Custom WooCommerce Email](https://www.skyverge.com/blog/how-to-add-a-custom-woocommerce-email/).

