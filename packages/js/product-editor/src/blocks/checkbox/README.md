# woocommerce/product-checkbox-field

A reusable checkbox for the product editor.

_Please note that to persist a custom field in the product it also needs to be added to the WooCommerce REST API._

| **attribute** | **type** | **required** | **description**                                                            |
|--------------:|----------|--------------|----------------------------------------------------------------------------|
| title         | string   | no           | Header that appears above the checkbox                                     |
| label         | string   | no           | Label that appears at the side of the checkbox                             |
| property      | string   | yes          | Property in which the checkbox value is stored                             |
| tooltip       | string   | no           | Tooltip text that is shown when hovering the icon at the side of the label |

Here's an example on how it is used for the 'sold_individually' field in the Inventory section:

```php
$parent_container->add_block(
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

![Product checkbox field screenshot](https://woocommerce.files.wordpress.com/2023/09/checkbox.png)


