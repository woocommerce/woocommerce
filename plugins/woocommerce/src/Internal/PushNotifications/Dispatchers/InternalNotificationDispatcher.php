<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Dispatchers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationStepLogger;
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
	 * JWT expiry in seconds.
	 */
	const JWT_EXPIRY_SECONDS = 30;

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
			foreach ( $notifications as $notification ) {
				$this->step_logger->log_failure(
					$notification,
					'dispatched',
					'encode_failed',
					'error',
					'Failed to JSON-encode push notification payload.',
					array( 'batch_size' => count( $notifications ) )
				);
			}
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
		 */
		$response = wp_remote_post(
			rest_url( self::SEND_ENDPOINT ),
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

		foreach ( $notifications as $notification ) {
			if ( is_wp_error( $response ) ) {
				$this->step_logger->log_failure(
					$notification,
					'dispatched',
					'request_failed',
					'warning',
					sprintf( 'Loopback request failed: %s', $response->get_error_message() ),
					array( 'batch_size' => count( $notifications ) )
				);
			} else {
				$this->step_logger->log_notification_step(
					$notification,
					'dispatched',
					'ok',
					array( 'batch_size' => count( $notifications ) )
				);
			}
		}
	}
}
