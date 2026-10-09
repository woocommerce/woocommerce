<?php
/**
 * Shared REST permission checks.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Shared REST permission checks.
 */
class RESTPermissions {

	/**
	 * Require a logged in user.
	 *
	 * The shared authentication floor for customer-facing routes: any logged-in user
	 * passes; resource-level authorization (e.g. per-contract ownership) stays with the
	 * route handlers. Core's cookie auth has already verified the REST nonce (`wp_rest`)
	 * for a cookie-authenticated request by the time a permission callback runs.
	 *
	 * @return true|\WP_Error True when logged in, else a 401 error.
	 */
	public function require_logged_in_permission() {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'woocommerce_subscriptions_engine_not_authenticated',
				__( 'You must be logged in to access this resource.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Require a logged in user with the manage_woocommerce capability.
	 *
	 * @return true|\WP_Error
	 */
	public function require_admin_permission() {
		$logged_in = $this->require_logged_in_permission();
		if ( true !== $logged_in ) {
			return $logged_in;
		}

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers manage_woocommerce.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'woocommerce_subscriptions_engine_insufficient_permissions',
				__( 'Sorry, you are not allowed to access this resource.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
