<?php
/**
 * Block configuration.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates;

class Block implements BlockContainerInterface {
	use BlockContainerTrait;

	/**
	 * Key for the block name in the block configuration.
	 */
	const NAME_KEY = 'blockName';

	/**
	 * Key for the block ID in the block configuration.
	 */
	const ID_KEY = 'id';

	/**
	 * Key for the internal order in the block configuration.
	 */
	const ORDER_KEY = 'order';

	/**
	 * Key for the block attributes in the block configuration.
	 */
	const ATTRIBUTES_KEY = 'attributes';

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var int
	 */
	private $order = 10;

	/**
	 * @var array
	 */
	private $attributes = [];

	/**
	 * @var BlockTemplate
	 */
	private $root_template;

	/**
	 * @var BlockContainerInterface
	 */
	private $parent;

	public function __construct( array $data, BlockTemplate &$root_template, BlockContainerInterface &$parent = null ) {
		$this->root_template = $root_template;
		$this->parent        = is_null( $parent ) ? $root_template : $parent;

		if ( $this->parent->get_root_template() !== $this->root_template ) {
			throw new \ValueError( 'The parent block must belong to the same template as the block.' );
		}

		if ( ! isset( $data[ self::NAME_KEY ] ) || ! is_string( $data[ self::NAME_KEY ] ) ) {
			throw new \ValueError( 'The block name must be specified.' );
		} else {
			$this->name = $data[ self::NAME_KEY ];
		}

		if ( ! isset( $data[ self::ID_KEY ] ) ) {
			$this->id = $this->root_template->generate_block_id( $this->get_name() );
		} else {
			$this->id = $data[ self::ID_KEY ];
		}

		if ( isset( $data[ self::ORDER_KEY ] ) && ! is_int( $data[ self::ORDER_KEY ] ) ) {
			throw new \ValueError( 'The block order must be an integer.' );
		} elseif ( isset( $data[ self::ORDER_KEY ] ) ) {
			$this->order = $data[ self::ORDER_KEY ];
		}

		if ( isset( $data[ self::ATTRIBUTES_KEY ] ) && ! is_array( $data[ self::ATTRIBUTES_KEY ] ) ) {
			throw new \ValueError( 'The block attributes must be an array.' );
		} elseif ( isset( $data[ self::ATTRIBUTES_KEY ] ) ) {
			$this->attributes = $data[ self::ATTRIBUTES_KEY ];
		}


	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_id(): string {
		return $this->id;
	}

	public function get_order(): int {
		return $this->order;
	}

	public function set_order( int $order ) {
		$this->order = $order;
	}

	public function get_attributes(): array {
		return $this->attributes;
	}

	public function set_attributes( array $attributes ) {
		$this->attributes = $attributes;
	}

	public function &get_root_template(): BlockTemplate {
		return $this->root_template;
	}

	public function &get_parent(): BlockContainerInterface {
		return $this->parent;
	}

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
