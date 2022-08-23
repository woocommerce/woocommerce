<?php
/**
 * Prepares formatted mobile deep link navigation link for order mails
 *
 * @package WooCommerce\Emails
 */

defined( 'ABSPATH' ) || exit;

/**
 * MobileFooterHandler
 */
class MobileFooterHandler {

	const OPEN_ORDER_INTERVAL_DAYS = 7;

	/**
	 * Prepares footer with deep link
	 *
	 * @param int $order_id of order to make a deep link for.
	 *
	 * @return string|null
	 */
	public static function prepare_mobile_footer( $order_id ): ?string {
		try {
			$last_mobile_used = self::get_closer_mobile_usage_date();
			$now              = new DateTime();

			if ( $last_mobile_used->diff( $now )->days > self::OPEN_ORDER_INTERVAL_DAYS ) {
				return null;
			} else {
				$blog_id = get_current_blog_id();

				return "<a href='https://woocommerce.com/mobile?blog_id=$blog_id&order_id=$order_id'><strong>Click here</strong></a> to manage the order in the mobile app.>";
			}
		} catch ( Exception $e ) {
			return null;
		}
	}


	/**
	 * Returns the closest date of last usage of any mobile app platform
	 *
	 * @return DateTime|null
	 */
	private static function get_closer_mobile_usage_date(): ?DateTime {
		$mobile_usage = WC_Tracker::get_woocommerce_mobile_usage();

		try {
			$last_ios_used     = new DateTime( $mobile_usage['ios']['last_used'] );
			$last_android_used = new DateTime( $mobile_usage['android']['last_used'] );

			return max( $last_android_used, $last_ios_used );
		} catch ( Exception $e ) {
			return null;
		}
	}
}

