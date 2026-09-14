<?php
/**
 * WooCommerce Coupons Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce Orders.
 */
class WC_Coupons_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'load-edit.php', array( $this, 'tracks_coupons_events' ), 10 );
	}

	/**
	 * Enqueue JS to handle tracking for bulk editing of coupons.
	 *
	 * @return void
	 */
	public function tracks_coupons_bulk_actions() {
		$handle = 'wc-tracks-coupons-bulk-actions';
		wp_register_script( $handle, '', array(), WC_VERSION, array( 'in_footer' => true ) );
		wp_enqueue_script( $handle );
		wp_add_inline_script(
			$handle,
			"
				(function() {
				    'use strict';

				    function trackBulkAction( selectorId ) {
				        return function() {
				            const select = document.getElementById( selectorId );
				            const action = select ? select.value : null;

				            if ( action && '-1' !== action && window.wcTracks && window.wcTracks.recordEvent ) {
				                window.wcTracks.recordEvent( 'coupons_view_bulk_action', { action: action } );
				            }
				        };
				    }

				    const topButton = document.getElementById( 'doaction' );
				    const bottomButton = document.getElementById( 'doaction2' );

				    if ( topButton ) {
				        topButton.addEventListener( 'click', trackBulkAction( 'bulk-action-selector-top' ) );
				    }

				    if ( bottomButton ) {
				        bottomButton.addEventListener( 'click', trackBulkAction( 'bulk-action-selector-bottom' ) );
				    }
				})();
			"
		);
	}

	/**
	 * Track page view events.
	 */
	public function tracks_coupons_events() {
		if ( isset( $_GET['post_type'] ) && 'shop_coupon' === $_GET['post_type'] ) {

			$this->tracks_coupons_bulk_actions();

			WC_Tracks::record_event(
				'coupons_view',
				array(
					'status' => isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : 'all',
				)
			);

			if ( isset( $_GET['filter_action'] ) && 'Filter' === sanitize_text_field( wp_unslash( $_GET['filter_action'] ) ) && isset( $_GET['coupon_type'] ) ) {
				WC_Tracks::record_event(
					'coupons_filter',
					array(
						'filter' => 'coupon_type',
						'value'  => sanitize_text_field( wp_unslash( $_GET['coupon_type'] ) ),
					)
				);
			}

			if ( isset( $_GET['s'] ) && 0 < strlen( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) ) {
				WC_Tracks::record_event( 'coupons_search' );
			}
		}
	}
}
