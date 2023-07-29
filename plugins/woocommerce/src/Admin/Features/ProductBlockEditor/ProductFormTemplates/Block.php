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
	 * @var array
	 */
	private $data = [];

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var BlockTemplate
	 */
	private $root_template;

	/**
	 * @var BlockContainerInterface
	 */
	private $parent;

	public function __construct( array $data, BlockTemplate &$root_template, BlockContainerInterface &$parent = null ) {
		$this->data = wp_parse_args(
			$data,
			[
				self::ORDER_KEY      => 10,
				self::ATTRIBUTES_KEY => [],
			]
		);

		$this->root_template = $root_template;
		$this->parent        = is_null( $parent ) ? $root_template : $parent;

		if ( ! isset( $this->data[ self::NAME_KEY ] ) || ! is_string( $this->data[ self::NAME_KEY ] ) ) {
			throw new \ValueError( 'The block name must be specified.' );
		} else {
			$this->name = $this->data[ self::NAME_KEY ];
		}

		if ( $this->parent->get_root_template() !== $this->root_template ) {
			throw new \ValueError( 'The parent block must belong to the same template as the block.' );
		}

		if ( ! isset( $this->data[ self::ID_KEY ] ) ) {
			$this->data[ self::ID_KEY ] = $this->root_template->generate_block_id( $this->get_name() );
		}
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_id(): string {
		return $this->data[ self::ID_KEY ];
	}

	public function get_order(): int {
		return $this->data[ self::ORDER_KEY ];
	}

	public function set_order( int $order ) {
		$this->data[ self::ORDER_KEY ] = $order;
	}

	public function get_attributes(): array {
		return $this->data[ self::ATTRIBUTES_KEY ];
	}

	public function set_attributes( array $attributes ) {
		$this->data[ self::ATTRIBUTES_KEY ] = $attributes;
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
