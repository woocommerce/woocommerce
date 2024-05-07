<?php
/**
 * REST API Launch Your Store Controller
 *
 * Handles requests to /launch-your-store/*
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\WCAdminHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Launch Your Store controller.
 *
 * @internal
 */
class LaunchYourStore {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'launch-your-store';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/initialize-coming-soon',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'initialize_coming_soon' ),
					'permission_callback' => array( $this, 'must_be_shop_manager_or_admin' ),
				),
			)
		);
	}

	/**
	 * User must be either shop_manager or administrator.
	 *
	 * @return bool
	 */
	public function must_be_shop_manager_or_admin() {
		// phpcs:ignore
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'administrator' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Initializes options for coming soon. Does not override if options exist.
	 *
	 * @return bool|void
	 */
	public function initialize_coming_soon() {
		$current_user_id = get_current_user_id();
		// Abort if we don't have a user id for some reason.
		if ( ! $current_user_id ) {
			return;
		}

		$coming_soon      = 'yes';
		$store_pages_only = WCAdminHelper::is_site_fresh() ? 'no' : 'yes';
		$private_link     = 'no';
		$share_key        = wp_generate_password( 32, false );

		add_option( 'woocommerce_coming_soon', $coming_soon );
		add_option( 'woocommerce_store_pages_only', $store_pages_only );
		add_option( 'woocommerce_private_link', $private_link );
		add_option( 'woocommerce_share_key', $share_key );

		wc_admin_record_tracks_event(
			'launch_your_store_initialize_coming_soon',
			array(
				'coming_soon'      => $coming_soon,
				'store_pages_only' => $store_pages_only,
				'private_link'     => $private_link,
			)
		);

		return true;
	}
}
