<?php
/**
 * Product Attributes route.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

/**
 * ProductAttributesById class.
 */
class ProductAttributesById extends AbstractRoute {
	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/products/attributes/(?P<id>[\d]+)';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			'args'   => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
				),
			),
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => array(
					'context' => $this->get_context_param(
						array(
							'default' => 'view',
						)
					),
				),
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get a single item.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$object = wc_get_attribute( (int) $request['id'] );

		if ( ! $object || 0 === $object->id ) {
			throw new RouteException( 'woocommerce_rest_attribute_invalid_id', __( 'Invalid attribute ID.', 'woocommerce' ), 404 );
		}

		$data     = $this->prepare_item_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}
}
