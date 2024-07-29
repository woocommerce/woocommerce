---
post_title: Code snippets for configuring special tax scenarios
menu_title: Configuring special tax scenarios
tags: code-snippet, tax
current wccom url: https://woocommerce.com/document/setting-up-taxes-in-woocommerce/configuring-specific-tax-setups-in-woocommerce/#configuring-special-tax-setups
---

# Code snippets for configuring special tax scenarios

## Scenario A: Charge the same price regardless of location and taxes

Scenario A: Charge the same price regardless of location and taxes

If a store enters product prices including taxes, but levies various location-based tax rates, the prices will appear to change depending on which tax rate is applied. In reality, the base price remains the same, but the taxes influence the total. [Follow this link for a detailed explanation](https://woocommerce.com/document/how-taxes-work-in-woocommerce/#cross-border-taxes).

Some merchants prefer to dynamically change product base prices to account for the changes in taxes and so keep the total price consistent regardless of tax rate. Enable that functionality by adding the following snippet to your child theme’s functions.php file or via a code snippet plugin.

```php
<?php

add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
```

## Scenario B: Charge tax based on the subtotal amount

The following snippet is useful in case where a store only ads taxes when the subtotal reaches a specified minimum. In the code snippet below that minimum is 110 of the store’s currency. Adjust the snippet according to your requirements. 

```php
<?php

add_filter( 'woocommerce_product_get_tax_class', 'big_apple_get_tax_class', 1, 2 );

function big_apple_get_tax_class( $tax_class, $product ) {
	if ( WC()->cart->subtotal <= 110 )
		$tax_class = 'Zero Rate';

	return $tax_class;
}
```

## Scenario C: Apply different tax rates based on the customer role

Some merchants may require different tax rates to be applied based on a customer role to accommodate for wholesale status or tax exemption.

To enable this functionality, add the following snippet to your child theme’s functions.php file or via a code snippet plugin. In this snippet, users with “administrator” capabilities will be assigned the **Zero rate tax class**. Adjust it according to your requirements.

```php
<?php
/**
 * Apply a different tax rate based on the user role.
 */
function wc_diff_rate_for_user( $tax_class, $product ) {
	if ( is_user_logged_in() && current_user_can( 'administrator' ) ) {
		$tax_class = 'Zero Rate';
	}

	return $tax_class;
}
add_filter( 'woocommerce_product_get_tax_class', 'wc_diff_rate_for_user', 1, 2 );
add_filter( 'woocommerce_product_variation_get_tax_class', 'wc_diff_rate_for_user', 1, 2 );
```

## Scenario D: Show 0 value taxes

Taxes that have 0-value are hidden by default. To show them regardless, add the following snippet to your theme’s functions.php file or via a code snippet plugins: 

```php
add_filter( 'woocommerce_order_hide_zero_taxes', '__return_false' );
```

## Scenario E: Suffixes on the main variable product

One of the tax settings for WooCommerce enables the use of suffixes to add additional information to product prices. It’s available for use with the variations of a variable product, but is disabled at the main variation level as it can impact website performance when there are many variations. 

The method responsible for the related price output can be customized via filter hooks if needed for variable products. This will require customization that can be implemented via this filter:

```php
add_filter( 'woocommerce_show_variation_price', '__return_true' );
```


