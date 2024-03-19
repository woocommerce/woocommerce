<?php

namespace Automattic\WooCommerce\Admin\Features;

/**
 * Core Profiler related taks
 */
class CoreProfiler {
	/**
	 * Construct
	 */
	public function __construct() {
		add_action( 'current_screen', array( $this, 'maybe_create_coming_soon_page' ) );
	}

	/**
	 * Add `coming soon` page when it hasn't been created yet.
	 *
	 * @param WP_Screen $current_screen Current screen object.
	 *
	 * @return void
	 */
	public function maybe_create_coming_soon_page( $current_screen ) {
		// phpcs:ignore
		$is_welcome_page = isset( $_GET['path'] ) && '/setup-wizard' === $_GET['path'];
		$page_id_option  = get_option( 'woocommerce_coming_soon_page_id', false );
		if ( $current_screen && 'woocommerce_page_wc-admin' === $current_screen->i[d && $is_welcome_page && ! $page_id_option ) {
			wc_create_page(
				esc_sql( _x( 'Coming Soon', 'Page slug', 'woocommerce' ) ),
				'woocommerce_coming_soon_page_id',
				_x( 'Coming Soon', 'Page title', 'woocommerce' ),
				'tbd',
			);
		}
	}
}
