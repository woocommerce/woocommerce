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
}
