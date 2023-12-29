# Available endpoints to extend with ExtendSchema <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Products](#products)
    -   [Use Cases](#use-cases)
    -   [Example](#example)
-   [Cart](#cart)
    -   [Use Cases](#use-cases-1)
    -   [Example](#example-1)
-   [Cart Items](#cart-items)
    -   [Use Cases](#use-cases-2)
    -   [Example](#example-2)
-   [Checkout](#checkout)
    -   [Use Cases](#use-cases-3)
    -   [Example](#example-3)

Some endpoints of the Store API are extensible via a class called `ExtendSchema`. This allows you to customise the data (including the schema) that is returned by the Store API so that it can be consumed by your application or plugin.

For more information about extending the Store API, you may also be interested in:

-   [How to add your data to Store API using `ExtendSchema`](./extend-rest-api-add-data.md)
-   [How to add a new endpoint](../../../internal-developers/rest-api/extend-rest-api-new-endpoint.md)

Below is a list of available endpoints that you can extend using `ExtendSchema`, as well as some example use-cases.

## Products

The main `wc/store/products` endpoint is extensible via ExtendSchema. The data is available via the `extensions` key for each `product` in the response array.

This endpoint can be extended using the `ProductSchema::IDENTIFIER` key. For this endpoint, your `data_callback` callback function is passed `$product` as a parameter. Your `schema_callback` function is passed no additional parameters; all products should share the same schema.

### Use Cases

This endpoint is useful for adding additional data about individual products. This could be some meta data, additional pricing, or anything else to support custom blocks or components on the products page.

### Example

```php
woocommerce_store_api_register_endpoint_data(
	array(
		'endpoint'        => ProductSchema::IDENTIFIER,
		'namespace'       => 'my_plugin_namespace',
		'data_callback'   => function( $product ) {
			return array(
				'my_meta_data' => get_post_meta( $product->get_id(), 'my_meta_data', true ),
			);
		},
		'schema_callback' => function() {
			return array(
				'properties' => array(
					'my_meta_data' => array(
						'type' => 'string',
					),
				),
			);
		},
		'schema_type'     => ARRAY_A,
	)
);
```

## Cart

The main `wc/store/cart` endpoint is extensible via ExtendSchema. The data is available via the `extensions` key in the response.

This endpoint can be extended using the `CartSchema::IDENTIFIER` key. For this endpoint, your `data_callback` and `schema_callback` functions are passed no additional parameters.

### Use Cases

This endpoint is useful for adding additional data to the cart page, for example, extra data about the cart items, or anything else needed to support custom blocks displayed on the cart page.

### Example

```php
woocommerce_store_api_register_endpoint_data(
	array(
		'endpoint'        => CartSchema::IDENTIFIER,
		'namespace'       => 'my_plugin_namespace',
		'data_callback'   => function() {
			return array(
				'foo' => 'bar',
			);
		},
		'schema_callback' => function() {
			return array(
				'properties' => array(
					'foo' => array(
						'type' => 'string',
					),
				),
			);
		},
		'schema_type'     => ARRAY_A,
	)
);
```

## Cart Items

The `wc/store/cart/items` endpoint, which is also available on `wc/store/cart` inside the `items` key. The data would be available inside each item of the `items` array.

This endpoint can be extended using the `CartItemSchema::IDENTIFIER` key. For this endpoint, your `data_callback` callback function is passed `$cart_item` as a parameter. Your `schema_callback` function is passed no additional parameters; all cart items should share the same schema.

### Use Cases

This endpoint is useful for adding additional data about individual cart items. This could be some meta data, additional pricing, or anything else to support custom blocks or components on the cart page.

### Example

```php
woocommerce_store_api_register_endpoint_data(
	array(
		'endpoint'        => CartItemSchema::IDENTIFIER,
		'namespace'       => 'my_plugin_namespace',
		'data_callback'   => function( $cart_item ) {
			$product = $cart_item['data'];
			return array(
				'my_meta_data' => get_post_meta( $product->get_id(), 'my_meta_data', true ),
			);
		},
		'schema_callback' => function() {
			return array(
				'properties' => array(
					'my_meta_data' => array(
						'type' => 'string',
					),
				),
			);
		},
		'schema_type'     => ARRAY_A,
	)
);
```

## Checkout

The `wc/store/checkout` endpoint is extensible via ExtendSchema. Additional data is available via the `extensions` key in the response.

This endpoint can be extended using the `CheckoutSchema::IDENTIFIER` key. For this endpoint, your `data_callback` and `schema_callback` functions are passed no additional parameters.

### Use Cases

This endpoint is useful for adding additional data to the checkout page, such as a custom payment method which requires additional data to be collected from the user or server.

⚠ **Important: Do **not** reveal any sensitive data in this endpoint, as it is publicly accessible. This includes private keys for payment services.**

### Example

```php
woocommerce_store_api_register_endpoint_data(
	array(
		'endpoint'        => CheckoutSchema::IDENTIFIER,
		'namespace'       => 'my_plugin_namespace',
		'data_callback'   => function() {
			return array(
				'foo' => 'bar',
			);
		},
		'schema_callback' => function() {
			return array(
				'properties' => array(
					'foo' => array(
						'type' => 'string',
					),
				),
			);
		},
		'schema_type'     => ARRAY_A,
	)
);
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/rest-api/available-endpoints-to-extend.md)

<!-- /FEEDBACK -->
