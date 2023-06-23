<?php
/**
 * Jetpack Completion Service Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\Completion;

use Automattic\Jetpack\Connection\Client;
use Jetpack;
use Jetpack_Options;
use JsonException;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Jetpack Service class.
 */
class Jetpack_Completion_Service implements Completion_Service_Interface {

	/**
	 * The timeout for the completion request.
	 */
	private const COMPLETION_TIMEOUT = 60;

	/**
	 * Gets the completion from the API.
	 *
	 * @param array $arguments An array of arguments to send to the API.
	 *
	 * @return string The completion response.
	 *
	 * @throws Completion_Exception If the request fails.
	 */
	public function get_completion( array $arguments ): string {
		$this->validate_jetpack_connection();

		$site_id  = $this->get_site_id();
		$response = $this->send_request_to_api( $site_id, $arguments );

		return $this->process_response( $response );
	}

	/**
	 * Validates the Jetpack connection.
	 *
	 * @throws Completion_Exception If Jetpack connection is not ready.
	 */
	private function validate_jetpack_connection(): void {
		if ( ! $this->is_jetpack_ready() ) {
			throw new Completion_Exception( __( 'Not connected to Jetpack. Please make sure that Jetpack is active and connected.', 'woocommerce' ), 400 );
		}
	}

	/**
	 * Returns whether Jetpack connection is ready.
	 *
	 * @return bool True if Jetpack connection is ready, false otherwise.
	 */
	private function is_jetpack_ready(): bool {
		return Jetpack::connection()->has_connected_owner() && Jetpack::is_connection_ready();
	}

	/**
	 * Gets the Jetpack Site ID.
	 *
	 * @return string The Jetpack Site ID.
	 *
	 * @throws Completion_Exception If no Jetpack Site ID is found.
	 */
	private function get_site_id(): string {
		$site_id = Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			throw new Completion_Exception( __( 'No Jetpack Site ID found. Please make sure that Jetpack is active and connected.', 'woocommerce' ), 400 );
		}

		return (string) $site_id;
	}

	/**
	 * Sends request to the API and gets the response.
	 *
	 * @param string $site_id   The site ID.
	 * @param array  $arguments An array of arguments to send to the API.
	 *
	 * @return array|WP_Error The response from the API.
	 */
	private function send_request_to_api( string $site_id, array $arguments ) {
		return Client::wpcom_json_api_request_as_user(
			'/text-completion/',
			'2',
			array(
				'method'  => 'POST',
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'timeout' => self::COMPLETION_TIMEOUT,
			),
			$arguments
		);
	}

	/**
	 * Processes the API response.
	 *
	 * @param array|WP_Error $response The response from the API.
	 *
	 * @return string The completion response.
	 *
	 * @throws Completion_Exception If there's an error in the response.
	 */
	private function process_response( $response ): string {
		if ( is_wp_error( $response ) ) {
			$this->handle_wp_error( $response );
		}

		if ( ! isset( $response['response']['code'] ) || 200 !== $response['response']['code'] ) {
			/* translators: %s: The error message. */
			throw new Completion_Exception( sprintf( __( 'Failed to get completion ( reason: %s )', 'woocommerce' ), $response['response']['message'] ), 400 );
		}

		$response_body = wp_remote_retrieve_body( $response );

		try {
			// Extract the string from the response. Response might be wrapped in quotes and escaped. E.g. "{ \n \"foo\": \"bar\" \n }".
			$decoded = json_decode( $response_body, true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			$this->handle_json_exception( $e );
		}

		return $this->get_completion_string_from_decoded_response( $decoded, $response_body );
	}

	/**
	 * Handles WP_Error response.
	 *
	 * @param WP_Error $response The WP_Error response.
	 *
	 * @throws Completion_Exception With the error message from the response.
	 */
	private function handle_wp_error( WP_Error $response ): void {
		/* translators: %s: The error message. */
		throw new Completion_Exception( sprintf( __( 'Failed to get completion ( reason: %s )', 'woocommerce' ), $response->get_error_message() ), 400 );
	}

	/**
	 * Handles JSON parsing exceptions.
	 *
	 * @param JsonException $e The JSON exception.
	 *
	 * @throws Completion_Exception With the error message from the JSON exception.
	 */
	private function handle_json_exception( JsonException $e ): void {
		/* translators: %s: The error message. */
		throw new Completion_Exception( sprintf( __( 'Failed to decode completion response ( reason: %s )', 'woocommerce' ), $e->getMessage() ), 500, $e );
	}

	/**
	 * Retrieves the completion string from the decoded response.
	 *
	 * @param mixed  $decoded       The decoded JSON response.
	 * @param string $response_body The original response body.
	 *
	 * @return string The completion string.
	 *
	 * @throws Completion_Exception If the decoded response is invalid or empty.
	 */
	private function get_completion_string_from_decoded_response( $decoded, string $response_body ): string {
		if ( ! is_string( $decoded ) ) {
			// Check if the response is an error.
			if ( isset( $decoded['code'] ) ) {
				$error_message = $decoded['message'] ?? $decoded['code'];
				/* translators: %s: The error message. */
				throw new Completion_Exception( sprintf( __( 'Failed to get completion ( reason: %s )', 'woocommerce' ), $error_message ), 400 );
			}

			// If the decoded response is not an error, it means that the response was not wrapped in quotes and escaped, so we can use it as is.
			$decoded = $response_body;
		}
		if ( empty( $decoded ) || ! is_string( $decoded ) ) {
			/* translators: %s: The response body. */
			throw new Completion_Exception( sprintf( __( 'Invalid or empty completion response: %s', 'woocommerce' ), $response_body ), 500 );
		}

		return $decoded;
	}

}
