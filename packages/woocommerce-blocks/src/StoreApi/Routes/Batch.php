<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Batch Route class.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
class Batch extends AbstractRoute implements RouteInterface {
	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/batch';
	}

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Get arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			'callback'            => [ $this, 'get_response' ],
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'args'                => array(
				'validation' => array(
					'type'    => 'string',
					'enum'    => array( 'require-all-validate', 'normal' ),
					'default' => 'normal',
				),
				'requests'   => array(
					'required' => true,
					'type'     => 'array',
					'maxItems' => 25,
					'items'    => array(
						'type'       => 'object',
						'properties' => array(
							'method'  => array(
								'type'    => 'string',
								'enum'    => array( 'POST', 'PUT', 'PATCH', 'DELETE' ),
								'default' => 'POST',
							),
							'path'    => array(
								'type'     => 'string',
								'required' => true,
							),
							'body'    => array(
								'type'                 => 'object',
								'properties'           => array(),
								'additionalProperties' => true,
							),
							'headers' => array(
								'type'                 => 'object',
								'properties'           => array(),
								'additionalProperties' => array(
									'type'  => array( 'string', 'array' ),
									'items' => array(
										'type' => 'string',
									),
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get the route response.
	 *
	 * @see WP_REST_Server::serve_batch_request_v1
	 * https://developer.wordpress.org/reference/classes/wp_rest_server/serve_batch_request_v1/
	 *
	 * @throws RouteException On error.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_response( WP_REST_Request $request ) {
		try {
			foreach ( $request['requests'] as $args ) {
				if ( ! stristr( $args['path'], 'wc/store' ) ) {
					throw new RouteException( 'woocommerce_rest_invalid_path', __( 'Invalid path provided.', 'woocommerce' ), 400 );
				}
			}
			$response = rest_get_server()->serve_batch_request_v1( $request );
		} catch ( RouteException $error ) {
			$response = $this->get_route_error_response( $error->getErrorCode(), $error->getMessage(), $error->getCode(), $error->getAdditionalData() );
		} catch ( \Exception $error ) {
			$response = $this->get_route_error_response( 'unknown_server_error', $error->getMessage(), 500 );
		}

		if ( is_wp_error( $response ) ) {
			$response = $this->error_to_response( $response );
		}

		$response->header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response->header( 'X-WC-Store-API-Nonce-Timestamp', time() );
		$response->header( 'X-WC-Store-API-User', get_current_user_id() );

		return $response;
	}
}
