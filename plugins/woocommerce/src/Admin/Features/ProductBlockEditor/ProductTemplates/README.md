# ProductTemplates

The `Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates` namespace contains interfaces for interacting with product editor templates, which are used to define the structure of the product editor form.

General interfaces for interacting with block templates are located in the
[`Automattic\WooCommerce\Admin\BlockTemplates`](../../../BlockTemplates/README.md) namespace.

## Usage

For more information on how to extend the product editor, please see the [Product Editor Development Handbook](../../../../../../../docs/product-editor-development/README.md).

### Adding a new group to product editor templates after an existing group

```php
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;

function YOUR_PREFIX_add_group( BlockInterface $general_group ) {
  $parent = $general_group->get_parent();

  $parent->add_group(
    [
      'id'         => 'YOUR-PREFIX-group',
      'order'      => $general_group->get_order() + 5,
      'attributes' => [
        'title' => __( 'My Group', 'YOUR-TEXT-DOMAIN' ),
      ],
    ]
  );
}

add_action( 'woocommerce_block_template_area_product-form_after_add_block_general', 'YOUR_PREFIX_add_group' );
```

### Adding a new block to product editor templates after an existing block

```php
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;

function YOUR_PREFIX_add_block( BlockInterface $product_name_block ) {
  $parent = $product_name_block->get_parent();

  $parent->add_block(
    [
      'id'         => 'YOUR-PREFIX-block',
      'blockName'  => 'woocommerce/product-text-field',
      'order'      => $product_name_block->get_order() + 5,
      'attributes' => [
        'label' => __( 'My Block', 'YOUR-TEXT-DOMAIN' ),
      ],
    ]
  );
}

add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-name', 'YOUR_PREFIX_add_block' );
```

### Removing a block from product editor templates

```php
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;

function YOUR_PREFIX_remove_block( BlockInterface $sale_price_block ) {
  $sale_price_block->remove();
}

add_action( 'woocommerce_block_template_area_product-form_after_remove_block_product-sale-price', 'YOUR_PREFIX_remove_block' );
```

### Conditionally hiding a block in product editor templates

```php
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;

// hide sale price block if regular_price is less than 10
function YOUR_PREFIX_hide_block( BlockInterface $sale_price_block ) {
  $sale_price_block->add_hide_condition( 'editedProduct.regular_price < 10' );
}

add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-sale-price', 'YOUR_PREFIX_hide_block' );
```

### Conditionally disabling a block in product editor templates

```php
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;

// disable sale price block if regular_price is not set
function YOUR_PREFIX_hide_block( BlockInterface $sale_price_block ) {
  $sale_price_block->add_disable_condition( '!editedProduct.regular_price' );
}

add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-sale-price', 'YOUR_PREFIX_hide_block' );
```



## Interfaces

### GroupInterface

Groups are the top-level organizational structure for product editor templates.
They typically contain one or more sections, though they can also contain
blocks directly.

#### Methods

##### `add_section( array $block_config ): SectionInterface`

Add a new section to the group.

##### `add_block( array $block_config ): BlockInterface`

Add a new block to the group.

### SectionInterface

Sections are the second-level organizational structure for product editor templates.
They typically contain one or more blocks, though they can also contain sub-sections
if further organization is needed.

#### Methods

##### `add_section( array $block_config ): SectionInterface`

Add a new sub-section to the section.

##### `add_block( array $block_config ): BlockInterface`

Add a new block to the section.

### ProductFormTemplateInterface

All product form templates implement this interface.
Product form templates are used to define the structure of the product editor form.
They contain groups as their top-level organizational structure.

#### Methods

##### `add_group( array $block_config ): GroupInterface`

Add a new group to the template.

##### `get_group_by_id( string $group_id ): ?GroupInterface`

Gets a group by ID. Returns null if the group does not exist.

##### `get_section_by_id( string $section_id ): ?SectionInterface`

Gets a section by ID. Returns null if the section does not exist.

##### `get_block_by_id( string $block_id ): ?BlockInterface`

Gets a block by ID. Returns null if the block does not exist.
