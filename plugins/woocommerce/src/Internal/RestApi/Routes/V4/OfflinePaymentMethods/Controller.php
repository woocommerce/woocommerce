<?php
/**
 * REST API Offline Payment Methods Controller
 *
 * Handles requests to the /payments/offline-methods endpoint.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\OfflinePaymentMethods;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Offline Payment Methods Controller Class.
 *
 * @extends AbstractController
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'payments/offline-methods';

	/**
	 * Payments instance.
	 *
	 * @var Payments
	 */
	protected $payments;

	/**
	 * Schema instance.
	 *
	 * @var OfflinePaymentMethodSchema
	 */
	protected $item_schema;

	/**
	 * Initialize the controller.
	 *
	 * @param Payments                   $payments Payments service.
	 * @param OfflinePaymentMethodSchema $schema   Schema class.
	 * @internal
	 */
	final public function init( Payments $payments, OfflinePaymentMethodSchema $schema ) {
		$this->payments    = $payments;
		$this->item_schema = $schema;
	}

	/**
	 * Register the routes for offline payment methods.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array_merge(
						$this->get_collection_params(),
						array(
							'location' => array(
								'description' => __( 'Country code to retrieve offline payment methods for.', 'woocommerce' ),
								'type'        => 'string',
								'required'    => false,
							),
						)
					),
				),
				'schema' => array( $this, 'get_collection_schema' ),
			)
		);
	}

	/**
	 * Check permissions for reading offline payment methods.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'payment_gateways', 'read' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_read',
				__( 'Sorry, you cannot list resources.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get offline payment methods.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		try {
			$offline_methods = $this->get_offline_payment_methods_data( $request );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'woocommerce_rest_offline_payment_methods_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		if ( is_wp_error( $offline_methods ) ) {
			return $offline_methods;
		}

		$data = array();
		foreach ( $offline_methods as $method ) {
			$prepared_item = $this->prepare_item_for_response( $method, $request );
			$data[]        = $this->prepare_response_for_collection( $prepared_item );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get offline payment methods data.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array The offline payment methods data.
	 * @throws \Exception If there's an error retrieving the data.
	 */
	private function get_offline_payment_methods_data( $request ) {
		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the payments country if no location is provided.
			$location = $this->payments->get_country();
		}

		try {
			$providers = $this->payments->get_payment_providers( $location );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'woocommerce_rest_payment_providers_error', $e->getMessage(), array( 'status' => 500 ) );
		}

		// Retrieve the offline PMs from the main providers list.
		$offline_payment_providers = array_values(
			array_filter(
				$providers,
				fn( $provider ) => isset( $provider['_type'] ) && PaymentsProviders::TYPE_OFFLINE_PM === $provider['_type']
			)
		);

		return $offline_payment_providers;
	}

	/**
	 * Get the schema for offline payment methods collection, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_collection_schema() {
		$schema = array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'offline_payment_methods',
			'type'    => 'array',
			'items'   => $this->item_schema->get_item_schema(),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the item schema for individual payment methods.
	 *
	 * @return array
	 */
	protected function get_schema(): array {
		return $this->item_schema->get_item_schema();
	}

	/**
	 * Get the item response for a single payment method.
	 *
	 * @param mixed           $item Payment method data.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_item_response( $item, WP_REST_Request $request ): array {
		return $this->item_schema->get_item_response( $item, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed            $item Payment method data.
	 * @param WP_REST_Request  $request Request object.
	 * @param WP_REST_Response $response Response object.
	 * @return array Links for the given payment method.
	 */
	protected function prepare_links( $item, WP_REST_Request $request, WP_REST_Response $response ): array {
		$links = array();

		if ( isset( $item['management']['_links']['settings']['href'] ) ) {
			$links['settings'] = array(
				'href' => $item['management']['_links']['settings']['href'],
			);
		}

		return $links;
	}
}
