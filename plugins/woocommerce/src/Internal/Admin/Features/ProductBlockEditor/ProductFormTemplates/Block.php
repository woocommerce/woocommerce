<?php

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductFormTemplates;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockTemplateInterface;

/**
 * Block configuration used to specify blocks in BlockTemplate.
 *
 * @package Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductFormTemplates
 */
class Block implements BlockInterface {
	use BlockContainerTrait;

	/**
	 * The block name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The block ID.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The block order.
	 *
	 * @var int
	 */
	private $order = 10;

	/**
	 * The block attributes.
	 *
	 * @var array
	 */
	private $attributes = [];

	/**
	 * The block template that this block belongs to.
	 *
	 * @var BlockTemplate
	 */
	private $root_template;

	/**
	 * The parent block container.
	 *
	 * @var BlockContainerInterface
	 */
	private $parent;

	/**
	 * Block constructor.
	 *
	 * @param array                        $config The block configuration.
	 * @param BlockTemplateInterface       $root_template The block template that this block belongs to.
	 * @param BlockContainerInterface|null $parent The parent block container.
	 * @throws \ValueError If the block configuration is invalid.
	 * @throws \ValueError If the parent block container does not belong to the same template as the block.
	 */
	public function __construct( array $config, BlockTemplateInterface &$root_template, BlockContainerInterface &$parent = null ) {
		$this->root_template = $root_template;
		$this->parent        = is_null( $parent ) ? $root_template : $parent;

		if ( $this->parent->get_root_template() !== $this->root_template ) {
			throw new \ValueError( 'The parent block must belong to the same template as the block.' );
		}

		if ( ! isset( $config[ self::NAME_KEY ] ) || ! is_string( $config[ self::NAME_KEY ] ) ) {
			throw new \ValueError( 'The block name must be specified.' );
		} else {
			$this->name = $config[ self::NAME_KEY ];
		}

		if ( ! isset( $config[ self::ID_KEY ] ) ) {
			$this->id = $this->root_template->generate_block_id( $this->get_name() );
		} else {
			$this->id = $config[ self::ID_KEY ];
		}

		if ( isset( $config[ self::ORDER_KEY ] ) && ! is_int( $config[ self::ORDER_KEY ] ) ) {
			throw new \ValueError( 'The block order must be an integer.' );
		} elseif ( isset( $config[ self::ORDER_KEY ] ) ) {
			$this->order = $config[ self::ORDER_KEY ];
		}

		if ( isset( $config[ self::ATTRIBUTES_KEY ] ) && ! is_array( $config[ self::ATTRIBUTES_KEY ] ) ) {
			throw new \ValueError( 'The block attributes must be an array.' );
		} elseif ( isset( $config[ self::ATTRIBUTES_KEY ] ) ) {
			$this->attributes = $config[ self::ATTRIBUTES_KEY ];
		}
	}

	/**
	 * Get the block name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the block ID.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get the block order.
	 */
	public function get_order(): int {
		return $this->order;
	}

	/**
	 * Set the block order.
	 *
	 * @param int $order The block order.
	 */
	public function set_order( int $order ) {
		$this->order = $order;
	}

	/**
	 * Get the block attributes.
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Set the block attributes.
	 *
	 * @param array $attributes The block attributes.
	 */
	public function set_attributes( array $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Get the block template that this block belongs to.
	 */
	public function &get_root_template(): BlockTemplate {
		return $this->root_template;
	}

	/**
	 * Get the parent block container.
	 */
	public function &get_parent(): BlockContainerInterface {
		return $this->parent;
	}

	/**
	 * Get the block configuration as a simple array.
	 *
	 * @return array The block configuration as a simple array.
	 */
	public function get_as_simple_array(): array {
		$arr = [
			$this->get_name(),
			$this->get_attributes(),
		];

		$child_blocks_as_simple_array = $this->get_child_blocks_as_simple_array();
		if ( ! empty( $child_blocks_as_simple_array ) ) {
			$arr[] = $child_blocks_as_simple_array;
		}

		return $arr;
	}
}
