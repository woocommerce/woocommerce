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
		if ( ! $this->is_jetpack_ready() ) {
			throw new Completion_Exception( __( 'Not connected to Jetpack. Please make sure that Jetpack is active and connected.', 'woocommerce' ), 400 );
		}

		$site_id  = $this->get_site_id();
		$response = Client::wpcom_json_api_request_as_user(
			"/sites/{$site_id}/jetpack-ai/completions",
			'2',
			array(
				'method'  => 'POST',
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'timeout' => self::COMPLETION_TIMEOUT,
			),
			$arguments
		);

		if ( is_wp_error( $response ) ) {
			/* translators: %s: The error message. */
			throw new Completion_Exception( sprintf( __( 'Failed to get completion: %s', 'woocommerce' ), $response->get_error_message() ), $response->get_error_code() );
		}

		try {
			$response_body = wp_remote_retrieve_body( $response );
			// Extract the string from the response. Response might be wrapped in quotes and escaped. E.g. "{ \n \"foo\": \"bar\" \n }".
			if ( is_string( $response_body ) ) {
				$response_body = json_decode( $response_body, true, 512, JSON_THROW_ON_ERROR );
			}

			$decoded = json_decode( $response_body, true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			/* translators: %s: The error message. */
			throw new Completion_Exception( sprintf( __( 'Failed to decode completion response: %s', 'woocommerce' ), $e->getMessage() ), 500, $e );
		}

		if ( empty( $decoded ) || ! is_array( $decoded ) || ! isset( $decoded['completion'] ) ) {
			/* translators: %s: The response body. */
			throw new Completion_Exception( sprintf( __( 'Invalid or empty completion response: %s', 'woocommerce' ), $response_body ), 500 );
		}

		return $response;
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
}
