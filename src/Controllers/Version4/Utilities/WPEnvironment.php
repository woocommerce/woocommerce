<?php
/**
 * WP information for status report.
 *
 * @package WooCommerce/Utilities
 */

namespace WooCommerce\RestApi\Controllers\Version4\Utilities;

/**
 * WPEnvironment class.
 */
class WPEnvironment {
	/**
	 * Returns security tips.
	 *
	 * @return array
	 */
	public function get_security_info() {
		$check_page = wc_get_page_permalink( 'shop' );
		return array(
			'secure_connection' => 'https' === substr( $check_page, 0, 5 ),
			'hide_errors'       => ! ( defined( 'WP_DEBUG' ) && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG && WP_DEBUG_DISPLAY ) || 0 === intval( ini_get( 'display_errors' ) ),
		);
	}

	/**
	 * Get array of counts of objects. Orders, products, etc.
	 *
	 * @return array
	 */
	public function get_post_type_counts() {
		global $wpdb;

		$post_type_counts = $wpdb->get_results( "SELECT post_type AS 'type', count(1) AS 'count' FROM {$wpdb->posts} GROUP BY post_type;" );

		return is_array( $post_type_counts ) ? $post_type_counts : array();
	}

	/**
	 * Returns a mini-report on WC pages and if they are configured correctly:
	 * Present, visible, and including the correct shortcode.
	 *
	 * @return array
	 */
	public function get_pages() {
		// WC pages to check against.
		$check_pages = array(
			_x( 'Shop base', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_shop_page_id',
				'shortcode' => '',
			),
			_x( 'Cart', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_cart_page_id',
				'shortcode' => '[' . apply_filters( 'woocommerce_cart_shortcode_tag', 'woocommerce_cart' ) . ']',
			),
			_x( 'Checkout', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_checkout_page_id',
				'shortcode' => '[' . apply_filters( 'woocommerce_checkout_shortcode_tag', 'woocommerce_checkout' ) . ']',
			),
			_x( 'My account', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_myaccount_page_id',
				'shortcode' => '[' . apply_filters( 'woocommerce_my_account_shortcode_tag', 'woocommerce_my_account' ) . ']',
			),
			_x( 'Terms and conditions', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_terms_page_id',
				'shortcode' => '',
			),
		);

		$pages_output = array();
		foreach ( $check_pages as $page_name => $values ) {
			$page_id            = get_option( $values['option'] );
			$page_set           = false;
			$page_exists        = false;
			$page_visible       = false;
			$shortcode_present  = false;
			$shortcode_required = false;

			// Page checks.
			if ( $page_id ) {
				$page_set = true;
			}
			if ( get_post( $page_id ) ) {
				$page_exists = true;
			}
			if ( 'publish' === get_post_status( $page_id ) ) {
				$page_visible = true;
			}

			// Shortcode checks.
			if ( $values['shortcode'] && get_post( $page_id ) ) {
				$shortcode_required = true;
				$page               = get_post( $page_id );
				if ( strstr( $page->post_content, $values['shortcode'] ) ) {
					$shortcode_present = true;
				}
			}

			// Wrap up our findings into an output array.
			$pages_output[] = array(
				'page_name'          => $page_name,
				'page_id'            => $page_id,
				'page_set'           => $page_set,
				'page_exists'        => $page_exists,
				'page_visible'       => $page_visible,
				'shortcode'          => $values['shortcode'],
				'shortcode_required' => $shortcode_required,
				'shortcode_present'  => $shortcode_present,
			);
		}

		return $pages_output;
	}
}
