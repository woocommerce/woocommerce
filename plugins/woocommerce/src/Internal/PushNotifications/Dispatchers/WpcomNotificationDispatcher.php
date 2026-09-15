<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Dispatchers;

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationStepLogger;
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
	 * Outcomes of a send, as WPCOM reports them. The endpoint queues every
	 * token or none, so one outcome describes the whole batch.
	 */
	const OUTCOME_ACCEPTED                      = 'accepted';
	const OUTCOME_DEDUPLICATED                  = 'deduplicated';
	const OUTCOME_REJECTED_INVALID_TOKEN        = 'rejected_invalid_token';
	const OUTCOME_REJECTED_INVALID_NOTIFICATION = 'rejected_invalid_notification';
	const OUTCOME_SITE_ID_MISSING               = 'site_id_missing';
	const OUTCOME_RESOURCE_MISSING              = 'resource_missing';
	const OUTCOME_REQUEST_FAILED                = 'request_failed';
	const OUTCOME_FAILED                        = 'failed';

	/**
	 * Error code WPCOM returns when it refused the batch for a rejected token.
	 */
	const WPCOM_ERROR_INVALID_TOKENS = 'invalid_tokens';

	/**
	 * Error code WPCOM returns when the notification itself failed validation.
	 */
	const WPCOM_ERROR_INVALID_PARAM = 'rest_invalid_param';

	/**
	 * The step logger.
	 *
	 * @var NotificationStepLogger
	 */
	private NotificationStepLogger $step_logger;

	/**
	 * Initialize injected dependencies.
	 *
	 * @internal
	 *
	 * @param NotificationStepLogger $step_logger The step logger.
	 *
	 * @since 11.3.0
	 */
	final public function init( NotificationStepLogger $step_logger ): void {
		$this->step_logger = $step_logger;
	}

	/**
	 * Dispatches a notification with push tokens to WPCOM.
	 *
	 * `invalid_tokens` holds the token strings WPCOM refused, exactly as sent,
	 * when the outcome is a token rejection; it is empty otherwise.
	 *
	 * @param Notification $notification The notification to send.
	 * @param PushToken[]  $tokens       The push tokens to send to.
	 * @return array{success: bool, retry_after: int|null, outcome: string, invalid_tokens: string[]}
	 *
	 * @since 10.7.0
	 */
	public function dispatch( Notification $notification, array $tokens ): array {
		$site_id = class_exists( Jetpack_Options::class ) ? Jetpack_Options::get_option( 'id' ) : null;

		if ( empty( $site_id ) ) {
			$this->step_logger->log_failure(
				$notification,
				'send',
				self::OUTCOME_SITE_ID_MISSING,
				'error',
				'Cannot send push notifications: Jetpack site ID unavailable.'
			);

			return self::failure( self::OUTCOME_SITE_ID_MISSING );
		}

		$payload = $notification->to_payload();

		if ( null === $payload ) {
			$this->step_logger->log_failure(
				$notification,
				'send',
				self::OUTCOME_RESOURCE_MISSING,
				'error',
				sprintf(
					'Cannot send push notification: resource no longer exists (type=%s, resource_id=%d).',
					$notification->get_type(),
					$notification->get_resource_id()
				)
			);

			return self::failure( self::OUTCOME_RESOURCE_MISSING );
		}

		$response = $this->make_request( $site_id, $payload, $tokens );

		if ( is_wp_error( $response ) ) {
			$this->step_logger->log_failure(
				$notification,
				'send',
				self::OUTCOME_REQUEST_FAILED,
				'error',
				sprintf( 'Push notification request failed: %s', $response->get_error_message() ),
				array( 'error_code' => $response->get_error_code() )
			);

			return self::failure( self::OUTCOME_REQUEST_FAILED );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$body        = is_array( $body ) ? $body : array();

		if ( WP_Http::OK === $status_code ) {
			$queued       = (int) ( $body['queued'] ?? 0 );
			$deduplicated = (int) ( $body['deduplicated'] ?? 0 );
			$outcome      = $queued > 0 ? self::OUTCOME_ACCEPTED : self::OUTCOME_DEDUPLICATED;

			$this->step_logger->log_notification_step(
				$notification,
				'send',
				$outcome,
				array(
					'recipients'   => count( $tokens ),
					'queued'       => $queued,
					'deduplicated' => $deduplicated,
				)
			);

			return array(
				'success'        => true,
				'retry_after'    => null,
				'outcome'        => $outcome,
				'invalid_tokens' => array(),
			);
		}

		$retry_after    = wp_remote_retrieve_header( $response, 'retry-after' );
		$error_code     = (string) ( $body['code'] ?? '' );
		$invalid_tokens = array();
		$outcome        = self::OUTCOME_FAILED;

		if ( self::WPCOM_ERROR_INVALID_TOKENS === $error_code ) {
			$outcome        = self::OUTCOME_REJECTED_INVALID_TOKEN;
			$invalid_tokens = array_values( array_filter( (array) ( $body['data']['invalid_tokens'] ?? array() ), 'is_string' ) );
		} elseif ( self::WPCOM_ERROR_INVALID_PARAM === $error_code ) {
			$outcome = self::OUTCOME_REJECTED_INVALID_NOTIFICATION;
		}

		$this->step_logger->log_failure(
			$notification,
			'send',
			$outcome,
			'error',
			sprintf( 'Push notification request returned HTTP %d.', $status_code ),
			array(
				'http_status'    => $status_code,
				'error_code'     => $error_code,
				'recipients'     => count( $tokens ),
				'invalid_tokens' => count( $invalid_tokens ),
				'retry_after'    => '' !== $retry_after ? (int) $retry_after : null,
			)
		);

		return array(
			'success'        => false,
			'retry_after'    => '' !== $retry_after ? (int) $retry_after : null,
			'outcome'        => $outcome,
			'invalid_tokens' => $invalid_tokens,
		);
	}

	/**
	 * Builds the return value for a send that failed before or during the request.
	 *
	 * @param string $outcome One of the OUTCOME_* constants.
	 * @return array{success: bool, retry_after: int|null, outcome: string, invalid_tokens: string[]}
	 */
	private static function failure( string $outcome ): array {
		return array(
			'success'        => false,
			'retry_after'    => null,
			'outcome'        => $outcome,
			'invalid_tokens' => array(),
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
