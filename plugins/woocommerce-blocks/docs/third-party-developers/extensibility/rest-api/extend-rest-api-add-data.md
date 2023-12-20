# Exposing your data in the Store API. <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [The problem](#the-problem)
-   [Solution](#solution)
-   [Basic usage](#basic-usage)
-   [Things To Consider](#things-to-consider)
    -   [ExtendSchema is a shared instance](#extendschema-is-a-shared-instance)
    -   [Errors and fatals are silence for non-admins](#errors-and-fatals-are-silence-for-non-admins)
    -   [Callbacks should always return an array](#callbacks-should-always-return-an-array)
-   [API Definition](#api-definition)
-   [Putting it all together](#putting-it-all-together)
-   [Formatting your data](#formatting-your-data)

## The problem

You want to extend the Mini-Cart, Cart and Checkout blocks, but you want to use some custom data not available on Store API or the context. You don't want to create your own endpoints or Ajax actions. You want to piggyback on the existing StoreAPI calls.

## Solution

ExtendSchema offers the possibility to add contextual custom data to Store API endpoints, like `wc/store/cart` and `wc/store/cart/items` endpoints. That data is namespaced to your plugin and protected from other plugins causing it to malfunction. The data is available on all frontend filters and slotFills for you to consume.

## Basic usage

You can use ExtendSchema by registering a couple of functions, `schema_callback` and `data_callback` on a specific endpoint namespace. ExtendSchema will call them at execution time and will pass them relevant data as well.

This example below uses the Cart endpoint, [see passed parameters.](./available-endpoints-to-extend.md#wcstorecart)

**Note: Make sure to read the "Things to consider" section below.**

```php
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

add_action('woocommerce_blocks_loaded', function() {
 woocommerce_store_api_register_endpoint_data(
	array(
		'endpoint' => CartSchema::IDENTIFIER,
		'namespace' => 'plugin_namespace',
		'data_callback' => 'my_data_callback',
		'schema_callback' => 'my_schema_callback',
		'schema_type' => ARRAY_A,
		)
	);
});


function my_data_callback() {
	return [
		'custom-key' => 'custom-value';
	]
}

function my_schema_callback() {
	return [
		'custom-key' => [
			'description' => __( 'My custom data', 'plugin-namespace' ),
			'type' => 'string'
			'readonly' => true,
		]
	]
}
```

Data callback and Schema callback can also receive parameters:

```php

function my_cart_item_callback( $cart_item ) {
$product = $cart_item['data'];
	if ( is_my_custom_product_type( $product ) ) {
		$custom_value = get_custom_value( $product );
		return [
			'custom-key' => $custom_value;
		]
	}
}

```

## Things To Consider

### ExtendSchema is a shared instance

The ExtendSchema is stored as a shared instance between the API and consumers (third-party developers). So you shouldn't initiate the class yourself with `new ExtendSchema` because it would not work. Instead, you should always use the shared instance from the StoreApi dependency injection container like this.

```php
$extend = StoreApi::container()->get( ExtendSchema::class );
```

Also note that the dependency injection container is not available until after the `woocommerce_blocks_loaded` action has been fired, so you should hook your file that action:

```php
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;

add_action( 'woocommerce_blocks_loaded', function() {
	$extend = StoreApi::container()->get( ExtendSchema::class );
	// my logic.
});
```

Or use the global helper functions:

-   `woocommerce_store_api_register_endpoint_data( $args )`
-   `woocommerce_store_api_register_update_callback( $args )`
-   `woocommerce_store_api_register_payment_requirements( $args )`
-   `woocommerce_store_api_get_formatter( $name )`

### Errors and fatals are silence for non-admins

If your callback functions `data_callback` and `schema_callback` throw an exception or an error, or you passed the incorrect type of parameter to `register_endpoint_data`; that error would be caught and logged into WooCommerce error logs. If the current user is a shop manager or an admin, and has WP_DEBUG enabled, the error would be surfaced to the frontend.

### Callbacks should always return an array

To reduce the chances of breaking your client code or passing the wrong type, and also to keep a consistent REST API response, callbacks like `data_callback` and `schema_callback` should always return an array, even if it was empty.

## API Definition

-   `ExtendSchema::register_endpoint_data`: Used to register data to a custom endpoint. It takes an array of arguments:

| Attribute         | Type     |         Required         | Description                                                                                                                                          |
| :---------------- | :------- | :----------------------: | :--------------------------------------------------------------------------------------------------------------------------------------------------- |
| `endpoint`        | string   |           Yes            | The endpoint you're trying to extend. It is suggested that you use the `::IDENTIFIER` available on the route Schema class to avoid typos.            |
| `namespace`       | string   |           Yes            | Your plugin namespace, the data will be available under this namespace in the StoreAPI response.                                                     |
| `data_callback`   | callback |           Yes            | A callback that returns an array with your data.                                                                                                     |
| `schema_callback` | callback |           Yes            | A callback that returns the shape of your data.                                                                                                      |
| `schema_type`     | string   | No (default: `ARRAY_A` ) | The type of your data. If you're adding an object (key => values), it should be `ARRAY_A`. If you're adding a list of items, it should be `ARRAY_N`. |

## Putting it all together

This is a complete example that shows how you can register contextual WooCommerce Subscriptions data in each cart item (simplified). This example uses [Formatters](./extend-rest-api-formatters.md), utility classes that allow you to format values so that they are compatible with the StoreAPI.

```php
<?php
/**
 * WooCommerce Subscriptions Extend Store API.
 *
 * A class to extend the store public API with subscription related data
 * for each subscription item
 *
 * @package WooCommerce Subscriptions
 */
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

add_action( 'woocommerce_blocks_loaded', function() {
	$extend = StoreApi::container()->get( ExtendSchema::class );
	WC_Subscriptions_Extend_Store_Endpoint::init( $extend );
});

class WC_Subscriptions_Extend_Store_Endpoint {
	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendSchema
	 */
	private static $extend;

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'subscriptions';

	/**
	 * Bootstraps the class and hooks required data.
	 *
	 * @param ExtendSchema $extend_rest_api An instance of the ExtendSchema class.
	 *
	 * @since 3.1.0
	 */
	public static function init( ExtendSchema $extend_rest_api ) {
		self::$extend = $extend_rest_api;
		self::extend_store();
	}

	/**
	 * Registers the actual data into each endpoint.
	 */
	public static function extend_store() {

		// Register into `cart/items`
		self::$extend->register_endpoint_data(
			array(
				'endpoint'        => CartItemSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( 'WC_Subscriptions_Extend_Store_Endpoint', 'extend_cart_item_data' ),
				'schema_callback' => array( 'WC_Subscriptions_Extend_Store_Endpoint', 'extend_cart_item_schema' ),
				'schema_type'       => ARRAY_A,
			)
		);
	}

	/**
	 * Register subscription product data into cart/items endpoint.
	 *
	 * @param array $cart_item Current cart item data.
	 *
	 * @return array $item_data Registered data or empty array if condition is not satisfied.
	 */
	public static function extend_cart_item_data( $cart_item ) {
		$product   = $cart_item['data'];
		$item_data = array(
			'billing_period'      => null,
			'billing_interval'    => null,
			'subscription_length' => null,
			'trial_length'        => null,
			'trial_period'        => null,
			'sign_up_fees'        => null,
			'sign_up_fees_tax'    => null,

		);

		if ( in_array( $product->get_type(), array( 'subscription', 'subscription_variation' ), true ) ) {
			$item_data = array_merge(
				array(
					'billing_period'      => WC_Subscriptions_Product::get_period( $product ),
					'billing_interval'    => (int) WC_Subscriptions_Product::get_interval( $product ),
					'subscription_length' => (int) WC_Subscriptions_Product::get_length( $product ),
					'trial_length'        => (int) WC_Subscriptions_Product::get_trial_length( $product ),
					'trial_period'        => WC_Subscriptions_Product::get_trial_period( $product ),
				),
				self::format_sign_up_fees( $product )
			);
		}

		return $item_data;
	}

	/**
	 * Register subscription product schema into cart/items endpoint.
	 *
	 * @return array Registered schema.
	 */
	public static function extend_cart_item_schema() {
		return array(
			'billing_period'      => array(
				'description' => __( 'Billing period for the subscription.', 'woocommerce-subscriptions' ),
				'type'        => array( 'string', 'null' ),
				'enum'        => array_keys( wcs_get_subscription_period_strings() ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'billing_interval'    => array(
				'description' => __( 'The number of billing periods between subscription renewals.', 'woocommerce-subscriptions' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'subscription_length' => array(
				'description' => __( 'Subscription Product length.', 'woocommerce-subscriptions' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'trial_period'        => array(
				'description' => __( 'Subscription Product trial period.', 'woocommerce-subscriptions' ),
				'type'        => array( 'string', 'null' ),
				'enum'        => array_keys( wcs_get_subscription_period_strings() ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'trial_length'        => array(
				'description' => __( 'Subscription Product trial interval.', 'woocommerce-subscriptions' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'sign_up_fees'        => array(
				'description' => __( 'Subscription Product signup fees.', 'woocommerce-subscriptions' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'sign_up_fees_tax'    => array(
				'description' => __( 'Subscription Product signup fees taxes.', 'woocommerce-subscriptions' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}


	/**
	 * Format sign-up fees.
	 *
	 * @param \WC_Product $product current product.
	 * @return array
	 */
	private static function format_sign_up_fees( $product ) {
		$fees_excluding_tax = wcs_get_price_excluding_tax(
			$product,
			array(
				'qty'   => 1,
				'price' => WC_Subscriptions_Product::get_sign_up_fee( $product ),
			)
		);

		$fees_including_tax = wcs_get_price_including_tax(
			$product,
			array(
				'qty'   => 1,
				'price' => WC_Subscriptions_Product::get_sign_up_fee( $product ),
			)
		);

		$money_formatter = self::$extend->get_formatter( 'money' );

		return array(
			'sign_up_fees'     => $money_formatter->format(
				$fees_excluding_tax
			),
			'sign_up_fees_tax' => $money_formatter->format(
				$fees_including_tax
				- $fees_excluding_tax
			),

		);
	}
}
```

## Formatting your data

You may wish to use our pre-existing Formatters to ensure your data is passed through the Store API in the correct format. More information on the Formatters can be found in the [StoreApi Formatters documentation](./extend-rest-api-formatters.md).

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/rest-api/extend-rest-api-add-data.md)

<!-- /FEEDBACK -->

