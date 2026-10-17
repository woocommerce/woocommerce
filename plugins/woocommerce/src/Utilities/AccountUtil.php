<?php
/**
 * AccountUtil class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Utilities;

/**
 * Utilities for working with customer accounts.
 */
final class AccountUtil {

	/**
	 * Get the My Account > Edit address page title.
	 *
	 * @since 11.1.0
	 *
	 * @param string $address_type Type of address; 'billing' or 'shipping'.
	 * @return string
	 */
	public static function get_edit_address_title( $address_type = 'billing' ) {
		$title = ( 'billing' === $address_type ) ? esc_html__( 'Billing address', 'woocommerce' ) : esc_html__( 'Shipping address', 'woocommerce' );

		/**
		 * Filters the My Account > Edit address page title.
		 *
		 * @since 2.1.0
		 *
		 * @param string $title        Page title.
		 * @param string $address_type Type of address; 'billing' or 'shipping'.
		 */
		$filtered_title = apply_filters( 'woocommerce_my_account_edit_address_title', $title, $address_type );

		return is_scalar( $filtered_title ) || null === $filtered_title ? (string) $filtered_title : $title;
	}
}
