<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;

/**
 * Block configuration used to specify blocks in BlockTemplate.
 */
class AbstractBlock implements BlockInterface {
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
	 * The parent container.
	 *
	 * @var ContainerInterface
	 */
	private $parent;

	/**
	 * Block constructor.
	 *
	 * @param array                        $config The block configuration.
	 * @param BlockTemplateInterface       $root_template The block template that this block belongs to.
	 * @param BlockContainerInterface|null $parent The parent block container.
	 *
	 * @throws \ValueError If the block configuration is invalid.
	 * @throws \ValueError If the parent block container does not belong to the same template as the block.
	 */
	public function __construct( array $config, BlockTemplateInterface &$root_template, ContainerInterface &$parent = null ) {
		$this->validate( $config, $root_template, $parent );

		$this->root_template = $root_template;
		$this->parent        = is_null( $parent ) ? $root_template : $parent;

		$this->name = $config[ self::NAME_KEY ];

		if ( ! isset( $config[ self::ID_KEY ] ) ) {
			$this->id = $this->root_template->generate_block_id( $this->get_name() );
		} else {
			$this->id = $config[ self::ID_KEY ];
		}

		if ( isset( $config[ self::ORDER_KEY ] ) ) {
			$this->order = $config[ self::ORDER_KEY ];
		}

		if ( isset( $config[ self::ATTRIBUTES_KEY ] ) ) {
			$this->attributes = $config[ self::ATTRIBUTES_KEY ];
		}
	}

	/**
	 * Validate block configuration.
	 *
	 * @param array                   $config The block configuration.
	 * @param BlockTemplateInterface  $root_template The block template that this block belongs to.
	 * @param ContainerInterface|null $parent The parent block container.
	 *
	 * @throws \ValueError If the block configuration is invalid.
	 * @throws \ValueError If the parent block container does not belong to the same template as the block.
	 */
	protected function validate( array $config, BlockTemplateInterface &$root_template, ContainerInterface &$parent = null ) {
		if ( isset( $parent ) && ( $parent->get_root_template() !== $root_template ) ) {
			throw new \ValueError( 'The parent block must belong to the same template as the block.' );
		}

		if ( ! isset( $config[ self::NAME_KEY ] ) || ! is_string( $config[ self::NAME_KEY ] ) ) {
			throw new \ValueError( 'The block name must be specified.' );
		}

		if ( isset( $config[ self::ORDER_KEY ] ) && ! is_int( $config[ self::ORDER_KEY ] ) ) {
			throw new \ValueError( 'The block order must be an integer.' );
		}

		if ( isset( $config[ self::ATTRIBUTES_KEY ] ) && ! is_array( $config[ self::ATTRIBUTES_KEY ] ) ) {
			throw new \ValueError( 'The block attributes must be an array.' );
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
	 * Get the template that this block belongs to.
	 */
	public function &get_root_template(): BlockTemplateInterface {
		return $this->root_template;
	}

	/**
	 * Get the parent block container.
	 */
	public function &get_parent(): ContainerInterface {
		return $this->parent;
	}

	/**
	 * Get the block configuration as a formatted template.
	 *
	 * @return array The block configuration as a formatted template.
	 */
	public function get_formatted_template(): array {
		$arr = [
			$this->get_name(),
			$this->get_attributes(),
		];

		return $arr;
	}
}
