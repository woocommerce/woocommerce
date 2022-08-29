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

	const OPEN_ORDER_INTERVAL_DAYS = 30;

	/**
	 * Prepares mobile messaging with a deep link
	 *
	 * @param int      $order_id of order to make a deep link for.
	 * @param ?int     $blog_id  of blog to make a deep link for.
	 * @param DateTime $now      current DateTime.
	 *
	 * @return string|null
	 */
	public static function prepare_mobile_message(
		int $order_id,
		?int $blog_id,
		DateTime $now
	): ?string {
		try {
			$last_mobile_used = self::get_closer_mobile_usage_date();

			if ( $last_mobile_used->diff( $now )->days > self::OPEN_ORDER_INTERVAL_DAYS || $blog_id === null ) {
				return null;
			} else {
				$url = add_query_arg(
					array(
						'blog_id'  => absint( $blog_id ),
						'order_id' => absint( $order_id ),
					),
					'https://woocommerce.com/mobile/order'
				);

				return sprintf(
					wp_kses_data(
						/* translators: %s: Email link */
						__( '<a href="%s">Manage the order</a> in the mobile app.', 'woocommerce' )
					),
					esc_url( $url )
				);

			}
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Returns the closest date of last usage of any mobile app platform.
	 *
	 * @return DateTime|null
	 */
	private static function get_closer_mobile_usage_date(): ?DateTime {
		$mobile_usage = WC_Tracker::get_woocommerce_mobile_usage();

		$last_ios_used     = self::get_last_used_or_null( 'ios', $mobile_usage );
		$last_android_used = self::get_last_used_or_null( 'android', $mobile_usage );

		return max( $last_android_used, $last_ios_used );
	}

	/**
	 * Returns last used date of specified mobile app platform.
	 *
	 * @param string $platform     mobile platform to check.
	 * @param array  $mobile_usage mobile apps usage data.
	 *
	 * @return DateTime|null last used date of specified mobile app
	 */
	private static function get_last_used_or_null(
		string $platform, array $mobile_usage
	): ?DateTime {
		try {
			if ( array_key_exists( $platform, $mobile_usage ) ) {
				return new DateTime( $mobile_usage[ $platform ]['last_used'] );
			} else {
				return null;
			}
		} catch ( Exception $e ) {
			return null;
		}
	}
}

