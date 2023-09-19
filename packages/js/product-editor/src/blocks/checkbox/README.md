# woocommerce/product-checkbox-field

This block is used to render a checkbox field in the product editor.

_Please note that to persist a custom field in the product it also needs to be added to the WooCommerce REST API._

Here's an example on how it is used for fields in the Product Catalog section:

```php
$organization_group = $this->get_group_by_id( $this::GROUP_IDS['ORGANIZATION'] );
// Product Catalog Section.
$product_catalog_section = $organization_group->add_section(
  [
    'id'         => 'product-catalog-section',
    'order'      => 10,
    'attributes' => [
      'title' => __( 'Product catalog', 'woocommerce' ),
    ],
  ]
);
$product_catalog_section->add_block(
  [
    'id'         => 'product-enable-product-reviews',
    'blockName'  => 'woocommerce/product-checkbox-field',
    'order'      => 40,
    'attributes' => [
      'label'    => __( 'Enable product reviews', 'woocommerce' ),
      'property' => 'reviews_allowed',
    ],
  ]
);
```

Here's how it looks on the product editor:

![Product checkbox field screenshot](checkbox.png)


