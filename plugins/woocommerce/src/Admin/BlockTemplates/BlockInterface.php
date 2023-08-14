<?php

namespace Automattic\WooCommerce\Admin\BlockTemplates;

/**
 * Interface for block configuration used to specify blocks in BlockTemplate.
 */
interface BlockInterface {
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
	 * Get the parent container that the block belongs to.
	 */
	public function &get_parent(): ?ContainerInterface;

	/**
	 * Get the root template that the block belongs to.
	 */
	public function &get_root_template(): BlockTemplateInterface;

	/**
	 * Get the block configuration as a formatted template.
	 *
	 * @return array The block configuration as a formatted template.
	 */
	public function get_formatted_template(): array;
}
