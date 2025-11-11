<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Controller for the REST endpoint to expose shipping providers in the WooCommerce REST API.
 */
class ProvidersRestController extends RestApiControllerBase {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'fulfillments/providers';

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'fulfillments_providers';
	}

	/**
	 * Handle the woocommerce_rest_api_get_rest_namespaces filter
	 * to add ourselves to the list of REST API controllers registered by WooCommerce.
	 *
	 * @param array $namespaces The original list of WooCommerce REST API namespaces/controllers.
	 * @return array The updated list of WooCommerce REST API namespaces/controllers.
	 */
	public function handle_woocommerce_rest_api_get_rest_namespaces( array $namespaces ): array {
		$namespaces['wc/v4'][ $this->get_rest_api_namespace() ] = static::class;
		return $namespaces;
	}

	/**
	 * Register the routes for shipping providers.
	 */
	public function register_routes() {
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_providers' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Check permissions for accessing shipping providers.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool
	 */
	public function check_permissions( WP_REST_Request $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get all shipping providers.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response
	 */
	public function get_providers( WP_REST_Request $request ) {
		$providers = FulfillmentUtils::get_shipping_providers_object();
		return rest_ensure_response( $providers );
	}

	/**
	 * Get the shipping providers schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'shipping_providers',
			'type'       => 'object',
			'properties' => array(
				'label' => array(
					'description' => __( 'Provider display name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'icon'  => array(
					'description' => __( 'Provider icon URL.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'value' => array(
					'description' => __( 'Provider key/value.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'url'   => array(
					'description' => __( 'Tracking URL template.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $schema;
	}
}
