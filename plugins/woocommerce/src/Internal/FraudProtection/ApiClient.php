<?php
/**
 * ApiClient class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;

defined( 'ABSPATH' ) || exit;

/**
 * Handles communication with the WPCOM fraud protection endpoint.
 *
 * Uses Jetpack Connection for authenticated requests to the WPCOM endpoint
 * to get fraud protection decisions (allow, block, or challenge).
 *
 * This class implements a fail-open pattern: if the endpoint is unreachable,
 * times out, or returns an error, it returns an "allow" decision to ensure
 * legitimate transactions are never blocked due to service issues.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class ApiClient {

	/**
	 * Default timeout for API requests in seconds.
	 */
	private const DEFAULT_TIMEOUT = 30;

	/**
	 * WPCOM API version.
	 */
	private const WPCOM_API_VERSION = '2';

	/**
	 * WPCOM fraud protection events endpoint path within Transact platform.
	 */
	private const EVENTS_ENDPOINT = 'transact/fraud_protection/events';

	/**
	 * Decision type: allow session.
	 */
	public const DECISION_ALLOW = 'allow';

	/**
	 * Decision type: block session.
	 */
	public const DECISION_BLOCK = 'block';

	/**
	 * Decision type: challenge session.
	 */
	public const DECISION_CHALLENGE = 'challenge';

	/**
	 * Valid decision values that can be returned by the API.
	 *
	 * @var array<string>
	 */
	public const VALID_DECISIONS = array(
		self::DECISION_ALLOW,
		self::DECISION_BLOCK,
	);

	/**
	 * Send a fraud protection event and get a decision from WPCOM endpoint.
	 *
	 * Implements fail-open pattern: if the endpoint is unreachable or times out,
	 * returns "allow" decision and logs the error.
	 *
	 * @since 10.5.0
	 *
	 * @param string               $event_type Type of event being sent (e.g., 'cart_updated', 'checkout_started').
	 * @param array<string, mixed> $event_data Event data to send to the endpoint.
	 * @return string Decision: "allow" or "block".
	 */
	public function send_event( string $event_type, array $event_data ): string {
		$payload = array_merge(
			array( 'event_type' => $event_type ),
			array_filter( $event_data, fn( $value ) => null !== $value )
		);

		FraudProtectionController::log(
			'info',
			sprintf( 'Sending fraud protection event: %s', $event_type ),
			array( 'payload' => $payload )
		);

		$response = $this->make_request( 'POST', self::EVENTS_ENDPOINT, $payload );

		if ( is_wp_error( $response ) ) {
			$error_data = $response->get_error_data() ?? array();
			$error_data = is_array( $error_data ) ? $error_data : array( 'error' => $error_data );
			FraudProtectionController::log(
				'error',
				sprintf(
					'Event track request failed: %s. Failing open with "allow" decision.',
					$response->get_error_message()
				),
				$error_data
			);
			return self::DECISION_ALLOW;
		}

		if ( ! isset( $response['decision'] ) ) {
			FraudProtectionController::log(
				'error',
				'Response missing "decision" field. Failing open with "allow" decision.',
				array( 'response' => $response )
			);
			return self::DECISION_ALLOW;
		}

		$decision = $response['decision'];

		if ( ! in_array( $decision, self::VALID_DECISIONS, true ) ) {
			FraudProtectionController::log(
				'error',
				sprintf(
					'Invalid decision value "%s". Failing open with "allow" decision.',
					$decision
				),
				array( 'response' => $response )
			);
			return self::DECISION_ALLOW;
		}

		$session    = is_array( $event_data['session'] ?? null ) ? $event_data['session'] : array();
		$session_id = $session['session_id'] ?? 'unknown';
		FraudProtectionController::log(
			'info',
			sprintf(
				'Fraud decision received: %s | Event: %s | Session: %s',
				$decision,
				$event_type,
				$session_id
			),
			array( 'response' => $response )
		);

		return $decision;
	}

	/**
	 * Make an HTTP request to a WPCOM endpoint via Jetpack Connection.
	 *
	 * @param string               $method  HTTP method (GET, POST, etc.).
	 * @param string               $path    Endpoint path (relative to sites/{blog_id}/).
	 * @param array<string, mixed> $payload Request payload.
	 * @return array<string, mixed>|\WP_Error Parsed JSON response or WP_Error on failure.
	 */
	private function make_request( string $method, string $path, array $payload ) {
		if ( ! class_exists( Jetpack_Connection_Client::class ) ) {
			return new \WP_Error(
				'jetpack_not_available',
				'Jetpack Connection is not available'
			);
		}

		$blog_id = $this->get_blog_id();
		if ( ! $blog_id ) {
			return new \WP_Error(
				'no_blog_id',
				'Jetpack blog ID not found. Is the site connected to WordPress.com?'
			);
		}

		$full_path = sprintf( 'sites/%d/%s', $blog_id, $path );

		$body = \wp_json_encode( $payload );

		if ( false === $body ) {
			return new \WP_Error(
				'json_encode_error',
				'Failed to encode payload',
				array( 'payload' => $payload )
			);
		}

		$response = Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			$full_path,
			self::WPCOM_API_VERSION,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'method'  => $method,
				'timeout' => self::DEFAULT_TIMEOUT,
			),
			$body,
			'wpcom'
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		/**
		 * Type assertion for PHPStan - Jetpack returns array on success.
		 *
		 * @var array $response
		 */
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		$data = json_decode( $response_body, true );

		if ( $response_code >= 300 ) {
			return new \WP_Error(
				'api_error',
				sprintf( 'Endpoint %s returned status code %d', "$method $path", $response_code ),
				array( 'response' => JSON_ERROR_NONE === json_last_error() ? $data : $response_body )
			);
		}

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			return new \WP_Error(
				'json_decode_error',
				sprintf( 'Failed to decode JSON response: %s', json_last_error_msg() ),
				array( 'response' => $response_body )
			);
		}

		return $data;
	}

	/**
	 * Get the Jetpack blog ID.
	 *
	 * @return int|false Blog ID or false if not available.
	 */
	private function get_blog_id() {
		if ( ! class_exists( \Jetpack_Options::class ) ) {
			return false;
		}
		return \Jetpack_Options::get_option( 'id' );
	}
}
