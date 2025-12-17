<?php
/**
 * FraudProtectionServiceApiClient class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;

defined( 'ABSPATH' ) || exit;

/**
 * Handles communication with the WPCOM fraud protection endpoint.
 *
 * Uses Jetpack Connection for authenticated requests to the WPCOM endpoint
 * to get fraud protection verdicts (allow, block, or challenge).
 *
 * @since 10.4.0
 */
class FraudProtectionServiceApiClient {

	/**
	 * Default timeout for API requests in seconds.
	 */
	private const DEFAULT_TIMEOUT = 10;

	/**
	 * Logger source identifier.
	 */
	private const LOGGER_SOURCE = 'woo-fraud-protection';

	/**
	 * WPCOM API version.
	 */
	private const WPCOM_API_VERSION = '2';

	/**
	 * WPCOM fraud protection events endpoint path (relative, without leading slash).
	 * Must be combined with sites/{blog_id}/ prefix for site-specific endpoints.
	 */
	private const WPCOM_ENDPOINT_PATH = 'fraud-protection/events';

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
	 * Jetpack connection manager instance.
	 *
	 * @var JetpackConnectionManager
	 */
	private $connection_manager;

	/**
	 * Constructor.
	 *
	 * @param JetpackConnectionManager $connection_manager Jetpack connection manager instance.
	 */
	public function __construct( JetpackConnectionManager $connection_manager ) {
		$this->connection_manager = $connection_manager;
	}

	/**
	 * Track a session event and get fraud decision from WPCOM endpoint.
	 *
	 * Implements fail-open pattern: if the endpoint is unreachable or times out,
	 * returns "allow" decision and logs the error.
	 *
	 * @param string $event_type   Type of event being tracked (e.g., 'challenge_requested', 'challenge_succeeded').
	 * @param array  $session_data Session data to send to the endpoint.
	 * @return string Decision: "allow", "block", or "challenge".
	 */
	public function track_session_event( string $event_type, array $session_data ): string {
		// Build flat payload matching WPCOM endpoint schema.
		$payload = array_merge(
			array( 'event_type' => $event_type ),
			array_filter( $session_data, fn( $value ) => null !== $value ) // Filter out null values
		);

		// Make the API request.
		$response = $this->make_request( $payload );

		// Handle errors with fail-open pattern.
		if ( is_wp_error( $response ) ) {
			$this->log_error(
				sprintf(
					'Endpoint request failed: %s. Failing open with "allow" decision.',
					$response->get_error_message()
				)
			);
			return self::DECISION_ALLOW;
		}

		// Parse and validate response.
		$verdict = $this->parse_response( $response );

		// Log successful verdict.
		$this->log_success( $event_type, $verdict, $session_data );

		return $verdict;
	}

	/**
	 * Make an HTTP POST request to the WPCOM endpoint via Jetpack Connection.
	 *
	 * @param array $payload Request payload (flat schema).
	 * @return array|\WP_Error Response array with 'code' and 'body' keys, or WP_Error on failure.
	 */
	private function make_request( array $payload ) {
		$this->log_request( $payload );

		// Check connection status using connection manager.
		$connection_status = $this->connection_manager->get_connection_status();
		if ( ! $connection_status['connected'] ) {
			return new \WP_Error(
				$connection_status['error_code'],
				$connection_status['error']
			);
		}

		// Get blog ID from connection manager.
		$blog_id = $connection_status['blog_id'];

		// Check if Jetpack Connection Client is available.
		if ( ! class_exists( Jetpack_Connection_Client::class ) ) {
			return new \WP_Error(
				'jetpack_not_available',
				'Jetpack Connection Client class is not available'
			);
		}

		// Build site-specific endpoint path: sites/{blog_id}/fraud-protection/events
		$path = sprintf( 'sites/%d/%s', $blog_id, self::WPCOM_ENDPOINT_PATH );

		// Use Jetpack Connection for authenticated WPCOM requests.
		$response = Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			$path,
			self::WPCOM_API_VERSION,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'method'  => 'POST',
				'timeout' => $this->get_timeout(),
			),
			wp_json_encode( $payload ),
			'wpcom'
		);

		// Check for connection errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Check for HTTP errors.
		if ( $response_code >= 300 ) {
			return new \WP_Error(
				'api_error',
				sprintf(
					'Endpoint returned status code %d: %s',
					$response_code,
					$response_body
				)
			);
		}

		return array(
			'code' => $response_code,
			'body' => $response_body,
		);
	}

	/**
	 * Parse and validate the API response.
	 *
	 * Expected WPCOM response format:
	 * {
	 *   "success": true,
	 *   "fraud_event_id": 123,
	 *   "verdict": "allow|block|challenge",
	 *   "risk_score": 45,
	 *   "reason_tags": ["failures_per_ip"]
	 * }
	 *
	 * @param array $response Response array with 'code' and 'body' keys.
	 * @return string Decision: "allow", "block", or "challenge".
	 */
	private function parse_response( array $response ): string {
		$data = json_decode( $response['body'], true );

		// Handle JSON decode errors.
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->log_error(
				sprintf(
					'Failed to decode JSON response: %s. Failing open with "allow" decision.',
					json_last_error_msg()
				)
			);
			return self::DECISION_ALLOW;
		}

		// Validate response structure - check for success field.
		if ( ! isset( $data['success'] ) ) {
			$this->log_error(
				'Response missing "success" field. Failing open with "allow" decision.'
			);
			return self::DECISION_ALLOW;
		}

		// Handle error response.
		if ( true !== $data['success'] ) {
			$error_details = isset( $data['message'] ) ? $data['message'] : 'No error details provided';
			$this->log_error(
				sprintf(
					'Endpoint returned error: %s. Failing open with "allow" decision.',
					$error_details
				)
			);
			return self::DECISION_ALLOW;
		}

		// Handle success response - get verdict.
		if ( ! isset( $data['verdict'] ) ) {
			$this->log_error(
				'Success response missing "verdict" field. Failing open with "allow" decision.'
			);
			return self::DECISION_ALLOW;
		}

		$verdict = $data['verdict'];

		// Validate verdict value.
		if ( ! in_array( $verdict, array( self::DECISION_ALLOW, self::DECISION_BLOCK, self::DECISION_CHALLENGE ), true ) ) {
			$this->log_error(
				sprintf(
					'Invalid verdict value "%s". Failing open with "allow" decision.',
					$verdict
				)
			);
			return self::DECISION_ALLOW;
		}

		return $verdict;
	}

	/**
	 * Get the request timeout in seconds.
	 *
	 * @return int Timeout in seconds.
	 */
	private function get_timeout(): int {
		/**
		 * Filter the API request timeout.
		 *
		 * @since 10.4.0
		 *
		 * @param int $timeout Timeout in seconds.
		 */
		return apply_filters(
			'woocommerce_fraud_protection_api_timeout',
			self::DEFAULT_TIMEOUT
		);
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	private function log_error( string $message ): void {
		$logger = wc_get_logger();
		$logger->error( $message, array( 'source' => self::LOGGER_SOURCE ) );
	}

	/**
	 * Log a request.
	 *
	 * @param array  $payload Request payload.
	 * @return void
	 */
	private function log_request( array $payload ): void {
		$logger = wc_get_logger();
		$logger->info( 'FraudProtectionServiceRequestSent for event: ' . $payload['event_type'], array( 'source' => self::LOGGER_SOURCE, 'payload' => $payload ) );
	}

	/**
	 * Log a successful API call.
	 *
	 * @param string $event_type   Event type that was tracked.
	 * @param string $verdict      Verdict received.
	 * @param array  $session_data Session data sent.
	 * @return void
	 */
	private function log_success( string $event_type, string $verdict, array $session_data ): void {
		$logger = wc_get_logger();

		$session_id = isset( $session_data['session_id'] ) ? $session_data['session_id'] : 'unknown';

		$message = sprintf(
			'Fraud verdict received: %s | Event: %s | Session: %s | Timestamp: %s',
			$verdict,
			$event_type,
			$session_id,
			current_time( 'mysql' )
		);

		$logger->info( $message, array( 'source' => self::LOGGER_SOURCE ) );
	}
}
