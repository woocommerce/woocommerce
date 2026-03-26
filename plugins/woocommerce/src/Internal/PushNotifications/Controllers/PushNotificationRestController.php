<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Controllers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
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

		if ( empty( $notifications ) || ! is_array( $notifications ) ) {
			wc_get_logger()->warning(
				'Loopback endpoint received empty or missing notifications array.',
				array( 'source' => PushNotifications::FEATURE_NAME )
			);

			return $success_response;
		}

		$processor = wc_get_container()->get( NotificationProcessor::class );

		foreach ( $notifications as $data ) {
			try {
				$notification = Notification::from_array( $data );
				$processor->process( $notification );
			} catch ( Exception $e ) {
				wc_get_logger()->error(
					sprintf( 'Failed to process notification: %s', $e->getMessage() ),
					array( 'source' => PushNotifications::FEATURE_NAME )
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
		$header = $request->get_header( 'authorization' );

		if ( empty( $header ) ) {
			return new WP_Error(
				'woocommerce_rest_unauthorized',
				'Missing authorization header.',
				array( 'status' => WP_Http::UNAUTHORIZED )
			);
		}

		$token = (string) preg_replace( '/^\s*Bearer\s+/i', '', $header );

		if ( ! JsonWebToken::validate( $token, wp_salt( 'auth' ) ) ) {
			return new WP_Error(
				'woocommerce_rest_unauthorized',
				'Invalid or expired token.',
				array( 'status' => WP_Http::UNAUTHORIZED )
			);
		}

		$parts = JsonWebToken::get_parts( $token );

		if ( ! isset( $parts->payload->iss ) || get_site_url() !== $parts->payload->iss ) {
			return new WP_Error(
				'woocommerce_rest_unauthorized',
				'Invalid token issuer.',
				array( 'status' => WP_Http::UNAUTHORIZED )
			);
		}

		$body_hash = hash( 'sha256', $request->get_body() );

		if ( ! isset( $parts->payload->body_hash ) || ! hash_equals( (string) $parts->payload->body_hash, $body_hash ) ) {
			return new WP_Error(
				'woocommerce_rest_unauthorized',
				'Body hash mismatch.',
				array( 'status' => WP_Http::UNAUTHORIZED )
			);
		}

		return true;
	}
}
