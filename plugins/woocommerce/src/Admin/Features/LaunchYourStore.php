<?php

namespace Automattic\WooCommerce\Admin\Features;

class LaunchYourStore {
	public function __construct() {
		add_action( 'woocommerce_update_options_general', array( $this, 'save_site_visibility_options' ) );
	}

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
