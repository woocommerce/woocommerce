<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;

/**
 * Block configuration used to specify blocks in BlockTemplate.
 */
class AbstractBlock implements BlockInterface {
	use BlockFormattedTemplateTrait;

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
	private $order = 10000;

	/**
	 * The block attributes.
	 *
	 * @var array
	 */
	private $attributes = array();

	/**
	 * The block hide conditions.
	 *
	 * @var array
	 */
	private $hide_conditions = array();

	/**
	 * The block hide conditions counter.
	 *
	 * @var int
	 */
	private $hide_conditions_counter = 0;

	/**
	 * The block disable conditions.
	 *
	 * @var array
	 */
	private $disable_conditions = array();

	/**
	 * The block disable conditions counter.
	 *
	 * @var int
	 */
	private $disable_conditions_counter = 0;

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

		if ( isset( $config[ self::HIDE_CONDITIONS_KEY ] ) ) {
			foreach ( $config[ self::HIDE_CONDITIONS_KEY ] as $hide_condition ) {
				$this->add_hide_condition( $hide_condition['expression'] );
			}
		}

		if ( isset( $config[ self::DISABLE_CONDITIONS_KEY ] ) ) {
			foreach ( $config[ self::DISABLE_CONDITIONS_KEY ] as $disable_condition ) {
				$this->add_disable_condition( $disable_condition['expression'] );
			}
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
	 * Set a block attribute value without replacing the entire attributes object.
	 *
	 * @param string $key The attribute key.
	 * @param mixed  $value The attribute value.
	 */
	public function set_attribute( string $key, $value ) {
		$this->attributes[ $key ] = $value;
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
	 * Remove the block from its parent.
	 */
	public function remove() {
		$this->parent->remove_block( $this->id );
	}

	/**
	 * Check if the block is detached from its parent block container or the template it belongs to.
	 *
	 * @return bool True if the block is detached from its parent block container or the template it belongs to.
	 */
	public function is_detached(): bool {
		$is_in_parent        = $this->parent->get_block( $this->id ) === $this;
		$is_in_root_template = $this->get_root_template()->get_block( $this->id ) === $this;

		return ! ( $is_in_parent && $is_in_root_template );
	}

	/**
	 * Add a hide condition to the block.
	 *
	 * The hide condition is a JavaScript-like expression that will be evaluated on the client to determine if the block should be hidden.
	 * See [@woocommerce/expression-evaluation](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/expression-evaluation/README.md) for more details.
	 *
	 * @param string $expression An expression, which if true, will hide the block.
	 */
	public function add_hide_condition( string $expression ): string {
		$key = 'k' . $this->hide_conditions_counter;
		$this->hide_conditions_counter++;

		// Storing the expression in an array to allow for future expansion
		// (such as adding the plugin that added the condition).
		$this->hide_conditions[ $key ] = array(
			'expression' => $expression,
		);

		/**
		 * Action called after a hide condition is added to a block.
		 *
		 * @param BlockInterface $block The block.
		 *
		 * @since 8.4.0
		 */
		do_action( 'woocommerce_block_template_after_add_hide_condition', $this );

		return $key;
	}

	/**
	 * Remove a hide condition from the block.
	 *
	 * @param string $key The key of the hide condition to remove.
	 */
	public function remove_hide_condition( string $key ) {
		unset( $this->hide_conditions[ $key ] );

		/**
		 * Action called after a hide condition is removed from a block.
		 *
		 * @param BlockInterface $block The block.
		 *
		 * @since 8.4.0
		 */
		do_action( 'woocommerce_block_template_after_remove_hide_condition', $this );
	}

	/**
	 * Get the hide conditions of the block.
	 */
	public function get_hide_conditions(): array {
		return $this->hide_conditions;
	}

	/**
	 * Add a disable condition to the block.
	 *
	 * The disable condition is a JavaScript-like expression that will be evaluated on the client to determine if the block should be hidden.
	 * See [@woocommerce/expression-evaluation](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/expression-evaluation/README.md) for more details.
	 *
	 * @param string $expression An expression, which if true, will disable the block.
	 */
	public function add_disable_condition( string $expression ): string {
		$key = 'k' . $this->disable_conditions_counter;
		$this->disable_conditions_counter++;

		// Storing the expression in an array to allow for future expansion
		// (such as adding the plugin that added the condition).
		$this->disable_conditions[ $key ] = array(
			'expression' => $expression,
		);

		return $key;
	}

	/**
	 * Remove a disable condition from the block.
	 *
	 * @param string $key The key of the disable condition to remove.
	 */
	public function remove_disable_condition( string $key ) {
		unset( $this->disable_conditions[ $key ] );
	}

	/**
	 * Get the disable conditions of the block.
	 */
	public function get_disable_conditions(): array {
		return $this->disable_conditions;
	}
}
