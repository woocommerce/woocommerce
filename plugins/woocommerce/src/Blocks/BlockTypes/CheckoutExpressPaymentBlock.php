<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Exception;

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
	 * Default styles for the express payment buttons
	 *
	 * @var boolean
	 */
	protected $default_styles = null;

	/**
	 * Current styles for the express payment buttons
	 *
	 * @var boolean
	 */
	protected $current_styles = null;

	/**
	 * Initialise the block
	 */
	protected function initialize() {
		parent::initialize();

		$this->default_styles = array(
			'showButtonStyles'   => false,
			'buttonHeight'       => '48',
			'buttonBorderRadius' => '4',
		);

		add_action( 'save_post', array( $this, 'sync_express_payment_attrs' ), 10, 2 );
	}

	/**
	 * Synchorize the express payment attributes between the Cart and Checkout pages.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function sync_express_payment_attrs( $post_id, $post ) {
		if ( wc_get_page_id( 'cart' ) === $post_id ) {
			$cart_or_checkout = 'cart';
		} elseif ( wc_get_page_id( 'checkout' ) === $post_id ) {
			$cart_or_checkout = 'checkout';
		} else {
			return;
		}

		// This is not a proper save action, maybe an autosave, so don't continue.
		if ( empty( $post->post_status ) || 'inherit' === $post->post_status ) {
			return;
		}

		$block_name    = 'woocommerce/' . $cart_or_checkout;
		$page_id       = 'woocommerce_' . $cart_or_checkout . '_page_id';
		$template_name = 'page-' . $cart_or_checkout;

		// Check if we are editing the cart/checkout page and that it contains a Cart/Checkout block.
		// Cast to string for Cart/Checkout page ID comparison because get_option can return it as a string, so better to compare both values as strings.
		if ( ! empty( $post->post_type ) && 'wp_template' !== $post->post_type && ( false === has_block( $block_name, $post ) || (string) get_option( $page_id ) !== (string) $post_id ) ) {
			return;
		}

		// Check if we are editing the Cart/Checkout template and that it contains a Cart/Checkout block.
		if ( ( ! empty( $post->post_type ) && ! empty( $post->post_name ) && $template_name !== $post->post_name && 'wp_template' === $post->post_type ) || false === has_block( $block_name, $post ) ) {
			return;
		}

		if ( empty( $post->post_content ) ) {
			return;
		}

		try {
			// Parse the post content to get the express payment attributes of the current page.
			$attrs = CartCheckoutUtils::find_express_checkout_attributes( $post->post_content, $cart_or_checkout );

			if ( ! is_array( $attrs ) ) {
				return;
			}
			$updated_attrs = array_merge( $this->default_styles, $attrs );

			// We need to sync the attributes between the Cart and Checkout pages.
			$other_page = 'cart' === $cart_or_checkout ? 'checkout' : 'cart';

			$this->update_other_page_with_express_payment_attrs( $other_page, $updated_attrs );
		} catch ( Exception $e ) {
			wc_get_logger()->log( 'error', 'Error updating express payment attributes: ' . $e->getMessage() );
		}
	}

	/**
	 * Update the express payment attributes in the other page (Cart or Checkout).
	 *
	 * @param string $cart_or_checkout The page to update.
	 * @param array  $updated_attrs     The updated attributes.
	 */
	private function update_other_page_with_express_payment_attrs( $cart_or_checkout, $updated_attrs ) {
		$page_id = 'cart' === $cart_or_checkout ? wc_get_page_id( 'cart' ) : wc_get_page_id( 'checkout' );

		if ( -1 === $page_id ) {
			return;
		}

		$post = get_post( $page_id );

		if ( empty( $post->post_content ) ) {
			return;
		}

		$blocks = parse_blocks( $post->post_content );
		CartCheckoutUtils::update_blocks_with_new_attrs( $blocks, $cart_or_checkout, $updated_attrs );

		$updated_content = serialize_blocks( $blocks );
		remove_action( 'save_post', array( $this, 'sync_express_payment_attrs' ), 10, 2 );

		wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => $updated_content,
			),
			false,
			false
		);

		add_action( 'save_post', array( $this, 'sync_express_payment_attrs' ), 10, 2 );
	}
}
