<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

/**
 * CartExpressPaymentBlock class.
 */
class CartExpressPaymentBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'cart-express-payment-block';

	/**
	 * Uniform default_styles for the express payment buttons
	 *
	 * @var boolean
	 */
	protected $default_styles = null;

	/**
	 * Initialise the block
	 */
	protected function initialize() {
		parent::initialize();

		$this->default_styles = array(
			'showButtonStyles'   => true,
			'buttonHeight'       => '48',
			'buttonBorderRadius' => '4',
		);

		add_option(
			'woocommerce_express_payment_settings',
			$this->default_styles
		);

		add_action( 'save_post', array( $this, 'update_express_payment_settings' ), 10, 2 );
	}

	/**
	 * Persist the attributes of the express ppayment block to wp options
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function update_express_payment_settings( $post_id, $post ) {
		CartCheckoutUtils::update_express_payment_settings( $post_id, $post, 'cart', $this->default_styles );
	}

	/**
	 * Send extra data from server to client
	 *
	 * @param array $attributes Attributes to send to client.
	 */
	protected function enqueue_data( array $attributes = [] ) {

		$this->asset_data_registry->add(
			'expressCheckout',
			get_option( 'woocommerce_express_payment_settings', $this->default_styles )
		);
	}
}
