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
		$options = array(
			'woocommerce_coming_soon'      => array( 'yes', 'no' ),
			'woocommerce_store_pages_only' => array( 'yes', 'no' ),
			'woocommerce_private_link'     => array( 'yes', 'no' ),
		);

		foreach ( $options as $name => $option ) {
			if ( isset( $_POST[ $name ] ) && in_array( $_POST[ $name ], $option, true ) ) {
				update_option( $name, $_POST[ $name ] );
			}
		}
	}
}
