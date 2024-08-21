<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API\AI;

use Automattic\WooCommerce\Blocks\AI\Connection;
use Automattic\WooCommerce\Blocks\Images\Pexels;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Images controller
 *
 * @internal
 */
class Images extends AIEndpoint {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	protected $endpoint = 'images';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		$this->register(
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_images' ),
					'permission_callback' => array( Middleware::class, 'is_authorized' ),
					'args'                => array(
						'business_description' => array(
							'description' => __( 'The business description for a given store.', 'woocommerce' ),
							'type'        => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Generate Images from Pexels
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function generate_images( WP_REST_Request $request ) {

		$business_description = sanitize_text_field( wp_unslash( $request['business_description'] ) );

		if ( empty( $business_description ) ) {
			$business_description = get_option( 'woo_ai_describe_store_description' );
		}

		$last_business_description = get_option( 'last_business_description_with_ai_content_generated' );

		if ( $last_business_description === $business_description ) {
			return rest_ensure_response(
				$this->prepare_item_for_response(
					array(
						'ai_content_generated' => true,
						'images'               => array(),
					),
					$request
				)
			);
		}

		$ai_connection = new Connection();

		$site_id = $ai_connection->get_site_id();

		if ( is_wp_error( $site_id ) ) {
			return $site_id;
		}

		$token = $ai_connection->get_jwt_token( $site_id );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$images = ( new Pexels() )->get_images( $ai_connection, $token, $business_description );

		if ( is_wp_error( $images ) ) {
			$images = array(
				'images'      => array(),
				'search_term' => '',
			);
		}

		return rest_ensure_response(
			array(
				'ai_content_generated' => true,
				'images'               => $images,
			)
		);
	}
}
