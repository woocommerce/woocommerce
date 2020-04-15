<?php
/**
 * WooCommerce Import Tracking
 *
 * @package WooCommerce\Tracks
 */

use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce Products.
 */
class WC_Products_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'edit_post', array( $this, 'track_product_updated' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'track_product_published' ), 10, 3 );
		add_action( 'created_product_cat', array( $this, 'track_product_category_created' ) );
		add_action( 'add_meta_boxes_product', array( $this, 'track_product_updated_client_side' ), 10 );
	}

	/**
	 * Send a Tracks event when a product is updated.
	 *
	 * @param int    $product_id Product id.
	 * @param object $post       WordPress post.
	 */
	public function track_product_updated( $product_id, $post ) {
		if ( 'product' !== $post->post_type ) {
			return;
		}

		$properties = array(
			'product_id' => $product_id,
		);

		WC_Tracks::record_event( 'product_edit', $properties );
	}

	/**
	 * Track the Update button being clicked on the client side.
	 * This is needed because `track_product_updated` (using the `edit_post`
	 * hook) is called in response to a number of other triggers.
	 *
	 * @param WP_Post $post The post, not used.
	 */
	public function track_product_updated_client_side( $post ) {
		wc_enqueue_js(
			"
			if ( $( 'h1.wp-heading-inline' ).text().trim() === '" . __( 'Edit product', 'woocommerce' ) . "') {
				var initialStockValue = $( '#_stock' ).val();
				var hasRecordedEvent = false;

				$( '#publish' ).click( function() {
					if ( hasRecordedEvent ) {
						return;
					}

					var currentStockValue = $( '#_stock' ).val();
					var properties = {
						product_type:			$( '#product-type' ).val(),
						is_virtual:				$( '#_virtual' ).is( ':checked' ) ? 'Y' : 'N',
						is_downloadable:		$( '#_downloadable' ).is( ':checked' ) ? 'Y' : 'N',
						manage_stock:			$( '#_manage_stock' ).is( ':checked' ) ? 'Y' : 'N',
						stock_quantity_update:	( initialStockValue != currentStockValue ) ? 'Y' : 'N',
					};

					window.wcTracks.recordEvent( 'product_update', properties );
					hasRecordedEvent = true;
				} );
			}
			"
		);
	}

	/**
	 * Send a Tracks event when a product is published.
	 *
	 * @param string $new_status New post_status.
	 * @param string $old_status Previous post_status.
	 * @param object $post WordPress post.
	 */
	public function track_product_published( $new_status, $old_status, $post ) {
		if (
			'product' !== $post->post_type ||
			'publish' !== $new_status ||
			'publish' === $old_status
		) {
			return;
		}

		$properties = array(
			'product_id' => $post->ID,
		);

		WC_Tracks::record_event( 'product_add_publish', $properties );
	}

	/**
	 * Send a Tracks event when a product category is created.
	 *
	 * @param int $category_id Category ID.
	 */
	public function track_product_category_created( $category_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Only track category creation from the edit product screen or the
		// category management screen (which both occur via AJAX).
		if (
			! Constants::is_defined( 'DOING_AJAX' ) ||
			empty( $_POST['action'] ) ||
			(
				// Product Categories screen.
				'add-tag' !== $_POST['action'] &&
				// Edit Product screen.
				'add-product_cat' !== $_POST['action']
			)
		) {
			return;
		}

		$category   = get_term( $category_id, 'product_cat' );
		$properties = array(
			'category_id' => $category_id,
			'parent_id'   => $category->parent,
			'page'        => ( 'add-tag' === $_POST['action'] ) ? 'categories' : 'product',
		);
		// phpcs:enable

		WC_Tracks::record_event( 'product_category_add', $properties );
	}
}
