<?php
/**
 * REST API Launch Your Store Controller
 *
 * Handles requests to /launch-your-store/*
 */

namespace Automattic\WooCommerce\Admin\API;

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
			'/' . $this->rest_base . '/dismiss-coming-soon-banner',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'dismiss_coming_soon_banner' ),
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
	 * Update woocommerce_coming_soon_banner_dismissed to 'yes'.
	 *
	 * @return bool|void
	 */
	public function dismiss_coming_soon_banner() {
		$current_user_id = get_current_user_id();
		// Abort if we don't have a user id for some reason.
		if ( ! $current_user_id ) {
			return;
		}

		update_user_meta( $current_user_id, 'woocommerce_coming_soon_banner_dismissed', 'yes' );

		return true;
	}
}
