<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Dispatchers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;

/**
 * Fires a non-blocking POST to the internal REST endpoint with JSON-encoded
 * notification data and a signed JWT.
 *
 * Called directly by PendingNotificationStore::dispatch_all() on shutdown.
 *
 * @internal
 * @since 10.7.0
 */
class InternalNotificationDispatcher {

	/**
	 * REST route for the send endpoint.
	 */
	const SEND_ENDPOINT = 'wc-push-notifications/send';

	/**
	 * Query parameter carrying the JWT as a fallback credential.
	 *
	 * Some hosting setups (e.g. nginx to PHP-FPM, or Plesk's nginx-to-Apache
	 * proxying) do not pass the `Authorization` header through to PHP. On
	 * those hosts a header-only credential silently fails and every
	 * notification degrades to the slower ActionScheduler safety net. The
	 * same token is therefore also sent in the URL, where nothing strips it.
	 */
	const TOKEN_QUERY_PARAM = 'wcpn_token';

	/**
	 * JWT expiry in seconds.
	 */
	const JWT_EXPIRY_SECONDS = 30;

	/**
	 * JSON-encodes notifications and fires a non-blocking POST to the internal
	 * REST endpoint.
	 *
	 * @param Notification[] $notifications The notifications to dispatch.
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function dispatch( array $notifications ): void {
		if ( empty( $notifications ) ) {
			return;
		}

		$encoded = array_map( fn ( Notification $notification ) => $notification->to_array(), $notifications );
		$body    = wp_json_encode( array( 'notifications' => $encoded ) );

		if ( false === $body ) {
			wc_get_logger()->error(
				'Failed to JSON-encode push notification payload.',
				array( 'source' => PushNotifications::FEATURE_NAME )
			);
			return;
		}

		$token = JsonWebToken::create(
			array(
				'iss'       => get_site_url(),
				'exp'       => time() + self::JWT_EXPIRY_SECONDS,
				'body_hash' => hash( 'sha256', $body ),
			),
			wp_salt( 'auth' )
		);

		/**
		 * The request is non-blocking so the response is not handled anywhere.
		 * If the request fails, the ActionScheduler safety net will pick up
		 * unsent notifications after 60 seconds.
		 *
		 * The token travels both as an Authorization header and as a query
		 * parameter: the receiver prefers the header and falls back to the
		 * parameter on hosts that strip the header (see TOKEN_QUERY_PARAM).
		 */
		wp_remote_post(
			add_query_arg( self::TOKEN_QUERY_PARAM, $token, rest_url( self::SEND_ENDPOINT ) ),
			array(
				'blocking' => false,
				'timeout'  => 1,
				'headers'  => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
				'body'     => $body,
			)
		);
	}
}
