<?php
/**
 * Completion Service Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\Completion;

use Exception;
use JsonException;

defined( 'ABSPATH' ) || exit;

class Completion_Service implements Completion_Service_Interface {
	/**
	 * Gets the completion from the API.
	 *
	 * @param array $messages An array of messages to send to the API.
	 * @param array $options  An array of options to send to the API.
	 *
	 * @return string
	 *
	 * @throws Exception If the request fails.
	 */
	public function get_completion( array $messages, array $options = array() ): string {
		$post_data = array(
			'messages'    => $messages,
			'model'       => 'gpt-3.5-turbo',
			'temperature' => 1,
		);

		$post_data = array_merge( $post_data, $options );

		$response = $this->send_post_request( 'https://api.openai.com/v1/chat/completions', $post_data );

		if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
			throw new Exception( 'Invalid response from API: ' . wp_json_encode( $response ) );
		}

		return $response['choices'][0]['message']['content'];
	}

	/**
	 * Send a POST request and returns the JSON decoded response as an array.
	 *
	 * @param string $url       The URL to send the request to.
	 * @param array  $post_data The data to send in the request body.
	 *
	 * @return array The JSON decoded response.
	 *
	 * @throws Exception If the request fails.
	 */
	protected function send_post_request( string $url, array $post_data ): array {
		$raw_response = wp_remote_post(
			$url,
			array(
				'method'  => 'POST',
				'body'    => wp_json_encode( $post_data ),
				'timeout' => 45,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . OPEN_AI_KEY,
				),
			)
		);

		if ( is_wp_error( $raw_response ) ) {
			$error_message = $raw_response->get_error_message();

			throw new Exception( "Something went wrong: $error_message" );
		}

		try {
			$response = json_decode( wp_remote_retrieve_body( $raw_response ), true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			throw new Exception( sprintf( 'Invalid Response from API: %s', $e->getMessage() ) );
		}

		return $response;
	}
}
