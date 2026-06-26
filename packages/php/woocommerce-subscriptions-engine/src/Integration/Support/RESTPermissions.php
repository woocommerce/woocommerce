<?php
/**
 * Shared admin REST permission checks.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Shared admin REST permission checks.
 */
class RESTPermissions {

	/**
	 * Require a logged in user with the manage_woocommerce capability.
	 *
	 * @return true|\WP_Error
	 */
	public function require_admin_permission() {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'woocommerce_subscriptions_engine_not_authenticated',
				__( 'You must be logged in to access this resource.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => 401 )
			);
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
