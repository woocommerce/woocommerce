<?php
/**
 * Functionality that takes a static URL, constructs a cart, and redirects to the checkout with a cart session.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
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
	 * Prefix numeric SKUs with `sku=`. Delimiters within identifiers, keys, or values must be escaped with a tilde.
	 *
	 * @return array[] Array of product data formatted for CartController::add_to_cart().
	 */
	protected function get_products_from_checkout_link() {
		$products_query = wp_unslash( $_GET['products'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_string( $products_query ) ) {
			return [];
		}

		$products_query = sanitize_text_field( $products_query );
		$raw_products   = array_filter( $this->split_escaped( $products_query, ',' ) );
		$products       = [];

		foreach ( $raw_products as $raw_product ) {
			$segments           = $this->split_escaped( $raw_product, ':', 4 );
			$product_identifier = $segments[0] ?? '';
			$quantity           = '' !== ( $segments[1] ?? '' ) ? absint( $segments[1] ) : 1;
			$variation_data     = $this->parse_product_data( $segments[2] ?? '' );
			$cart_item_data     = $this->parse_product_data( $segments[3] ?? '' );

			if ( ! $product_identifier || ! $quantity ) {
				continue;
			}

			if ( 0 === strpos( $product_identifier, 'sku=' ) ) {
				$product_id = wc_get_product_id_by_sku( substr( $product_identifier, 4 ) );
			} elseif ( is_numeric( $product_identifier ) ) {
				$product_id = absint( $product_identifier );
			} else {
				$product_id = wc_get_product_id_by_sku( $product_identifier );
			}

			if ( ! $product_id ) {
				continue;
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
	 * @since 11.2.0
	 *
	 * @param string $raw_data Raw product data from the checkout link.
	 * @return array<string, string> Sanitized product data.
	 */
	protected function parse_product_data( $raw_data ) {
		$data = [];

		foreach ( $this->split_escaped( $raw_data, ';' ) as $pair ) {
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
	 * Split a string on unescaped delimiters.
	 *
	 * @param string $value     Value to split.
	 * @param string $delimiter Single-character delimiter.
	 * @param int    $limit     Maximum number of returned segments.
	 * @return string[] Split values with delimiter escapes removed.
	 */
	private function split_escaped( $value, $delimiter, $limit = PHP_INT_MAX ) {
		$segments = [ '' ];
		$index    = 0;
		$length   = strlen( $value );

		for ( $position = 0; $position < $length; ++$position ) {
			$character = $value[ $position ];

			if ( '~' === $character && $position + 1 < $length && $delimiter === $value[ $position + 1 ] ) {
				$segments[ $index ] .= $delimiter;
				++$position;
			} elseif ( $delimiter === $character && count( $segments ) < $limit ) {
				$segments[] = '';
				++$index;
			} else {
				$segments[ $index ] .= $character;
			}
		}

		return $segments;
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
		$controller    = new CartController();
		$products      = $this->get_products_from_checkout_link();
		$errors        = new \WP_Error();
		$needs_options = [];
		$options_url   = '';

		foreach ( $products as $product_data ) {
			try {
				$controller->add_to_cart( $product_data );
			} catch ( \Exception $e ) {
				// Variations with an "Any" attribute need the shopper to choose a value, so send them to the product page.
				$product             = $this->is_missing_variation_data_error( $e ) ? wc_get_product( $product_data['id'] ) : null;
				$product_options_url = $product ? $this->get_product_options_url( $product, $product_data['variation'] ) : '';

				if ( ! $product || ! $product_options_url ) {
					$errors->add( 'error', $e->getMessage() );
					continue;
				}

				/* translators: %s: product name */
				$needs_options[] = sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce' ), esc_html( $product->get_name() ) );

				if ( '' === $options_url ) {
					$options_url = $product_options_url;
				}
			}
		}

		if ( $needs_options ) {
			$errors->add(
				'checkout_link_needs_options',
				sprintf(
					/* translators: %s: comma-separated list of product names */
					_n( 'Choose options for %s to add it to your cart.', 'Choose options for %s to add them to your cart.', count( $needs_options ), 'woocommerce' ),
					wc_format_list_of_items( $needs_options )
				)
			);
		}

		// Nothing was added to the cart. Redirect to the product page if options are needed, otherwise the cart page, with
		// an error notice. Since guests may not have a session, add the notice in the query string.
		if ( wc()->cart->is_empty() ) {
			if ( $options_url ) {
				$redirect_url = $options_url;
				$notice_code  = 'checkout_link_needs_options';
			} else {
				$redirect_url = wc_get_cart_url();
				$notice_code  = 'error';
				$errors->add( 'error', __( 'The provided checkout link was out of date or invalid. No products were added to the cart.', 'woocommerce' ) );
			}

			if ( ! wc()->session->has_session() ) {
				return add_query_arg( 'wc_error', rawurlencode( $errors->get_error_message( $notice_code ) ), $redirect_url );
			}

			$this->add_error_notices( $errors );

			return $redirect_url;
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

		$redirect_url = $options_url ? $options_url : wc_get_checkout_url();

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

	/**
	 * Check if an add to cart error was caused by missing variation attributes.
	 *
	 * @param \Exception $exception The add to cart exception.
	 * @return bool
	 */
	private function is_missing_variation_data_error( \Exception $exception ) {
		return $exception instanceof RouteException
			&& in_array( $exception->getErrorCode(), [ 'woocommerce_rest_missing_variation_data', 'woocommerce_rest_missing_attributes' ], true );
	}

	/**
	 * Get the product page URL where the shopper can choose the attributes missing from a checkout link.
	 *
	 * Attributes set by the variation, or passed in the link, are added to the URL so they are preselected.
	 *
	 * @param \WC_Product $product   The variable product or variation from the checkout link.
	 * @param array[]     $variation Variation attributes parsed from the checkout link.
	 * @return string The product page URL, or an empty string if the product page is not available.
	 */
	private function get_product_options_url( \WC_Product $product, array $variation ) {
		$is_variation   = $product instanceof \WC_Product_Variation;
		$parent_product = $is_variation ? wc_get_product( $product->get_parent_id() ) : $product;

		if ( ! $parent_product || ProductStatus::PUBLISH !== $parent_product->get_status() || ProductStatus::PUBLISH !== $product->get_status() ) {
			return '';
		}

		$selected  = $is_variation ? array_filter( $product->get_variation_attributes(), 'wc_array_filter_default_attributes' ) : [];
		$requested = wp_list_pluck( $variation, 'value', 'attribute' );

		foreach ( $parent_product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_variation() ) {
				continue;
			}

			$attribute_name = $attribute->get_name();
			$query_key      = wc_variation_attribute_name( $attribute_name );

			foreach ( [ $query_key, $attribute_name, strtolower( wc_attribute_label( $attribute_name, $parent_product ) ) ] as $requested_key ) {
				if ( isset( $requested[ $requested_key ] ) && '' !== $requested[ $requested_key ] ) {
					$selected[ $query_key ] = $attribute->is_taxonomy() ? sanitize_title( $requested[ $requested_key ] ) : $requested[ $requested_key ];
					break;
				}
			}
		}

		// Encode keys and values so add_query_arg() keeps them intact, matching WC_Product_Variation::get_permalink().
		return add_query_arg(
			array_combine( array_map( 'urlencode', array_keys( $selected ) ), array_map( 'urlencode', $selected ) ),
			$parent_product->get_permalink()
		);
	}
}
