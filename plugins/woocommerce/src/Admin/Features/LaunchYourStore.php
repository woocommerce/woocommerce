<?php

namespace Automattic\WooCommerce\Admin\Features;

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

		foreach ( $options as $name => $option ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( isset( $_POST[ $name ] ) && in_array( $_POST[ $name ], $option, true ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				update_option( $name, wp_unslash( $_POST[ $name ] ) );
			}
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
		// phpcs:ignore
		$is_welcome_page = isset( $_GET['path'] ) && '/setup-wizard' === $_GET['path'];
		$page_id_option  = get_option( 'woocommerce_coming_soon_page_id', false );
		if ( $current_screen && 'woocommerce_page_wc-admin' === $current_screen->id && $is_welcome_page && ! $page_id_option ) {
			wc_create_page(
				esc_sql( _x( 'Coming Soon', 'Page slug', 'woocommerce' ) ),
				'woocommerce_coming_soon_page_id',
				_x( 'Coming Soon', 'Page title', 'woocommerce' ),
				'tbd',
			);
		}
	}
}
