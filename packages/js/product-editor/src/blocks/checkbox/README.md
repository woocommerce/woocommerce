# woocommerce/product-checkbox-field

This block is used to render a checkbox field in the product editor.

_Please note that to persist a custom field in the product it also needs to be added to the WooCommerce REST API._

Here's an example on how it is used for the 'sold_individually' field in the Inventory section:

```php
$product_inventory_advanced_wrapper->add_block(
			[
				'id'         => 'product-limit-purchase',
				'blockName'  => 'woocommerce/product-checkbox-field',
				'order'      => 20,
				'attributes' => [
					'title'    => __(
						'Restrictions',
						'woocommerce'
					),
					'label'    => __(
						'Limit purchases to 1 item per order',
						'woocommerce'
					),
					'property' => 'sold_individually',
					'tooltip'  => __(
						'When checked, customers will be able to purchase only 1 item in a single order. This is particularly useful for items that have limited quantity, like art or handmade goods.',
						'woocommerce'
					),
				],
			]
		);
```

Here's how it looks on the product editor:

![Product checkbox field screenshot](checkbox.png)


