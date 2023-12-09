<?php

namespace Automattic\WooCommerce\Blocks\Domain\Services\OnboardingTasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Review the cart/checkout Task
 */
class ReviewCheckoutTask extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'review-checkout-experience';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Review your checkout experience', 'woo-gutenberg-products-block' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return '';
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return '';
	}

	/**
	 * Additional Info.
	 *
	 * @return string
	 */
	public function get_additional_info() {
		return __( 'Make sure cart and checkout flows are configured correctly for your shoppers.', 'woo-gutenberg-products-block' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return $this->is_visited();
	}

	/**
	 * Check if the store uses blocks on the cart or checkout page.
	 *
	 * @return boolean
	 */
	private function has_cart_block() {
		$cart_page_id   = wc_get_page_id( 'cart' );
		$has_block_cart = $cart_page_id && ( has_block( 'woocommerce/cart', $cart_page_id ) || has_block( 'woocommerce/classic-shortcode', $cart_page_id ) );

		return $has_block_cart;
	}

	/**
	 * Check if the store uses blocks on the cart or checkout page.
	 *
	 * @return boolean
	 */
	private function has_checkout_block() {
		$cart_page_id   = wc_get_page_id( 'cart' );
		$has_block_cart = $cart_page_id && ( has_block( 'woocommerce/cart', $cart_page_id ) || has_block( 'woocommerce/classic-shortcode', $cart_page_id ) );

		return $has_block_cart;
	}

	/**
	 * Check if the store uses blocks on the cart or checkout page.
	 *
	 * @return boolean
	 */
	private function has_cart_or_checkout_block() {
		return $this->has_cart_block() || $this->has_checkout_block();
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return $this->has_cart_or_checkout_block();
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		$base_url = wc_current_theme_is_fse_theme() ? 'site-editor.php?postType=page&postId=' : 'post.php?action=edit&post=';
		$page_id  = $this->has_cart_block() ? wc_get_page_id( 'cart' ) : wc_get_page_id( 'checkout' );
		$focus    = $this->has_cart_block() ? 'cart' : 'checkout';

		return admin_url( $base_url . absint( $page_id ) . '&focus=' . $focus . '&canvas=edit' );
	}
}
