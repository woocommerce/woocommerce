<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutExpressPaymentBlock class.
 */
class CheckoutExpressPaymentBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-express-payment-block';
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
			'woocommerce_express_checkout_settings',
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
		// This is not a proper save action, maybe an autosave, so don't continue.
		if ( empty( $post->post_status ) || 'inherit' === $post->post_status ) {
			return;
		}

		// Check if we are editing the checkout page and that it contains a Checkout block.
		// Cast to string for Checkout page ID comparison because get_option can return it as a string, so better to compare both values as strings.
		if ( ! empty( $post->post_type ) && 'wp_template' !== $post->post_type && ( false === has_block( 'woocommerce/checkout', $post ) || (string) get_option( 'woocommerce_checkout_page_id' ) !== (string) $post_id ) ) {
			return;
		}

		// Check if we are editing the Checkout template and that it contains a Checkout block.
		if ( ( ! empty( $post->post_type ) && ! empty( $post->post_name ) && 'page-checkout' !== $post->post_name && 'wp_template' === $post->post_type ) || false === has_block( 'woocommerce/checkout', $post ) ) {
			return;
		}

		if ( empty( $post->post_content ) ) {
			return;
		}

		$blocks = parse_blocks( $post->post_content );
		$attrs  = $this->find_express_checkout_attributes( $blocks );

		$updated_attrs = $this->default_styles;
		if ( is_array( $attrs ) ) {
			$updated_attrs = array_merge( $this->default_styles, $attrs );
		}
		update_option(
			'woocommerce_express_checkout_settings',
			$updated_attrs
		);
	}

	/**
	 * Recursively search the checkout block to find the express checkout block and
	 * get the button style attributes
	 *
	 * @param array $blocks Blocks to search.
	 */
	private function find_express_checkout_attributes( $blocks ) {
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && 'woocommerce/checkout-express-payment-block' === $block['blockName'] && ! empty( $block['attrs'] ) ) {
				return $block['attrs'];
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$answer = $this->find_express_checkout_attributes( $block['innerBlocks'] );
				if ( $answer ) {
					return $answer;
				}
			}
		}
	}

	/**
	 * Send extra data from server to client
	 *
	 * @param array $attributes Attributes to send to client.
	 */
	protected function enqueue_data( array $attributes = [] ) {

		$this->asset_data_registry->add(
			'expressCheckout',
			get_option( 'woocommerce_express_checkout_settings', $this->default_styles )
		);
	}
}
