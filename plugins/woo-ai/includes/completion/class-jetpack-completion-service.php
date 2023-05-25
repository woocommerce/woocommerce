<?php
/**
 * Jetpack Completion Service Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\Completion;

use Automattic\Jetpack\Connection\Client;
use Exception;
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
	 * @throws Exception If the request fails.
	 */
	public function get_completion( array $arguments ): string {
		if ( ! $this->is_jetpack_ready() ) {
			throw new Exception( 'Not connected to Jetpack' );
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

		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			throw new Exception( 'Failed to get completion: ' . $response->get_error_message() );
		}

		try {
			// Extract the string from the response. Response is wrapped in quotes and escaped. E.g. "{ \n \"foo\": \"bar\" \n }".
			$response = json_decode( $response['body'], true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			throw new Exception( 'Failed to decode completion response: ' . $e->getMessage() );
		}

		return $response;
	}

	/**
	 * Gets the Jetpack Site ID.
	 *
	 * @return string The Jetpack Site ID.
	 *
	 * @throws Exception If no Jetpack Site ID is found.
	 */
	private function get_site_id(): string {
		$site_id = Jetpack_Options::get_option( 'id' );
		if ( ! $site_id ) {
			throw new Exception( 'No Jetpack Site ID found' );
		}

		return (string) $site_id;
	}

	/**
	 * Returns whether Jetpack connection is ready.
	 *
	 * @return bool True if Jetpack connection is ready, false otherwise.
	 */
	private function is_jetpack_ready(): bool {
		return Jetpack::connection()->has_connected_owner() && Jetpack::is_connection_ready();

	}
}
