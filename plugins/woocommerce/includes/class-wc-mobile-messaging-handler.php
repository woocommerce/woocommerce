<?php
/**
 * Prepares formatted mobile deep link navigation link for order mails
 *
 * @package WooCommerce\Emails
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Mobile_Messaging_Handler
 */
class WC_Mobile_Messaging_Handler {

	/**
	 * Prepares mobile messaging with a deep link
	 *
	 * @param int  $order_id of order to make a deep link for.
	 * @param ?int $blog_id  of blog to make a deep link for.
	 *
	 * @return string|null
	 */
	public static function prepare_mobile_message(
		int $order_id,
		?int $blog_id
	): ?string {

		if ( $blog_id === null ) {
			return null;
		} else {
			$url = add_query_arg(
				array(
					'blog_id'  => absint( $blog_id ),
					'order_id' => absint( $order_id ),
				),
				'https://woocommerce.com/mobile'
			);

			return sprintf(
				wp_kses_data(
				/* translators: %s: Email link */
					__( '<a href="%s">Manage the order</a> in the mobile app.', 'woocommerce' )
				),
				esc_url( $url )
			);
		}
	}
}

