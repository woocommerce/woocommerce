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
	 * Key for the block hide conditions in the block configuration.
	 */
	public const HIDE_CONDITIONS_KEY = 'hideConditions';

	/**
	 * Key for the block disable conditions in the block configuration.
	 */
	public const DISABLE_CONDITIONS_KEY = 'disableConditions';

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
	 * Set a block attribute value without replacing the entire attributes object.
	 *
	 * @param string $key The attribute key.
	 * @param mixed  $value The attribute value.
	 */
	public function set_attribute( string $key, $value );

	/**
	 * Get the parent container that the block belongs to.
	 */
	public function &get_parent(): ContainerInterface;

	/**
	 * Get the root template that the block belongs to.
	 */
	public function &get_root_template(): BlockTemplateInterface;

	/**
	 * Remove the block from its parent.
	 */
	public function remove();

	/**
	 * Check if the block is detached from its parent or root template.
	 *
	 * @return bool True if the block is detached from its parent or root template.
	 */
	public function is_detached(): bool;

	/**
	 * Add a hide condition to the block.
	 *
	 * The hide condition is a JavaScript-like expression that will be evaluated on the client to determine if the block should be hidden.
	 * See [@woocommerce/expression-evaluation](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/expression-evaluation/README.md) for more details.
	 *
	 * @param string $expression An expression, which if true, will hide the block.
	 * @return string The key of the hide condition, which can be used to remove the hide condition.
	 */
	public function add_hide_condition( string $expression ): string;

	/**
	 * Remove a hide condition from the block.
	 *
	 * @param string $key The key of the hide condition to remove.
	 */
	public function remove_hide_condition( string $key );

	/**
	 * Get the hide conditions of the block.
	 */
	public function get_hide_conditions(): array;

	/**
	 * Add a disable condition to the block.
	 *
	 * The disable condition is a JavaScript-like expression that will be evaluated on the client to determine if the block should be disabled.
	 * See [@woocommerce/expression-evaluation](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/expression-evaluation/README.md) for more details.
	 *
	 * @param string $expression An expression, which if true, will disable the block.
	 * @return string The key of the disable condition, which can be used to remove the disable condition.
	 */
	public function add_disable_condition( string $expression ): string;

	/**
	 * Remove a disable condition from the block.
	 *
	 * @param string $key The key of the disable condition to remove.
	 */
	public function remove_disable_condition( string $key );

	/**
	 * Get the disable conditions of the block.
	 */
	public function get_disable_conditions(): array;

	/**
	 * Get the block configuration as a formatted template.
	 *
	 * @return array The block configuration as a formatted template.
	 */
	public function get_formatted_template(): array;
}
