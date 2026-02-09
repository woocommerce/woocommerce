<?php
/**
 * WooCommerce Status Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce Orders.
 */
class WC_Status_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'track_status_view' ), 10 );
	}

	/**
	 * Add Tracks events to the status page.
	 */
	public function track_status_view() {
		if ( isset( $_GET['page'] ) && 'wc-status' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {

			$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'status';

			WC_Tracks::record_event(
				'status_view',
				array(
					'tab'       => $tab,
					'tool_used' => isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : null,
				)
			);

			if ( 'status' === $tab ) {
				$handle = 'wc-tracks-status-view';
				wp_register_script( $handle, '', array(), WC_VERSION, array( 'in_footer' => true ) );
				wp_enqueue_script( $handle );
				wp_add_inline_script(
					$handle,
					"
			            (function() {
			                'use strict';
			                const debugReportLink = document.querySelector( 'a.debug-report' );
			                if ( debugReportLink ) {
			                    debugReportLink.addEventListener( 'click', function() {
			                        if ( window.wcTracks && window.wcTracks.recordEvent ) {
			                            window.wcTracks.recordEvent( 'status_view_reports' );
			                        }
			                    } );
			                }
			            })();
                    "
				);
			}
		}
	}
}
