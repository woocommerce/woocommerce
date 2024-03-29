<?php

namespace Automattic\WooCommerce\Admin\Features;

use Automattic\WooCommerce\Admin\PageController;

/**
 * Takes care of Launch Your Store related actions.
 */
class LaunchYourStore {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_update_options_general', array( $this, 'save_site_visibility_options' ) );
		add_action( 'current_screen', array( $this, 'maybe_create_coming_soon_page' ) );
	}

	/**
	 * Save values submitted from WooCommerce -> Settings -> General.
	 *
	 * @return void
	 */
	public function save_site_visibility_options() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'woocommerce-settings' ) ) {
			return;
		}

		$options = array(
			'woocommerce_coming_soon'      => array( 'yes', 'no' ),
			'woocommerce_store_pages_only' => array( 'yes', 'no' ),
			'woocommerce_private_link'     => array( 'yes', 'no' ),
		);

		$at_least_one_saved = false;

		foreach ( $options as $name => $option ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( isset( $_POST[ $name ] ) && in_array( $_POST[ $name ], $option, true ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				update_option( $name, wp_unslash( $_POST[ $name ] ) );
				$at_least_one_saved = true;
			}
		}

		if ( $at_least_one_saved ) {
			wc_admin_record_tracks_event( 'site_visibility_saved' );
		}
	}

	/**
	 * Add `coming soon` page when it hasn't been created yet.
	 *
	 * @param WP_Screen $current_screen Current screen object.
	 *
	 * @return void
	 */
	public function maybe_create_coming_soon_page( $current_screen ) {
		$option_name    = 'woocommerce_coming_soon_page_id';
		$current_page   = PageController::get_instance()->get_current_page();
		$is_home        = isset( $current_page['id'] ) && 'woocommerce-home' === $current_page['id'];
		$page_id_option = get_option( $option_name, false );
		if ( $current_screen && 'woocommerce_page_wc-admin' === $current_screen->id && $is_home && ! $page_id_option ) {
			wc_create_page(
				esc_sql( _x( 'Coming Soon', 'Page slug', 'woocommerce' ) ),
				$option_name,
				_x( 'Coming Soon', 'Page title', 'woocommerce' ),
				'tbd',
			);
			// wc_create_page doesn't create options with autoload = yes.
			// Since we'll querying the option on WooCommerce home,
			// we should update the option to set autoload to yes.
			$page_id_option = get_option( $option_name );
			update_option( $option_name, $page_id_option, true );
		}
	}
}
