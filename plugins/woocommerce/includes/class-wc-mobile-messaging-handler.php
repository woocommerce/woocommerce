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
	 * @param WC_Order $order order that mobile message is created for.
	 * @param ?int     $blog_id  of blog to make a deep link for.
	 * @param DateTime $now      current DateTime.
	 *
	 * @return string|null
	 */
	public static function prepare_mobile_message(
		WC_Order $order,
		?int $blog_id,
		DateTime $now
	): ?string {
		try {
			$last_mobile_used = self::get_closer_mobile_usage_date();

			$used_app_in_last_month = null !== $last_mobile_used && $last_mobile_used->diff( $now )->days <= self::OPEN_ORDER_INTERVAL_DAYS;
			$has_jetpack            = null !== $blog_id;

			if ( $used_app_in_last_month && $has_jetpack ) {
				if ( is_store_in_person_payment_eligible() ) {
					if ( is_order_in_person_payment_eligible( $order ) ) {
						return self::accept_payment_message( $blog_id, $order->get_id() );
					} else {
						return self::manage_order_message( $blog_id, $order->get_id() );
					}
				} else {
					return self::manage_order_message( $blog_id, $order->get_id() );
				}
			} else {
				return self::no_app_message();
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

		if ( ! $mobile_usage ) {
			return null;
		}

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

	/**
	 * Prepares message with a deep link to mobile payment.
	 *
	 * @param int $blog_id blog id to deep link to.
	 * @param int $order_id order id to deep link to.
	 *
	 * @return string formatted message
	 */
	public static function accept_payment_message( int $blog_id, int $order_id ): string {
		$deep_link_url = add_query_arg(
			array(
				'blog_id'  => absint( $blog_id ),
				'order_id' => absint( $order_id ),
			),
			'https://woocommerce.com/mobile/payments'
		);

		return sprintf(
			wp_kses_data(
			/* translators: %s: Email link */
				__(
					'<a href="%1$s">Accept payments</a> with a card reader in our mobile app.<br /><a href="%2$s">Learn more about In-Person Payments.</a>',
					'woocommerce'
				)
			),
			esc_url( $deep_link_url ),
			'https://woocommerce.com/in-person-payments/'
		);
	}

	/**
	 * Prepares message with a deep link to manage order details.
	 *
	 * @param int $blog_id blog id to deep link to.
	 * @param int $order_id order id to deep link to.
	 *
	 * @return string formatted message
	 */
	public static function manage_order_message( int $blog_id, int $order_id ): string {
		$deep_link_url = add_query_arg(
			array(
				'blog_id'  => absint( $blog_id ),
				'order_id' => absint( $order_id ),
			),
			'https://woocommerce.com/mobile/order'
		);

		return sprintf(
			wp_kses_data(
			/* translators: %s: Email link */
				__(
					'<a href="%s">Manage the order</a> with the app.',
					'woocommerce'
				)
			),
			esc_url( $deep_link_url )
		);
	}

	/**
	 * Prepares message with a deep link to learn more about mobile app.
	 *
	 * @return string formatted message
	 */
	public static function no_app_message(): string {
		return sprintf(
			wp_kses_data(
			/* translators: %s: Email link */
				__(
					'Process your orders on the go. <a href="%s">Get the app</a>.',
					'woocommerce'
				)
			),
			'https://woocommerce.com/mobile/'
		);
	}
}

