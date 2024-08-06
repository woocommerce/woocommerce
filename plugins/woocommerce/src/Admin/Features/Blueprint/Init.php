<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

use Automattic\WooCommerce\Admin\PageController;

/**
 * Class Init
 *
 * This class initializes the Blueprint feature for WooCommerce.
 */
class Init {
	/**
	 * Init constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'init_rest_api' ) );
		add_filter( 'woocommerce_admin_shared_settings', array( $this, 'add_upload_nonce_to_settings' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function init_rest_api() {
		( new RestApi() )->register_routes();
	}


	/**
	 * Add upload nonce to global JS settings.
	 *
	 * The value can be accessed at wcSettings.admin.blueprint_upload_nonce
	 *
	 * @param array $settings Global JS settings.
	 *
	 * @return array
	 */
	public function add_upload_nonce_to_settings( array $settings ) {
		if ( ! is_admin() ) {
			return $settings;
		}

		$page_id = PageController::get_instance()->get_current_screen_id();
		if ( 'woocommerce_page_wc-admin' === $page_id ) {
			$settings['blueprint_upload_nonce'] = wp_create_nonce( 'blueprint_upload_nonce' );
			return $settings;
		}

		return $settings;
	}
}
