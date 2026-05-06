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
	 * The icon to use in the notification.
	 */
	const ICON = 'https://s.wp.com/wp-content/mu-plugins/notes/images/update-payment-2x.png';

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
			'icon'        => self::ICON,
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
