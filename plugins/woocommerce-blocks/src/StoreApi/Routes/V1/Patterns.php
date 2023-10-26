<?php

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Blocks\AI\Connection;
use Automattic\WooCommerce\Blocks\Images\Pexels;
use Automattic\WooCommerce\Blocks\Patterns\PatternUpdater;
use Automattic\WooCommerce\Blocks\Patterns\ProductUpdater;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * Patterns class.
 */
class Patterns extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'patterns';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'patterns';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/patterns';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ $this, 'is_authorized' ],
				'args'                => [
					'business_description' => [
						'description' => __( 'The business description for a given store.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Permission callback.
	 *
	 * @throws RouteException If the user is not allowed to make this request.
	 *
	 * @return true|\WP_Error
	 */
	public function is_authorized() {
		try {
			if ( ! current_user_can( 'manage_options' ) ) {
				throw new RouteException( 'woocommerce_rest_invalid_user', __( 'You are not allowed to make this request. Please make sure you are logged in.', 'woo-gutenberg-products-block' ), 403 );
			}
		} catch ( RouteException $error ) {
			return new \WP_Error(
				$error->getErrorCode(),
				$error->getMessage(),
				array( 'status' => $error->getCode() )
			);
		}

		return true;
	}

	/**
	 * Ensure the content and images in patterns are powered by AI.
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$allow_ai_connection = get_option( 'woocommerce_blocks_allow_ai_connection' );

		if ( ! $allow_ai_connection ) {
			return rest_ensure_response(
				$this->error_to_response(
					new \WP_Error(
						'ai_connection_not_allowed',
						__( 'AI content generation is not allowed on this store. Update your store settings if you wish to enable this feature.', 'woo-gutenberg-products-block' )
					)
				)
			);
		}

		$business_description = sanitize_text_field( wp_unslash( $request['business_description'] ) );

		if ( empty( $business_description ) ) {
			$business_description = get_option( 'woo_ai_describe_store_description' );
		}

		$last_business_description = get_option( 'last_business_description_with_ai_content_generated' );

		if ( $last_business_description === $business_description ) {
			return rest_ensure_response(
				$this->prepare_item_for_response(
					[
						'ai_content_generated' => true,
					],
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
			$response = $this->error_to_response( $images );
		} else {
			$populate_patterns = ( new PatternUpdater() )->generate_content( $ai_connection, $token, $images, $business_description );

			if ( is_wp_error( $populate_patterns ) ) {
				$response = $this->error_to_response( $populate_patterns );
			}

			$populate_products = ( new ProductUpdater() )->generate_content( $ai_connection, $token, $images, $business_description );

			if ( is_wp_error( $populate_products ) ) {
				$response = $this->error_to_response( $populate_products );
			}

			if ( true === $populate_patterns && true === $populate_products ) {
				update_option( 'last_business_description_with_ai_content_generated', $business_description );
			}
		}

		if ( ! isset( $response ) ) {
			$response = $this->prepare_item_for_response(
				[
					'ai_content_generated' => true,
				],
				$request
			);
		}

		return rest_ensure_response( $response );
	}
}
