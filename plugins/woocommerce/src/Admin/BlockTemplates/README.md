# BlockTemplates

The `Automattic\WooCommerce\Admin\BlockTemplates` namespace contains interfaces for working with block templates.

## Usage

Objects that implement the interfaces and fire the hooks in this namespace are instantiated using more specific APIs,
such as:

-  [Product Editor Templates](../Features/ProductBlockEditor/ProductTemplates/README.md)

Please see the documentation for those APIs for more information on how to do this.

Note: In order to use these interface type definitions, you will need to import them. For example to import the `BlockInterface`:

```php
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
```

## Hooks

### `woocommerce_block_template_area_{template_area}_after_add_block_{block_id}`

Fires after a specific block is added to any template in a specific area.

The dynamic portion of the hook name, `$template_area`, refers to the area of the template the block was added to.

The dynamic portion of the hook name, `$block_id`, refers to the ID of the block that was added.

#### Parameters

##### `BlockInterface $block`

The block that was added.

### `woocommerce_block_template_after_add_block`

Fires after a block is added to a template.

Unless you need to perform an action after any block is added to any template, you should use the more specific `woocommerce_block_template_area_{template_area}_after_add_block_{block_id}` hook instead for better performance.

#### Parameters

##### `BlockInterface $block`

The block that was added.

### `woocommerce_block_template_area_{template_area}_after_remove_block_{block_id}`

Fires after a specific block is removed from any template in a specific area.

The dynamic portion of the hook name, `$template_area`, refers to the area of the template the block was removed from.

The dynamic portion of the hook name, `$block_id`, refers to the ID of the block that was removed.

#### Parameters

##### `BlockInterface $block`

The block that was removed.

### `woocommerce_block_template_after_remove_block`

Fires after a block is removed from a template.

Unless you need to perform an action after any block is removed from any template, you should use the more specific `woocommerce_block_template_area_{template_area}_after_remove_block_{block_id}` hook instead for better performance.

#### Parameters

##### `BlockInterface $block`

The block that was removed.

## Interfaces

### BlockTemplateInterface

All block templates implement this interface.

#### Methods

##### `get_id(): string`

Get the template ID.

##### `get_title(): string`

Get the template title.

##### `get_description(): string`

Get the template description.

##### `get_area(): string`

Get the template area.

##### `get_block( string $block_id ): ?BlockInterface`

Get a block by ID.

##### `remove_block( string $block_id )`

Removes a block from the template.

##### `remove_blocks()`

Removes all blocks from the template.

##### `get_formatted_template(): array`

Get the formatted template.

### BlockContainerInterface

#### Methods

##### `get_block( string $block_id ): ?BlockInterface`

Get a block by ID.

##### `remove_block( string $block_id )`

Removes a block from the template.

##### `remove_blocks()`

Removes all blocks from the template.

##### `&get_root_template(): BlockTemplateInterface`

Get the root template that the block belongs to.

##### `get_formatted_template(): array`

Get the block configuration as a formatted template.

### ContainerInterface

#### Methods

##### `get_block( string $block_id ): ?BlockInterface`

Get a block by ID.

##### `remove_block( string $block_id )`

Removes a block from the container.

##### `remove_blocks()`

Removes all blocks from the container.

##### `&get_root_template(): BlockTemplateInterface`

Get the root template that the container belongs to.

##### `get_formatted_template(): array`

Get the container as a formatted template.

### BlockInterface

#### Methods

##### `get_name(): string`

Get the block name.

#### `get_id(): string`

Get the block ID.

#### `get_order(): int`

Get the block order.

#### `set_order( int $order )`

Set the block order.

##### `get_attributes(): array`

Get the block attributes as a key/value array.

##### `set_attributes( array $attributes )`

Set the block attributes as a key/value array.

##### `&get_parent(): ContainerInterface`

Get the parent container that the block belongs to.

##### `&get_root_template(): BlockTemplateInterface`

Get the root template that the block belongs to.

##### `remove()`

Removes the block from its parent. When a block is removed from its parent, it is detached from both the parent and the root template.

##### `is_detached(): bool`

Check if the block is detached from its parent or root template. A detached block is no longer a part of the template and will not be included in the formatted template.

##### `add_hide_condition( string $expression ): string`

Adds a hide condition to the block. The hide condition is a JavaScript-like expression that is evaluated at runtime on the client to determine if the block should be hidden.

See [@woocommerce/expression-evaluation](../../../../../packages/js/expression-evaluation/README.md) for more information on the expression syntax.

##### `remove_hide_condition( string $key )`

Removes a hide condition from the block, referenced by the key returned from `add_hide_condition()`.

##### `get_hide_conditions(): array`

Get the hide conditions for the block.

##### `add_disable_condition( string $expression ): string`

Adds a disable condition to the block. Similar to `add_hide_condition()`, but the block is shown as disabled instead of hidden.

##### `remove_disable_condition( string $key )`

Removes a disable condition from the block, referenced by the key returned from `add_disable_condition()`.

##### `get_disable_conditions(): array`

Get the disable conditions for the block.

##### `get_formatted_template(): array`

Get the block configuration as a formatted template.
