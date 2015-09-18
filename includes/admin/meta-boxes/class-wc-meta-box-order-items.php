<?php
/**
 * Order Data
 *
 * Functions for displaying the order items meta box.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Order_Items Class
 */
class WC_Meta_Box_Order_Items {

	/**
	 * Output the metabox
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

		include( 'views/html-order-items.php' );
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		wc_save_order_items( $post_id, $_POST );
	}
}
