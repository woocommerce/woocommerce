# BlockTemplates

The `Automattic\WooCommerce\Admin\BlockTemplates` namespace contains interfaces for working with block templates.

## Usage

Instances that implement these interfaces are created using more specific APIs,
such as:

-  [TODO](TODO)

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

##### `get_formatted_template(): array`

Get the block configuration as a formatted template.
