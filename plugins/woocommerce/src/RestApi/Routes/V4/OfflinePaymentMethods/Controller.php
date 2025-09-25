<?php
/**
 * REST API Offline Payment Methods Controller
 *
 * Handles requests to the /settings/payments/offline-methods endpoint.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\OfflinePaymentMethods;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\RestApi\Routes\V4\AbstractController;
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
	protected $rest_base = 'settings/payments/offline-methods';

	/**
	 * PaymentsProviders instance.
	 *
	 * @var PaymentsProviders
	 */
	protected $payments_providers;

	/**
	 * Schema instance.
	 *
	 * @var OfflinePaymentMethodSchema
	 */
	protected $item_schema;

	/**
	 * Initialize the controller.
	 *
	 * @param PaymentsProviders           $payments_providers PaymentsProviders service.
	 * @param OfflinePaymentMethodSchema $schema             Schema class.
	 * @internal
	 */
	public function __construct( PaymentsProviders $payments_providers, OfflinePaymentMethodSchema $schema ) {
		$this->payments_providers = $payments_providers;
		$this->item_schema        = $schema;
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
			$offline_methods = $this->get_offline_payment_methods_data();
		} catch ( \Exception $e ) {
			return new WP_Error( 
				'woocommerce_rest_offline_payment_methods_error', 
				$e->getMessage(), 
				array( 'status' => 500 ) 
			);
		}

		return rest_ensure_response( $offline_methods );
	}

	/**
	 * Get offline payment methods data.
	 *
	 * @return array The offline payment methods data.
	 * @throws \Exception If there's an error retrieving the data.
	 */
	private function get_offline_payment_methods_data(): array {
		$base_location    = wc_get_base_location();
		$location         = $base_location['country'];
		$offline_gateways = $this->payments_providers->get_offline_payment_methods_gateways();
		$offline_methods  = array();

		foreach ( $offline_gateways as $gateway ) {
			$provider_data = $this->payments_providers->get_payment_provider_details_from_gateway( $gateway, $location );
			if ( $provider_data && PaymentsProviders::TYPE_OFFLINE_PM === $provider_data['_type'] ) {
				$offline_methods[] = $provider_data;
			}
		}

		// Sort by _order field
		usort(
			$offline_methods,
			function( $a, $b ) {
				return ( $a['_order'] ?? 0 ) <=> ( $b['_order'] ?? 0 );
			}
		);

		return $offline_methods;
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
}