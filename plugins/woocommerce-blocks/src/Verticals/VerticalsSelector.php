<?php

namespace Automattic\WooCommerce\Blocks\Verticals;

use Automattic\WooCommerce\Blocks\AI\Connection;
use Automattic\WooCommerce\Blocks\Verticals\Client as VerticalsAPIClient;


/**
 * VerticalsSelector class.
 */
class VerticalsSelector {
	public const STORE_DESCRIPTION_OPTION_KEY = 'woo_ai_describe_store_description';

	/**
	 * The verticals API client.
	 *
	 * @var VerticalsAPIClient
	 */
	private $verticals_api_client;

	/**
	 * The AI Connection.
	 *
	 * @var Connection
	 */
	private $ai_connection;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->verticals_api_client = new VerticalsAPIClient();
		$this->ai_connection        = new Connection();
	}

	/**
	 * Gets the vertical id that better matches the business description using the GPT API.
	 *
	 * @param string $business_description The business description.
	 *
	 * @return string|\WP_Error The vertical id, or WP_Error if the request failed.
	 */
	public function get_vertical_id( $business_description = '' ) {
		if ( empty( $business_description ) ) {
			$business_description = $this->get_business_description();
		}

		if ( ! is_string( $business_description ) ) {
			return new \WP_Error(
				'missing_business_description',
				__( 'The business description is required to generate the content for your site.', 'woo-gutenberg-products-block' )
			);
		}

		$verticals = $this->verticals_api_client->get_verticals();
		if ( is_wp_error( $verticals ) ) {
			return $verticals;
		}

		$prompt  = $this->build_prompt( $verticals, $business_description );
		$site_id = $this->ai_connection->get_site_id();

		if ( is_wp_error( $site_id ) ) {
			return $site_id;
		}

		$token = $this->ai_connection->get_jwt_token( $site_id );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$ai_response = $this->ai_connection->fetch_ai_response( $token, $prompt );

		if ( is_wp_error( $ai_response ) ) {
			return $ai_response;
		}

		if ( ! isset( $ai_response['completion'] ) ) {
			return new \WP_Error( 'invalid_ai_response', __( 'The AI response is invalid.', 'woo-gutenberg-products-block' ) );
		}

		return $this->parse_answer( $ai_response['completion'] );
	}

	/**
	 * Get the business description from the AI settings in WooCommerce.
	 *
	 * @return string The business description.
	 */
	private function get_business_description(): string {
		return get_option( self::STORE_DESCRIPTION_OPTION_KEY, '' );
	}

	/**
	 * Build the prompt to send to the GPT API.
	 *
	 * @param array  $verticals The list of verticals.
	 * @param string $business_description The business description.
	 *
	 * @return string The prompt to send to the GPT API.
	 */
	private function build_prompt( array $verticals, string $business_description ): string {
		$verticals = array_map(
			function ( $vertical ) {
				return "[ID=${vertical['id']}, Name=\"${vertical['name']}\"]";
			},
			$verticals
		);

		if ( empty( $verticals ) ) {
			return '';
		}

		$verticals = implode( ', ', $verticals );

		return sprintf(
			'Filter the objects provided below and return the one that has a title that better matches this description of an online store with the following description: "%s". The response should include exclusively the ID of the object that better matches. The response should be a number, with absolutely no texts and without any explanations \n %s.',
			$business_description,
			$verticals
		);
	}

	/**
	 * Parse the answer from the GPT API and return the id of the selected vertical.
	 *
	 * @param string $ai_response The answer from the GPT API.
	 *
	 * @return int|\WP_Error The id of the selected vertical.
	 */
	private function parse_answer( $ai_response ) {
		$vertical_id = preg_replace( '/[^0-9]/', '', $ai_response );

		if ( ! is_numeric( $vertical_id ) ) {
			return new \WP_Error( 'invalid_ai_response', __( 'The AI response is invalid.', 'woo-gutenberg-products-block' ) );
		}

		return (int) $vertical_id;
	}
}
