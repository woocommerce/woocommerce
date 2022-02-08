<?php
/**
 * Order Data
 *
 * Functions for displaying the order items meta box.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce\Admin\Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Order_Items Class.
 */
class WC_Meta_Box_Order_Items {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $post, $thepostid, $theorder;

		if ( ! is_int( $thepostid ) ) {
			$thepostid = $post->ID;
		}

		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $thepostid );
		}

		$order = $theorder;
		$data  = get_post_meta( $post->ID );

		/**
		 * Allow plugins to determine whether refunds UI should be rendered in the template.
		 *
		 * @since 6.3.0
		 *
		 * @param $order_id The Order ID.
		 * @param $order The Order object.
		 * @return bool
		 */
		$should_render_refunds = (bool) apply_filters( 'woocommerce_admin_order_should_render_refunds', 0 < $order->get_total() - $order->get_total_refunded() || 0 < absint( $order->get_item_count() - $order->get_item_count_refunded() ), $order->get_id(), $order );

		include __DIR__ . '/views/html-order-items.php';
	}gi

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id ) {
		/**
		 * This $_POST variable's data has been validated and escaped
		 * inside `wc_save_order_items()` function.
		 */
		wc_save_order_items( $post_id, $_POST );
	}
}
