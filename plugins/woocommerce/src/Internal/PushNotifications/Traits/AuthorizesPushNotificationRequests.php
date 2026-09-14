<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Traits;

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Rest_Authentication;
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
		$authorized = $this->authorize_as_authenticated_ignoring_enablement( $request );

		if ( true !== $authorized ) {
			return $authorized;
		}

		return wc_get_container()->get( PushNotifications::class )->should_be_enabled();
	}

	/**
	 * Checks the caller is either WPCOM or an allowed user, without requiring
	 * the module to be enabled.
	 *
	 * WPCOM reads this endpoint to decide how to reach a store, and signs those
	 * requests with the Jetpack blog token, which identifies no user. Requiring a
	 * role would reject them.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error
	 *
	 * @since 11.2.0
	 */
	public function authorize_as_from_wpcom_or_allowed_user( WP_REST_Request $request ) {
		if ( $this->is_signed_with_blog_token() ) {
			return true;
		}

		return $this->authorize_as_authenticated_ignoring_enablement( $request );
	}

	/**
	 * Checks the user is authenticated and holds at least one role allowed to
	 * interact with push notifications.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error
	 */
	private function authorize_as_authenticated_ignoring_enablement( WP_REST_Request $request ) {
		if ( ! get_current_user_id() ) {
			return new WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you are not allowed to do that.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return array_reduce(
			PushNotifications::ROLES_WITH_PUSH_NOTIFICATIONS_ENABLED,
			fn ( $carry, $role ) => $this->check_permission( $request, $role ) === true ? true : $carry,
			false
		);
	}

	/**
	 * Determines whether the request is signed with the Jetpack blog token, which
	 * only WPCOM holds.
	 *
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	protected function is_signed_with_blog_token(): bool {
		return class_exists( Rest_Authentication::class ) && Rest_Authentication::is_signed_with_blog_token();
	}
}
