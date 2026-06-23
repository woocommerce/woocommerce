<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Traits;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use WP_Error;
use WP_REST_Request;

/**
 * Shared "is this caller an authenticated push-notifications user?" check for
 * REST controllers in the PushNotifications module.
 *
 * Implementing classes must extend {@see \Automattic\WooCommerce\Internal\RestApiControllerBase}
 * so that `check_permission()` is available.
 */
trait AuthorizesPushNotificationRequests {
	/**
	 * Checks the user is authenticated, the push notifications module is
	 * enabled, and the user holds at least one role allowed to interact with
	 * push notifications.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error
	 */
	public function authorize_as_authenticated( WP_REST_Request $request ) {
		if ( ! get_current_user_id() ) {
			return new WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you are not allowed to do that.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! wc_get_container()->get( PushNotifications::class )->should_be_enabled() ) {
			return false;
		}

		$has_valid_role = array_reduce(
			PushNotifications::ROLES_WITH_PUSH_NOTIFICATIONS_ENABLED,
			fn ( $carry, $role ) => $this->check_permission( $request, $role ) === true ? true : $carry,
			false
		);

		return $has_valid_role ? true : false;
	}
}
