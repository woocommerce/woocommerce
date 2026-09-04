<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Orders;

/**
 * Creates orders opened from an admin Add Order screen.
 */
class AdminOrderCreator {

	/**
	 * Create an order initialized for editing in the admin.
	 *
	 * @internal
	 *
	 * @param string $order_type Order type.
	 * @param int    $post_author Optional post author to use with the CPT data store.
	 * @return \WC_Order|null Created order, or null when the order type cannot be created.
	 */
	public function create_order( string $order_type, int $post_author = 0 ) {
		$order_type_definition = wc_get_order_type( $order_type );
		$order_class_name      = $order_type_definition['class_name'] ?? '';

		if ( ! $order_class_name || ! class_exists( $order_class_name ) ) {
			return null;
		}

		/** @var class-string<\WC_Order> $order_class_name */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
		$order = new $order_class_name();
		if ( $order_type !== $order->get_type() ) {
			return null;
		}

		$order->set_object_read( false );
		$order->set_status( 'auto-draft' );
		$order->set_created_via( 'admin' );
		$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );

		$set_post_author = static function ( array $order_data ) use ( $post_author ): array {
			$order_data['post_author'] = $post_author;
			return $order_data;
		};
		if ( $post_author ) {
			add_filter( 'woocommerce_new_order_data', $set_post_author );
		}

		try {
			$order_id = $order->save();
		} finally {
			if ( $post_author ) {
				remove_filter( 'woocommerce_new_order_data', $set_post_author );
			}
		}

		if ( ! $order_id ) {
			return null;
		}

		// Schedule auto-draft cleanup. We re-use the WP event here on purpose.
		if ( ! wp_next_scheduled( 'wp_scheduled_auto_draft_delete' ) ) {
			wp_schedule_event( time(), 'daily', 'wp_scheduled_auto_draft_delete' );
		}

		return $order;
	}
}
