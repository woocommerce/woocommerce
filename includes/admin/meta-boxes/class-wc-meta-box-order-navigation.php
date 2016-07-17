<?php
/**
 * Order Navigation
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Meta Boxes
 * @version
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Order_Navigation Class.
 */
class WC_Meta_Box_Order_Navigation {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $post, $wpdb, $theorder;

		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $post->ID );
		}

		$order_type_object = get_post_type_object( $post->post_type );

		$order_navigation = $wpdb->get_row( $wpdb->prepare( "
			SELECT
				(SELECT ID FROM {$wpdb->prefix}posts
				WHERE ID < %d
				AND post_type = '%s'
				AND post_status <> 'trash'
				ORDER BY ID DESC LIMIT 1 )
				AS prev_order_id,
				(SELECT ID FROM {$wpdb->prefix}posts
				WHERE ID > %d
				AND post_type = '%s'
				AND post_status <> 'trash'
				ORDER BY ID ASC LIMIT 1 )
				AS next_order_id
		", $post->ID, $post->post_type, $post->ID, $post->post_type ), ARRAY_A );

		include( 'views/html-order-navigation.php' );
	}
}
