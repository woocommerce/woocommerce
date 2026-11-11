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
	 * @return void
	 */
	protected function initialize() {
		parent::initialize();

		// Backward compatibility: keep hook for child classes (third-parties which might be overriding register_style_variations).
		if ( static::class !== __CLASS__ ) {
			add_action( 'wp_loaded', array( $this, 'register_style_variations' ) );
		}
	}

	/**
	 * Register style variations for the block.
	 *
	 * @internal
	 * @deprecated 11.4.0 Style variations are now declared in block.json.
	 */
	public function register_style_variations() {
	}
}
