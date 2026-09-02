<?php
/**
 * Functionality that takes a static URL, constructs a cart, and redirects to the checkout with a cart session.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout Link class.
 */
class CheckoutLink {
	/**
	 * Initialize the checkout link service.
	 */
	public function init() {
		add_action( 'init', array( $this, 'add_checkout_link_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'template_redirect', array( $this, 'handle_checkout_link_endpoint' ) );
	}

	/**
	 * Add the checkout link endpoint.
	 */
	public function add_checkout_link_endpoint() {
		// get registered rewrite rules.
		$rules = get_option( 'rewrite_rules', array() );
		$regex = '^checkout-link$';

		add_rewrite_rule( $regex, 'index.php?checkout-link=true', 'top' );

		// maybe flush rewrite rules if it was not previously in the option.
		if ( ! isset( $rules[ $regex ] ) ) {
			\WC_Post_Types::flush_rewrite_rules();
		}
	}

	/**
	 * Add the checkout link query var.
	 *
	 * @param array $vars The query vars.
	 * @return array The query vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'checkout-link';
		return $vars;
	}

	/**
	 * Handle the checkout link endpoint.
	 *
	 * @return void
	 */
	public function handle_checkout_link_endpoint() {
		if ( ! get_query_var( 'checkout-link' ) ) {
			return;
		}

		if ( ! $this->validate_checkout_link() ) {
			$redirect = add_query_arg( 'wc_error', rawurlencode( __( 'The provided checkout link was out of date or invalid. No products were added to the cart.', 'woocommerce' ) ), wc_get_cart_url() );
		} else {
			wc()->cart->empty_cart();
			$redirect = $this->get_checkout_link();
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Validate the checkout link.
	 *
	 * @return bool True if the checkout link is valid, false otherwise.
	 */
	protected function validate_checkout_link() {
		$products = $this->get_products_from_checkout_link();

		return ! empty( $products );
	}

	/**
	 * Get the products from the checkout link.
	 *
	 * Products use the format `id-or-sku:quantity:variation-data:cart-item-data`. Quantity and both data groups are optional,
	 * while an empty variation group separates cart item data. Multiple key-value pairs within a data group use semicolons.
	 *
	 * @return array[] Array of product data formatted for CartController::add_to_cart().
	 */
	protected function get_products_from_checkout_link() {
		$raw_products = array_filter( explode( ',', wc_clean( wp_unslash( $_GET['products'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$products     = [];

		foreach ( $raw_products as $raw_product ) {
			$segments           = explode( ':', $raw_product, 4 );
			$product_identifier = $segments[0] ?? '';
			$quantity           = '' !== ( $segments[1] ?? '' ) ? absint( $segments[1] ) : 1;
			$variation_data     = $this->parse_product_data( $segments[2] ?? '' );
			$cart_item_data     = $this->parse_product_data( $segments[3] ?? '' );

			if ( ! $product_identifier || ! $quantity ) {
				continue;
			}

			if ( is_numeric( $product_identifier ) ) {
				$product_id = absint( $product_identifier );
			} else {
				$product_id = wc_get_product_id_by_sku( $product_identifier );
				if ( ! $product_id ) {
					continue;
				}
			}

			$variation = array_map(
				function ( $key, $value ) {
					return [
						'attribute' => $key,
						'value'     => $value,
					];
				},
				array_keys( $variation_data ),
				$variation_data
			);

			$products[] = [
				'id'             => $product_id,
				'quantity'       => $quantity,
				'variation'      => $variation,
				'cart_item_data' => $cart_item_data,
			];
		}

		return $products;
	}

	/**
	 * Parse a semicolon-separated list of key-value pairs.
	 *
	 * @param string $raw_data Raw product data from the checkout link.
	 * @return array<string, string> Sanitized product data.
	 */
	protected function parse_product_data( $raw_data ) {
		$data = [];

		foreach ( explode( ';', $raw_data ) as $pair ) {
			if ( false === strpos( $pair, '=' ) ) {
				continue;
			}

			list( $key, $value ) = explode( '=', $pair, 2 );
			$key                 = sanitize_key( $key );

			if ( '' !== $key ) {
				$data[ $key ] = sanitize_text_field( $value );
			}
		}

		return $data;
	}

	/**
	 * Add error notices to the cart.
	 *
	 * @param \WP_Error $errors The errors.
	 * @return void
	 */
	protected function add_error_notices( \WP_Error $errors ) {
		foreach ( $errors->get_error_messages() as $message ) {
			wc_add_notice( $message, 'error' );
		}
	}

	/**
	 * Process the query params and return the checkout link to redirect to complete with session token.
	 *
	 * @return string The checkout link.
	 */
	protected function get_checkout_link() {
		$controller = new CartController();
		$products   = $this->get_products_from_checkout_link();
		$errors     = new \WP_Error();

		foreach ( $products as $product_data ) {
			try {
				$controller->add_to_cart( $product_data );
			} catch ( \Exception $e ) {
				$errors->add( 'error', $e->getMessage() );
			}
		}

		// Nothing was added to the cart. We need to redirect to the cart page with an error notice. Since guests may not
		// have a session, add the notice in the query string.
		if ( wc()->cart->is_empty() ) {
			$errors->add( 'error', __( 'The provided checkout link was out of date or invalid. No products were added to the cart.', 'woocommerce' ) );

			if ( ! wc()->session->has_session() ) {
				return add_query_arg( 'wc_error', rawurlencode( $errors->get_error_message() ), wc_get_cart_url() );
			} else {
				$this->add_error_notices( $errors );
			}

			return wc_get_cart_url();
		}

		// Apply coupon if provided.
		$coupon = wc_format_coupon_code( wp_unslash( $_GET['coupon'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( wc_coupons_enabled() && ! empty( $coupon ) ) {
			try {
				$controller->apply_coupon( $coupon );
			} catch ( \Exception $e ) {
				$errors->add( 'error', $e->getMessage() );
			}
		}

		// Add error notices to the cart. This requires a session otherwise the notices will not be displayed.
		$this->add_error_notices( $errors );

		$redirect_url = wc_get_checkout_url();

		// Preserve the query string--pass it to the checkout page.
		if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$redirect_url = remove_query_arg(
				[
					'products',
					'coupon',
					'checkout-link',
				],
				add_query_arg( wp_unslash( $_SERVER['QUERY_STRING'] ), '', $redirect_url ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			);
		}

		// If the user is logged in, the session is tied to the user ID. Do not use a cart token.
		if ( ! is_user_logged_in() ) {
			$session_token = CartTokenUtils::get_cart_token( (string) wc()->session->get_customer_id() );
			$redirect_url  = add_query_arg( 'session', $session_token, $redirect_url );
		}

		return $redirect_url;
	}
}
