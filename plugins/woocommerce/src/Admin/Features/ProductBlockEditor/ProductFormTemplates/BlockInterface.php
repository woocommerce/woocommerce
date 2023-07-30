<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates;

/**
 * Interface for block configuration used to specify blocks in BlockTemplate.
 *
 * @package Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates
 */
interface BlockInterface extends BlockContainerInterface {
	/**
	 * Key for the block name in the block configuration.
	 */
	public const NAME_KEY = 'blockName';

	/**
	 * Key for the block ID in the block configuration.
	 */
	public const ID_KEY = 'id';

	/**
	 * Key for the internal order in the block configuration.
	 */
	public const ORDER_KEY = 'order';

	/**
	 * Key for the block attributes in the block configuration.
	 */
	public const ATTRIBUTES_KEY = 'attributes';

	/**
	 * Get the block name.
	 */
	public function get_name(): string;

	/**
	 * Get the block ID.
	 */
	public function get_id(): string;

	/**
	 * Get the block order.
	 */
	public function get_order(): int;

	/**
	 * Set the block order.
	 *
	 * @param int $order The block order.
	 */
	public function set_order( int $order );

	/**
	 * Get the block attributes.
	 */
	public function get_attributes(): array;

	/**
	 * Set the block attributes.
	 *
	 * @param array $attributes The block attributes.
	 */
	public function set_attributes( array $attributes );

	/**
	 * Get the block template that this block belongs to.
	 */
	public function &get_root_template(): BlockTemplateInterface;

	/**
	 * Get the parent block container.
	 */
	public function &get_parent(): BlockContainerInterface;

	/**
	 * Get the block configuration as a simple array.
	 *
	 * @return array The block configuration as a simple array.
	 */
	public function get_as_simple_array(): array;
}
