<?php
/**
 * WooCommerce Import Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce Products.
 */
class WC_Products_Tracking {
	/**
	 * Init tracking.
	 */
	public static function init() {
		add_action( 'edit_post', array( __CLASS__, 'track_product_updated' ), 10, 2 );
	}

	/**
	 * Send a Tracks event when a product is updated.
	 *
	 * @param int   $product_id Product id.
	 * @param array $post WordPress post.
	 */
	public static function track_product_updated( $product_id, $post ) {
		if ( 'product' !== $post->post_type ) {
			return;
		}

		$properties = array(
			'product_id' => $product_id,
		);

		WC_Tracks::record_event( 'update_product', $properties );
	}
}
