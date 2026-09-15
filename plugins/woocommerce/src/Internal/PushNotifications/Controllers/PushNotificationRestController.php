<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Controllers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationStepLogger;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use Exception;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for the internal loopback send endpoint.
 *
 * Receives JWT-signed notification data from InternalNotificationDispatcher
 * and delegates each notification to NotificationProcessor.
 *
 * @since 10.7.0
 */
class PushNotificationRestController {

	/**
	 * The route namespace, shared with PushTokenRestController.
	 */
	const ROUTE_NAMESPACE = 'wc-push-notifications';

	/**
	 * Reasons a loopback request can fail authorization.
	 */
	const AUTH_FAILURE_CREDENTIAL_MISSING = 'credential_missing';
	const AUTH_FAILURE_TOKEN_INVALID      = 'token_invalid';
	const AUTH_FAILURE_ISSUER_INVALID     = 'issuer_invalid';
	const AUTH_FAILURE_BODY_HASH_MISMATCH = 'body_hash_mismatch';

	/**
	 * The response message for each authorization failure.
	 */
	const AUTH_FAILURE_MESSAGES = array(
		self::AUTH_FAILURE_CREDENTIAL_MISSING => 'Missing authorization header.',
		self::AUTH_FAILURE_TOKEN_INVALID      => 'Invalid or expired token.',
		self::AUTH_FAILURE_ISSUER_INVALID     => 'Invalid token issuer.',
		self::AUTH_FAILURE_BODY_HASH_MISMATCH => 'Body hash mismatch.',
	);

	/**
	 * Registers the REST API route on the rest_api_init hook.
	 *
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the send route.
	 *
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function register_routes(): void {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'send',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create' ),
				'permission_callback' => array( $this, 'authorize' ),
			)
		);
	}

	/**
	 * Processes the send request by delegating each notification to the
	 * processor.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 10.7.0
	 */
	public function create( WP_REST_Request $request ) {
		wc_set_time_limit( 30 );

		$body             = json_decode( $request->get_body(), true );
		$notifications    = is_array( $body ) ? ( $body['notifications'] ?? array() ) : array();
		$success_response = new WP_REST_Response( array( 'success' => true ), WP_Http::OK );

		$step_logger = wc_get_container()->get( NotificationStepLogger::class );

		if ( empty( $notifications ) || ! is_array( $notifications ) ) {
			$step_logger->log_unattributed_failure(
				'received',
				'malformed_body',
				'warning',
				'Loopback endpoint received empty or missing notifications array.'
			);

			return $success_response;
		}

		$processor = wc_get_container()->get( NotificationProcessor::class );

		foreach ( $notifications as $data ) {
			try {
				$notification = Notification::from_array( $data );
			} catch ( Exception $e ) {
				$step_logger->log_unattributed_failure(
					'received',
					'invalid_notification',
					'error',
					sprintf( 'Failed to process notification: %s', $e->getMessage() ),
					array(
						'type'        => is_array( $data ) ? (string) ( $data['type'] ?? '' ) : '',
						'resource_id' => is_array( $data ) ? (int) ( $data['resource_id'] ?? 0 ) : 0,
					)
				);
				continue;
			}

			$step_logger->log_notification_step( $notification, 'received', 'ok' );

			try {
				$processor->process( $notification );
			} catch ( Exception $e ) {
				$step_logger->log_failure(
					$notification,
					'processing',
					'exception',
					'error',
					sprintf( 'Failed to process notification: %s', $e->getMessage() )
				);
			}
		}

		return $success_response;
	}

	/**
	 * Validates the JWT from the Authorization header.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return true|WP_Error
	 *
	 * @since 10.7.0
	 */
	public function authorize( WP_REST_Request $request ) {
		$reason = $this->find_authorization_failure( $request );

		if ( null === $reason ) {
			return true;
		}

		$this->log_authorization_failure( $request, $reason );

		return new WP_Error(
			'woocommerce_rest_unauthorized',
			self::AUTH_FAILURE_MESSAGES[ $reason ],
			array( 'status' => WP_Http::UNAUTHORIZED )
		);
	}

	/**
	 * Checks the JWT from the Authorization header against the request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return string|null One of the AUTH_FAILURE_* constants, or null when authorized.
	 */
	private function find_authorization_failure( WP_REST_Request $request ): ?string {
		$header = trim( (string) $request->get_header( 'authorization' ) );

		if ( empty( $header ) ) {
			return self::AUTH_FAILURE_CREDENTIAL_MISSING;
		}

		$token = strncasecmp( $header, 'Bearer ', 7 ) === 0 ? substr( $header, 7 ) : $header;

		if ( ! JsonWebToken::validate( $token, wp_salt( 'auth' ) ) ) {
			return self::AUTH_FAILURE_TOKEN_INVALID;
		}

		$parts = JsonWebToken::get_parts( $token );

		if ( ! isset( $parts->payload->iss ) || get_site_url() !== $parts->payload->iss ) {
			return self::AUTH_FAILURE_ISSUER_INVALID;
		}

		$body_hash = hash( 'sha256', $request->get_body() );

		if ( ! isset( $parts->payload->body_hash ) || ! hash_equals( (string) $parts->payload->body_hash, $body_hash ) ) {
			return self::AUTH_FAILURE_BODY_HASH_MISMATCH;
		}

		return null;
	}

	/**
	 * Records a refused loopback request, but only when the body looks like
	 * one of ours.
	 *
	 * The route is public until authorization passes, so logging every refused
	 * request would let anyone write to the store's log by POSTing here. A
	 * stripped header or an expired token still arrives with our body; a
	 * scanner never does.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @param string          $reason  One of the AUTH_FAILURE_* constants.
	 * @return void
	 */
	private function log_authorization_failure( WP_REST_Request $request, string $reason ): void {
		$body = json_decode( $request->get_body(), true );

		if ( ! is_array( $body ) || ! array_key_exists( 'notifications', $body ) ) {
			return;
		}

		wc_get_container()->get( NotificationStepLogger::class )->log_unattributed_failure(
			'received',
			'auth_failed',
			'warning',
			sprintf( 'Loopback request refused: %s', self::AUTH_FAILURE_MESSAGES[ $reason ] ),
			array(
				'reason'        => $reason,
				'notifications' => is_array( $body['notifications'] ) ? count( $body['notifications'] ) : 0,
			)
		);
	}
}
