---
post_title: Cart and Checkout - Removing checkout fields
menu_title: Removing checkout fields
tags: reference
---
<!-- markdownlint-disable MD041 -->

If you're trying to extend the new Checkout block, you might notice the previous `checkout_fields` isn't working. This is intentional as we want to offer more curated extensibility paths. With that said, one of the most common requests we get is how to disable Checkout fields for certain order types. This is not something we encourage, but we are sharing the details due to how commonly requested it is.

## Why we don't encourage removing fields

The simple reason is that Checkout is complex, and many plugins need different data, payment methods will need your address info to run fraud detection, or validate your card, tax (including core) will need your full address to calculate taxes correctly, this is something we still think about in Checkout and are looking for a universal solution. While we work on this solution, the document below will help you remove checkout fields.

## Disabling fields for a single country

Checkout fields still respect country locale, which means you can modify fields for a country via PHP and this would work fine. The following billing form has 10 fields, 7 of them are required:

![Image](https://github.com/user-attachments/assets/63d83769-c20c-4c85-aebf-da8510d1d9ae)

Let's say that in Algeria, we know for sure that postal code and city are redundant, so we can remove them:

```php
add_filter('woocommerce_get_country_locale', function( $locale ) {

	$locale['DZ']['postcode']['required'] = false;
	$locale['DZ']['postcode']['hidden'] = true;

	$locale['DZ']['city']['required'] = false;
	$locale['DZ']['city']['hidden'] = true;

	 return $locale;
});
```

With the code above, both of those fields are removed for Algeria, but not for other countries, which is a good and safe update.

![Image](https://github.com/user-attachments/assets/96d45e1a-99f0-4b91-92d1-85ee742a9705)

Keep in mind that this will remove fields for both shipping and billing. We think strongly that billing shape should match shipping shape.

## Removing every field except Country and names

We can follow up with removing all other fields except Country, removing the country would fail your order, as we need country to know which fields to remove:

```php
add_filter('woocommerce_get_country_locale', function( $locale ) {

	$locale['DZ']['address_1'] = [
		'required' => false,
		'hidden'   => true,
	];

	$locale['DZ']['postcode'] = [
		'required' => false,
		'hidden'   => true,
	];

	$locale['DZ']['city'] = [
		'required' => false,
		'hidden'   => true,
	];

	$locale['DZ']['company'] = [
		'required' => false,
		'hidden'   => true,
	];
	
	$locale['DZ']['state'] = [
		'required' => false,
		'hidden'   => true,
	];
	
	$locale['DZ']['phone'] = [
		'required' => false,
		'hidden'   => true,
	];

	return $locale;
});
```

This is the result:

![Image](https://github.com/user-attachments/assets/19c82877-3405-4762-82ce-e952746abe66)

You notice that Address Line 2 is not visible, this is because it will always be skipped if Address Line 1 is skipped.

## Removing Company and Phone

One easy way (if you have access to the website), is to just toggle off the fields from the editor:

![Image](https://github.com/user-attachments/assets/53740d32-4ccd-4d5e-b08f-91a8b8b7d055)

This would be the result:

![Image](https://github.com/user-attachments/assets/3bb8dc23-22cc-4787-8577-648081e57644)

## Removing Company and Phone via code

Unlike other fields, Phone, Company, and Address 2 state is persisted to the database via an option:

- `woocommerce_checkout_phone_field`
- `woocommerce_checkout_company_field`
- `woocommerce_checkout_address_2_field`

The value of those options can be: `required`, `optional`, or `hidden`. They manage the default state of the field. So you can edit that, but still leave agency to the merchant to set it as they see fit. You can also edit them based on locale/country.

To edit the default value:

```php
add_filter(
	'default_option_woocommerce_checkout_phone_field',
	function ( $default_value ) {
		return "required";
	},
	10,
	1
);
```

If you want to change the value depending on locale (overriding the merchant value for that country), you can use the examples above.

Till now, all changes are applied to Algeria only, customers switching would see the correct fields for each country:

![Image](https://github.com/user-attachments/assets/3b8cb49a-1c95-4fab-8aaa-26b14ce22aad)

## Applying changes to all countries you sell to

As with the above, this is not something we recommend unless you sell to a very controlled set of countries and tested this with each country. Some payment gateways would only enable fraud detection on production mode and not test mode, and your fields that passed test mode fails in production mode, but here it is regardless:

```php
add_filter('woocommerce_get_country_locale', function( $locale ) {
	foreach ( $locale as $key => $value ) {
		$locale[ $key ]['address_1'] = [
			'required' => false,
			'hidden'   => true,
		];

		$locale[ $key ]['postcode'] = [
			'required' => false,
			'hidden'   => true,
		];

		$locale[ $key ]['city'] = [
			'required' => false,
			'hidden'   => true,
		];

		$locale[ $key ]['state'] = [
			'required' => false,
			'hidden'   => true,
		];
	}

	return $locale;
});
```

The above code would loop over all counties you sell and disable the fields there.

## Limiting changes to virtual carts only

We can choose to remove fields for virtual cart (ones that only require billing and no shipping), this can be done with some extra checks:

```php
add_filter('woocommerce_get_country_locale', function( $locale ) {
	$cart = wc()->cart;

	// Only remove fields if we're operating on a cart.
	if ( ! $cart ) {
		return $locale;
	}

	// Only remove fields if we're dealing with a virtual cart.
	if ( $cart->needs_shipping() ) {
		return $locale;
	}
  // Perform the rest of the logic below...
```

## Editing the address card text

For future visits, Checkout will show an address card for saved addresses, this isn't working constantly right now for billing, but once it works, it will show up like this:

![Image](https://github.com/user-attachments/assets/ab56c4ca-39ba-47ab-83d1-1ce6dfefc0c3)

We can edit that using PHP, because the value is coming from `get_address_formats` function, which passes through the `woocommerce_localisation_address_formats` filter.

We can use the following code:

```php
add_filter( 'woocommerce_localisation_address_formats', function( $formats ) {
	foreach ( $formats as $key => $format ) {
		$formats[ $key ] = "{first_name} {last_name}\n{country}";
		// You can also use `{name}` instead of first name and last name.
	}

	return $formats;
} );
```

![Image](https://github.com/user-attachments/assets/2f87e168-896f-44b3-8c4f-63cc2e159d03)

A note that you need to pass the string in double quotes for it to work; otherwise the line breaks won't be recognized.
