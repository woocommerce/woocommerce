<?php
/**
 * FraudProtectionServiceApiClient class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Handles communication with the mock WPCOM fraud decision endpoint.
 *
 * This is a prototype/POC implementation that calls a mock WPCOM endpoint
 * to determine fraud protection decisions (allow, block, or challenge).
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
	 * Track a session event and get fraud decision from WPCOM endpoint.
	 *
	 * Implements fail-open pattern: if the endpoint is unreachable or times out,
	 * returns "allow" decision and logs the error.
	 *
	 * @param string $event_name   Name of the event being tracked (e.g., 'challenge_requested', 'challenge_verified').
	 * @param array  $session_data Session data to send to the endpoint.
	 * @return string Decision: "allow", "block", or "challenge".
	 */
	public function track_session_event( string $event_name, array $session_data ): string {
		$endpoint_url = $this->get_endpoint_url();

		// Build request payload.
		$payload = array(
			'event_name'   => $event_name,
			'session_data' => $session_data,
			'timestamp'    => time(),
		);

		// Make the API request.
		$response = $this->make_request( $endpoint_url, $payload );

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
		$decision = $this->parse_response( $response );

		// Log successful decision.
		$this->log_success( $event_name, $decision, $session_data );

		return $decision;
	}

	/**
	 * Get the WPCOM endpoint URL.
	 *
	 * @return string Endpoint URL.
	 */
	private function get_endpoint_url(): string {
		/**
		 * Filter the WPCOM fraud decision endpoint URL.
		 *
		 * This allows configuration of the mock endpoint URL for POC/testing.
		 *
		 * @since 10.4.0
		 *
		 * @param string $url The endpoint URL.
		 */
		return 'https://public-api.wordpress.com/wpcom/v2/fraud-protection/events';
	}

	/**
	 * Make an HTTP POST request to the endpoint.
	 *
	 * @param string $url     Endpoint URL.
	 * @param array  $payload Request payload.
	 * @return array|\WP_Error Response array or WP_Error on failure.
	 */
	private function make_request( string $url, array $payload ) {
		// We'll mock the response for now untill the endpoint is ready
		switch ( $payload['session_data']['email'] ) {
			case 'test@example.com':
				return array(
					'code' => 200,
					'body' => '{"result": "success", "decision": "allow"}',
				);
			case 'fraudster@example.com':
				return array(
					'code' => 200,
					'body' => '{"result": "success", "decision": "block"}',
				);
			default:
				return new \WP_Error( 'api_error', 'Invalid email address' );
		}


		$request_args = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => $this->get_timeout(),
		);

		// TODO: use Jetpack Connection for sending requests
		$response = wp_remote_post( $url, $request_args );

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
	 * Expected response format:
	 * {
	 *   "result": "success|error",
	 *   "decision": "allow|block|challenge",
	 *   "error": [...]
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

		// Validate response structure.
		if ( ! isset( $data['result'] ) ) {
			$this->log_error(
				'Response missing "result" field. Failing open with "allow" decision.'
			);
			return self::DECISION_ALLOW;
		}

		// Handle error result.
		if ( 'error' === $data['result'] ) {
			$error_details = isset( $data['error'] ) ? wp_json_encode( $data['error'] ) : 'No error details provided';
			$this->log_error(
				sprintf(
					'Endpoint returned error result: %s. Failing open with "allow" decision.',
					$error_details
				)
			);
			return self::DECISION_ALLOW;
		}

		// Handle success result.
		if ( 'success' === $data['result'] ) {
			if ( ! isset( $data['decision'] ) ) {
				$this->log_error(
					'Success result missing "decision" field. Failing open with "allow" decision.'
				);
				return self::DECISION_ALLOW;
			}

			$decision = $data['decision'];

			// Validate decision value.
			if ( ! in_array( $decision, array( self::DECISION_ALLOW, self::DECISION_BLOCK, self::DECISION_CHALLENGE ), true ) ) {
				$this->log_error(
					sprintf(
						'Invalid decision value "%s". Failing open with "allow" decision.',
						$decision
					)
				);
				return self::DECISION_ALLOW;
			}

			return $decision;
		}

		// Handle unexpected result value.
		$this->log_error(
			sprintf(
				'Unexpected result value "%s". Failing open with "allow" decision.',
				$data['result']
			)
		);
		return self::DECISION_ALLOW;
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
	 * Log a successful API call.
	 *
	 * @param string $event_name   Event name that was tracked.
	 * @param string $decision     Decision received.
	 * @param array  $session_data Session data sent.
	 * @return void
	 */
	private function log_success( string $event_name, string $decision, array $session_data ): void {
		$logger = wc_get_logger();

		$session_id = isset( $session_data['session_key'] ) ? $session_data['session_key'] : 'unknown';

		$message = sprintf(
			'Fraud decision received: %s | Event: %s | Session: %s | Timestamp: %s',
			$decision,
			$event_name,
			$session_id,
			current_time( 'mysql' )
		);

		$logger->info( $message, array( 'source' => self::LOGGER_SOURCE ) );
	}
}
