<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutActionsBlock class.
 */
class CheckoutActionsBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-actions-block';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		parent::initialize();

		add_action( 'wp_loaded', array( $this, 'register_style_variations' ) );
	}

	/**
	 * Register style variations for the block.
	 */
	public function register_style_variations() {
		register_block_style(
			$this->get_full_block_name(),
			array(
				'name'       => 'without-price',
				'label'      => __( 'Hide Price', 'woocommerce' ),
				'is_default' => true,
			)
		);

		register_block_style(
			$this->get_full_block_name(),
			array(
				'name'       => 'with-price',
				'label'      => __( 'Show Price', 'woocommerce' ),
				'is_default' => false,
			)
		);
	}
}
