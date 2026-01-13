<?php
/**
 * REST API Offline Payment Methods Controller
 *
 * Handles requests to the /settings/payments/offline-methods endpoint.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\OfflinePaymentMethods;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\OfflinePaymentMethods\Schema\OfflinePaymentMethodSchema;
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
								'description'       => __( 'Country code to retrieve offline payment methods for.', 'woocommerce' ),
								'type'              => 'string',
								'required'          => false,
								'sanitize_callback' => static function ( $value ) {
									return sanitize_text_field( $value );
								},
							),
						)
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
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

		// Transform data to match the new format.
		$response_data = array(
			'id'          => 'payments/offline-methods',
			'title'       => __( 'Offline Payment Methods', 'woocommerce' ),
			'description' => __( 'Manage offline payment methods available for your store.', 'woocommerce' ),
			'values'      => array(),
			'groups'      => array(
				'payment_methods' => array(),
			),
		);

		// Validate input is an array.
		if ( ! is_array( $offline_methods ) ) {
			return new WP_Error(
				'woocommerce_rest_invalid_data',
				__( 'Invalid payment methods data received.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		// Process each offline payment method.
		foreach ( $offline_methods as $method ) {
			// Skip if method is not an array.
			if ( ! is_array( $method ) ) {
				continue;
			}

			$method_id = $method['id'] ?? '';
			if ( empty( $method_id ) || ! is_string( $method_id ) ) {
				continue;
			}

			// Add method to values (current settings/state).
			$enabled_state = false;
			if ( isset( $method['state'] ) && is_array( $method['state'] ) ) {
				$enabled_state = $method['state']['enabled'] ?? false;
			}
			if ( is_array( $enabled_state ) ) {
				$enabled_state = $enabled_state['value'] ?? false;
			}
			if ( is_string( $enabled_state ) ) {
				$enabled_state = wc_string_to_bool( $enabled_state );
			} elseif ( ! is_bool( $enabled_state ) ) {
				$enabled_state = (bool) $enabled_state;
			}
			$response_data['values'][ $method_id ] = $enabled_state;

			// Add complete payment method data to groups.payment_methods.
			$response_data['groups']['payment_methods'][ $method_id ] = array(
				'id'          => $method_id,
				'_order'      => isset( $method['_order'] ) ? absint( $method['_order'] ) : 0,
				'title'       => sanitize_text_field( $method['title'] ?? '' ),
				'description' => wp_kses_post( $method['description'] ?? '' ),
				'icon'        => esc_url_raw( $method['icon'] ?? '' ),
				'state'       => array_map(
					'rest_sanitize_boolean',
					wp_parse_args(
						is_array( $method['state'] ?? null ) ? $method['state'] : array(),
						array(
							'enabled'           => false,
							'account_connected' => false,
							'needs_setup'       => false,
							'test_mode'         => false,
							'dev_mode'          => false,
						)
					)
				),
				'management'  => $this->sanitize_management_field( $method['management'] ?? array() ),
			);
		}

		return rest_ensure_response( $response_data );
	}

	/**
	 * Get offline payment methods data.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error The offline payment methods data or error.
	 * @throws \Exception If there's an error retrieving the data.
	 */
	private function get_offline_payment_methods_data( $request ) {
		$location = sanitize_text_field( $request->get_param( 'location' ) );

		if ( empty( $location ) ) {
			// Fall back to the payments country if no location is provided.
			$location = $this->payments->get_country();
		}

		try {
			$providers = $this->payments->get_payment_providers( $location );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'woocommerce_rest_payment_providers_error', $e->getMessage(), array( 'status' => 500 ) );
		}

		if ( is_wp_error( $providers ) ) {
			return $providers;
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
	 * Get the schema for the current resource.
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
	 * Sanitize the management field data.
	 *
	 * @param mixed $management The management data to sanitize.
	 * @return array Sanitized management array.
	 */
	private function sanitize_management_field( $management ) {
		if ( ! is_array( $management ) ) {
			return array( '_links' => array() );
		}

		$sanitized = array(
			'_links' => array(),
		);

		if ( isset( $management['_links'] ) && is_array( $management['_links'] ) ) {
			foreach ( $management['_links'] as $key => $link ) {
				$sanitized_key = sanitize_key( $key );
				if ( is_array( $link ) && isset( $link['href'] ) ) {
					// Handle link objects with href property.
					$sanitized['_links'][ $sanitized_key ] = array(
						'href' => esc_url_raw( $link['href'] ),
					);
				} elseif ( is_string( $link ) ) {
					// Handle direct URL strings.
					$sanitized['_links'][ $sanitized_key ] = array(
						'href' => esc_url_raw( $link ),
					);
				}
			}
		}

		return $sanitized;
	}
}
