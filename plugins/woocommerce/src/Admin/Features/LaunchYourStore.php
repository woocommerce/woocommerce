<?php

namespace Automattic\WooCommerce\Admin\Features;

/**
 * Takes care of Launch Your Store related actions.
 */
class LaunchYourStore {
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
			'woocommerce_lys_setting_coming_soon' => ['yes', 'no'],
			'woocommerce_lys_setting_store_pages_only' => ['yes', 'no'],
			'woocommerce_lys_setting_private_link' => ['yes', 'no']
		);

		foreach ( $options as $name => $option ) {
			if ( isset( $_POST[ $name ] ) && in_array( $_POST[ $name ], $option ) ) {
				update_option( $name, $_POST[ $name ] );
			}
		}
	}
}
