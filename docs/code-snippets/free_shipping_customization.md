---
post_title: Free Shipping Customizations
menu_title: Free shipping customizations
tags: code-snippets
current wccom url: https://woocommerce.com/document/free-shipping/#advanced-settings-customization
combined with: https://woocommerce.com/document/hide-other-shipping-methods-when-free-shipping-is-available/#use-a-plugin
---

## Free Shipping: Advanced Settings/Customization

### Overview

By default, WooCommerce shows all shipping methods that match the customer and the cart contents. This means Free Shipping also shows along with Flat Rate and other Shipping Methods. 

The functionality to hide all other methods, and only show Free Shipping, requires either custom PHP code or a plugin/extension.

### Adding code

Before adding snippets, clear your WooCommerce cache. Go to WooCommerce > System Status > Tools > WooCommerce Transients > Clear transients.

## Code Snippets

### How do I only show Free Shipping?

The following snippet will hide all other shipping methods if Free Shipping is available for the customer.

```php
/**
 * Hide other shipping rates when free shipping is available.
 *
 * @param array $rates Array of rates found for the package.
 *
 * @return array
 */
function fsc_hide_shipping_rates_when_free_is_available( $rates ) {
	// Go through each rate found.
	foreach ( $rates as $rate_id => $rate ) {
		// If Free Shipping is found, define it as the only rate and break out of the foreach.
		if ( 'free_shipping' === $rate->method_id ) {
			$rates = [ $rate_id => $rate ];
			break;
		}
	}
	return $rates;
}
add_filter( 'woocommerce_package_rates', 'fsc_hide_shipping_rates_when_free_is_available', 10, 1 );
```

### How do I only show Local Pickup and Free Shipping?

The following snippet will hide all other shipping methods but Free Shipping and Local Pickup if they are available for the customer.

```php
/**
 * If Free Shipping is available hide other rates, excluding Local Pickup.
 *
 * @param array $rates Array of rates found for the package.
 *
 * @return array
 */
function fsc_hide_shipping_rates_when_free_is_available_excluding_local( $rates ) {
	// Define arrays to hold our Free Shipping and Local Pickup methods, if found.
	$free_shipping = [];
	$local_pickup  = [];

	// Go through each rate received.
	foreach ( $rates as $rate_id => $rate ) {
		// If either method is found, add them to their respective array.
		if ( 'free_shipping' === $rate->method_id ) {
			$free_shipping[ $rate_id ] = $rate;
			continue;
		}
		if ( 'pickup_location' === $rate->method_id ) {
			$local_pickup[ $rate_id ] = $rate;
		}
	}

	// If the free_shipping array contains a method, then merge the local_pickup into it, and overwrite the rates array.
	if ( ! empty( $free_shipping ) ) {
		$rates = array_merge( $free_shipping, $local_pickup );
	}

	return $rates;
}

add_filter( 'woocommerce_package_rates', 'fsc_hide_shipping_rates_when_free_is_available_excluding_local', 10, 1 );
```

### Enabling or Disabling Free Shipping via Hooks

If you would like to know if Free Shipping is available programmatically, this is possible. WooCommerce applies a filter like the below:

```php
return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available );
```

This means you can use `add_filter()` on `woocommerce_shipping_free_shipping_is_available` and receive `true` or `false` if Free Shipping is enabled. For example, this next snippet would log if Free Shipping is available or not:

```php
/**
 * Log if Free Shipping is available or not.
 *
 * @param bool $is_available If Free Shipping is available, then `true`, `false` if not.
 *
 * @return bool
 */
function fsc_free_shipping_is_available( $is_available ) {
	if ( $is_available ) {
		error_log( 'Free shipping is available' );
	} else {
		error_log( 'Free shipping is NOT available' );
	}
	return $is_available;
}
add_filter( 'woocommerce_shipping_free_shipping_is_available', 'fsc_free_shipping_is_available', 10, 1 );
```

### Enable Shipping Methods on a per Class / Product Basis, split orders, or other scenarios?

Need more flexibility? Take a look at our [premium Shipping Method extensions](https://woocommerce.com/product-category/woocommerce-extensions/shipping-methods/).
