<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Dispatchers;

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Jetpack_Options;
use WP_Error;
use WP_Http;

/**
 * Sends a notification to WPCOM via the Jetpack connection.
 *
 * Called directly by the NotificationProcessor. Combines the notification
 * payload with formatted push tokens and sends to the WPCOM push endpoint.
 * Returns a result array indicating success/failure and an optional retry-after
 * value.
 *
 * @internal
 * @since 10.7.0
 */
class WpcomNotificationDispatcher {

	/**
	 * WPCOM API version.
	 */
	const WPCOM_API_VERSION = '2';

	/**
	 * WPCOM endpoint path (appended after /sites/{id}/).
	 */
	const SEND_ENDPOINT = 'push-notifications';

	/**
	 * HTTP request timeout in seconds.
	 */
	const REQUEST_TIMEOUT = 15;

	/**
	 * Dispatches a notification with push tokens to WPCOM.
	 *
	 * @param Notification $notification The notification to send.
	 * @param PushToken[]  $tokens       The push tokens to send to.
	 * @return array{success: bool, retry_after: int|null}
	 *
	 * @since 10.7.0
	 */
	public function dispatch( Notification $notification, array $tokens ): array {
		$site_id = class_exists( Jetpack_Options::class ) ? Jetpack_Options::get_option( 'id' ) : null;

		if ( empty( $site_id ) ) {
			wc_get_logger()->error(
				'Cannot send push notifications: Jetpack site ID unavailable.',
				array( 'source' => PushNotifications::FEATURE_NAME )
			);

			return array(
				'success'     => false,
				'retry_after' => null,
			);
		}

		$payload = $notification->to_payload();

		if ( null === $payload ) {
			wc_get_logger()->error(
				sprintf(
					'Cannot send push notification: resource no longer exists (type=%s, resource_id=%d).',
					$notification->get_type(),
					$notification->get_resource_id()
				),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);

			return array(
				'success'     => false,
				'retry_after' => null,
			);
		}

		$response = $this->make_request( $site_id, $payload, $tokens );

		if ( is_wp_error( $response ) ) {
			wc_get_logger()->error(
				sprintf(
					'Push notification request failed: %s',
					$response->get_error_message()
				),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);

			return array(
				'success'     => false,
				'retry_after' => null,
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( WP_Http::OK === $status_code ) {
			return array(
				'success'     => true,
				'retry_after' => null,
			);
		}

		$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );

		wc_get_logger()->error(
			sprintf(
				'Push notification request returned HTTP %d.',
				$status_code
			),
			array( 'source' => PushNotifications::FEATURE_NAME )
		);

		return array(
			'success'     => false,
			'retry_after' => '' !== $retry_after ? (int) $retry_after : null,
		);
	}

	/**
	 * Makes the WPCOM API request via the Jetpack connection.
	 *
	 * @param int         $site_id The Jetpack site ID.
	 * @param array       $payload The notification payload.
	 * @param PushToken[] $tokens  The push tokens.
	 * @return array|WP_Error
	 *
	 * @since 10.7.0
	 *
	 * @phpstan-ignore return.unusedType (Jetpack stubs lack array return type.)
	 */
	private function make_request( int $site_id, array $payload, array $tokens ) {
		$body = wp_json_encode(
			array_merge(
				$payload,
				array(
					'tokens' => array_map(
						fn ( PushToken $token ) => $token->to_wpcom_format(),
						$tokens
					),
				)
			)
		);

		if ( false === $body ) {
			return new WP_Error( 'json_encode_failed', 'Failed to encode push notification payload.' );
		}

		// @phpstan-ignore return.type
		return Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			sprintf( '/sites/%d/%s', $site_id, self::SEND_ENDPOINT ),
			self::WPCOM_API_VERSION,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'method'  => 'POST',
				'timeout' => self::REQUEST_TIMEOUT,
			),
			$body,
			'wpcom'
		);
	}
}
