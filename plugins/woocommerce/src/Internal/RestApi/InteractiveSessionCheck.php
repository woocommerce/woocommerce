<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi;

use WP_Error;
use WP_REST_Request;

/**
 * Checks that a REST request comes from an interactive admin session.
 *
 * @since 11.2.2
 */
final class InteractiveSessionCheck {

	/**
	 * Check that the current user was authenticated by cookie and that the
	 * request carries a valid `wp_rest` nonce, as a `_wpnonce` parameter or
	 * an `X-WP-Nonce` header.
	 *
	 * @since 11.2.2
	 *
	 * @global mixed $wp_rest_auth_cookie True when core validated an auth cookie for this request.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request WP_REST_Request object.
	 * @return WP_Error|true
	 */
	public static function require_interactive_session( $request ) {
		global $wp_rest_auth_cookie;

		$nonce = $request->get_param( '_wpnonce' ) ?? $request->get_header( 'X-WP-Nonce' );

		if ( ! is_user_logged_in() || true !== $wp_rest_auth_cookie || ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'woocommerce_rest_missing_session',
				__( 'Sorry, you cannot perform this action without an interactive admin session.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}
}
