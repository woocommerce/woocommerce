<?php
/**
 * Nosara Tracks for WooCommerce
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce.
 */
class WC_Site_Tracking {

	/**
	 * Send a Tracks event when a product is updated.
	 *
	 * @param int   $product_id Product id.
	 * @param array $post WordPress post.
	 */
	public static function tracks_product_updated( $product_id, $post ) {
		if ( 'product' !== $post->post_type ) {
			return;
		}

		$properties = array(
			'product_id' => $product_id,
		);

		WC_Tracks::record_event( 'product_edit', $properties );
	}

	/**
	 * Route product importer action to the right callback.
	 *
	 * @return void
	 */
	public static function tracks_product_importer() {
		if ( ! isset( $_REQUEST['step'] ) ) {
			return;
		}

		if ( 'import' === $_REQUEST['step'] ) {
			return self::tracks_product_importer_start();
		}

		if ( 'done' === $_REQUEST['step'] ) {
			return self::tracks_product_importer_complete();
		}
	}

	/**
	 * Send a Tracks event when the product importer is started.
	 *
	 * @return void
	 */
	public static function tracks_product_importer_start() {
		if ( ! isset( $_REQUEST['file'] ) || ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		$properties = array(
			'update_existing' => isset( $_REQUEST['update_existing'] ) ? (bool) $_REQUEST['update_existing'] : false,
			'delimiter'       => empty( $_REQUEST['delimiter'] ) ? ',' : wc_clean( wp_unslash( $_REQUEST['delimiter'] ) ),
		);

		WC_Tracks::record_event( 'product_import_start', $properties );
	}

	/**
	 * Send a Tracks event when the product importer has finished.
	 *
	 * @return void
	 */
	public static function tracks_product_importer_complete() {
		if ( ! isset( $_REQUEST['nonce'] ) ) {
			return;
		}

		$properties = array(
			'imported' => isset( $_GET['products-imported'] ) ? absint( $_GET['products-imported'] ) : 0,
			'updated'  => isset( $_GET['products-updated'] ) ? absint( $_GET['products-updated'] ) : 0,
			'failed'   => isset( $_GET['products-failed'] ) ? absint( $_GET['products-failed'] ) : 0,
			'skipped'  => isset( $_GET['products-skipped'] ) ? absint( $_GET['products-skipped'] ) : 0,
		);

		WC_Tracks::record_event( 'product_import_complete', $properties );
	}

	/**
	 * Check if tracking is enabled.
	 *
	 * @return bool
	 */
	public static function is_tracking_enabled() {
		/**
		 * Don't track users who haven't opted-in to tracking or if a filter
		 * has been applied to turn it off.
		 */
		if ( 'yes' !== get_option( 'woocommerce_allow_tracking' ) ||
			! apply_filters( 'woocommerce_apply_user_tracking', true ) ) {
			return false;
		}

		if ( ! class_exists( 'WC_Tracks' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add scripts required to record events from javascript.
	 */
	public static function enqueue_scripts() {

		// Add w.js to the page.
		wp_enqueue_script( 'woo-tracks', 'https://stats.wp.com/w.js', array(), gmdate( 'YW' ), true );

		// Expose tracking via a function in the wcTracks global namespace.
		wc_enqueue_js(
			"
			window.wcTracks = window.wcTracks || {};
			window.wcTracks.recordEvent = function( name, properties ) {
				var eventName = '" . WC_Tracks::PREFIX . "' + name;
				var eventProperties = properties || {};
				eventProperties.url = '" . home_url() . "'
				window._tkq = window._tkq || [];
				window._tkq.push( [ 'recordEvent', eventName, eventProperties ] );
			}
		"
		);
	}

	/**
	 * Init tracking.
	 */
	public static function init() {

		if ( ! self::is_tracking_enabled() ) {

			// Define window.wcTracks.recordEvent in case there is an attempt to use it when tracking is turned off.
			wc_enqueue_js(
				'
				window.wcTracks = window.wcTracks || {};
				window.wcTracks.recordEvent = function() {};
			'
			);

			return;
		}

		self::enqueue_scripts();

		add_action( 'edit_post', array( 'WC_Site_Tracking', 'tracks_product_updated' ), 10, 2 );
		add_action( 'product_page_product_importer', array( __CLASS__, 'tracks_product_importer' ) );
	}
}
