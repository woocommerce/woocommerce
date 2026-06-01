<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Notifications;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Notification for new WooCommerce orders.
 *
 * @since 10.7.0
 */
class NewOrderNotification extends Notification {
	/**
	 * The notification type identifier for new orders.
	 */
	const TYPE = 'store_order';

	/**
	 * An array of emojis to select from when forming the payload.
	 */
	const EMOJI_LIST = array( '🎉', '🎊', '🥳', '👏', '🙌' );

	/**
	 * {@inheritDoc}
	 */
	public function get_type(): string {
		return self::TYPE;
	}

	/**
	 * Returns the WPCOM-ready payload for this notification.
	 *
	 * Returns null if the order no longer exists.
	 *
	 * @return array|null
	 *
	 * @since 10.7.0
	 */
	public function to_payload(): ?array {
		$order = WC()->call_function( 'wc_get_order', $this->get_resource_id() );

		if ( ! $order || ! $order instanceof WC_Order ) {
			return null;
		}

		return array(
			'type'        => $this->get_type(),
			// This represents the time the notification was triggered, so we can monitor age of notification at delivery.
			'timestamp'   => gmdate( 'c' ),
			'resource_id' => $this->get_resource_id(),
			'title'       => array(
				/**
				 * This will be translated in WordPress.com, format:
				 * 1: emoji
				 */
				'format' => 'You have a new order! %1$s',
				'args'   => array( self::EMOJI_LIST[ wp_rand( 0, count( self::EMOJI_LIST ) - 1 ) ] ),
			),
			'message'     => array(
				/**
				 * This will be translated in WordPress.com, format:
				 * 1: order total, 2: site title
				 */
				'format' => 'New order for %1$s on %2$s',
				'args'   => array(
					wp_strip_all_tags( $order->get_formatted_order_total() ),
					wp_strip_all_tags( get_bloginfo( 'name' ) ),
				),
			),
			'meta'        => array(
				'order_id' => $this->get_resource_id(),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * Extends the base enabled-toggle check with a minimum-amount threshold.
	 * When `min_amount` is set in the user's preferences, the order total must
	 * meet or exceed it for the notification to be sent.
	 *
	 * The threshold is interpreted in the order's currency; no currency
	 * conversion is performed. This mirrors how `WC_Coupon::minimum_amount`
	 * behaves, so multi-currency merchants should set thresholds with that
	 * in mind.
	 *
	 * @param mixed $pref_value The user's stored preference value, or null.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public function should_send_to_user( $pref_value ): bool {
		if ( ! parent::should_send_to_user( $pref_value ) ) {
			return false;
		}

		if ( ! is_array( $pref_value ) || ! isset( $pref_value['min_amount'] ) ) {
			return true;
		}

		$min_amount = (float) $pref_value['min_amount'];
		if ( $min_amount <= 0 ) {
			return true;
		}

		$order = WC()->call_function( 'wc_get_order', $this->get_resource_id() );
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		return (float) $order->get_total() >= $min_amount;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function has_meta( string $key ): bool {
		$order = WC()->call_function( 'wc_get_order', $this->get_resource_id() );
		return $order instanceof WC_Order && $order->meta_exists( $key );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function write_meta( string $key ): void {
		$order = WC()->call_function( 'wc_get_order', $this->get_resource_id() );

		if ( $order instanceof WC_Order ) {
			$order->update_meta_data( $key, (string) time() );
			$order->save_meta_data();
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function delete_meta( string $key ): void {
		$order = WC()->call_function( 'wc_get_order', $this->get_resource_id() );

		if ( $order instanceof WC_Order ) {
			$order->delete_meta_data( $key );
			$order->save_meta_data();
		}
	}
}
