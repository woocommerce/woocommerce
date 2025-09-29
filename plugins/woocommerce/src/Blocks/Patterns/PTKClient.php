<?php
namespace Automattic\WooCommerce\Blocks\Patterns;

use WP_Error;

/**
 * PatternsToolkit class.
 *
 * @internal
 */
class PTKClient {
	/**
	 *  The Patterns Toolkit API URL
	 */
	const PATTERNS_TOOLKIT_URL = 'https://public-api.wordpress.com/rest/v1/ptk/patterns/';

	/**
	 * The schema for the patterns toolkit.
	 *
	 * @var array
	 */
	private $schema;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->schema = [
			'type'  => 'array',
			'items' => [
				'type'       => 'object',
				'properties' => [
					'ID'         => [
						'type'     => 'integer',
						'required' => true,
					],
					'site_id'    => [
						'type'     => 'integer',
						'required' => true,
					],
					'title'      => [
						'type'     => 'string',
						'required' => true,
					],
					'name'       => [
						'type'     => 'string',
						'required' => true,
					],
					'html'       => [
						'type'     => 'string',
						'required' => true,
					],
					'categories' => [
						'type'                 => 'object',
						'additionalProperties' => [
							'type'       => 'object',
							'properties' => [
								'slug'        => [
									'type'     => 'string',
									'required' => true,
								],
								'title'       => [
									'type'     => 'string',
									'required' => true,
								],
								'description' => [
									'type'     => 'string',
									'required' => true,
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Fetch the WooCommerce patterns from the Patterns Toolkit (PTK) API.
	 *
	 * @param array $options Options for fetching patterns.
	 * @return array|WP_Error
	 */
	public function fetch_patterns( array $options = array() ) {
		$locale = get_user_locale();
		$lang   = preg_replace( '/(_.*)$/', '', $locale );

		$ptk_url = self::PATTERNS_TOOLKIT_URL . $lang;

		if ( isset( $options['site'] ) ) {
			$ptk_url = add_query_arg( 'site', $options['site'], $ptk_url );
		}

		if ( isset( $options['categories'] ) ) {
			$ptk_url = add_query_arg( 'categories', implode( ',', $options['categories'] ), $ptk_url );
		}

		if ( isset( $options['per_page'] ) ) {
			$ptk_url = add_query_arg( 'per_page', $options['per_page'], $ptk_url );
		}

		$patterns = wp_safe_remote_get( $ptk_url );
		if ( is_wp_error( $patterns ) || 200 !== wp_remote_retrieve_response_code( $patterns ) ) {
			return new WP_Error(
				'patterns_toolkit_api_error',
				__( 'Failed to connect with the Patterns Toolkit API: try again later.', 'woocommerce' )
			);
		}

		$body = wp_remote_retrieve_body( $patterns );

		if ( empty( $body ) ) {
			return new WP_Error(
				'patterns_toolkit_api_error',
				__( 'Empty response received from the Patterns Toolkit API.', 'woocommerce' )
			);
		}

		$decoded_body = json_decode( $body, true );

		$is_pattern_payload_valid = $this->is_valid_schema( $decoded_body );

		if ( ! $is_pattern_payload_valid ) {
			return new WP_Error(
				'patterns_toolkit_api_error',
				__( 'Wrong response received from the Patterns Toolkit API: try again later.', 'woocommerce' )
			);
		}

		return $decoded_body;
	}

	/**
	 * Validate the patterns toolkit patterns.
	 *
	 * @param array $patterns The patterns to validate.
	 * @return bool
	 */
	public function is_valid_schema( $patterns ) {
		$is_pattern_payload_valid = rest_validate_value_from_schema( $patterns, $this->schema );

		return ! is_wp_error( $is_pattern_payload_valid );
	}
}
