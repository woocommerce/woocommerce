<?php
/**
 * REST API Payment Gateways Controller
 *
 * Handles requests to the /settings/payment-gateways endpoint.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema\AbstractPaymentGatewaySettingsSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema\BacsGatewaySettingsSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema\CodGatewaySettingsSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema\PaymentGatewaySettingsSchema;
use WC_Payment_Gateway;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Payment Gateways Controller Class.
 *
 * @extends AbstractController
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings/payment-gateways';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected string $post_type = 'payment_gateways';

	/**
	 * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 *
	 * @return array
	 */
	protected function get_schema(): array {
		// Use generic schema for schema generation.
		$schema = new PaymentGatewaySettingsSchema();
		return $schema->get_item_schema();
	}

	/**
	 * Register the routes for payment gateways.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'values' => array(
							'description' => __( 'Payment gateway field values to update.', 'woocommerce' ),
							'type'        => 'object',
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'string',
						'pattern'     => '^[\w-]+$',
					),
				),
			)
		);
	}

	/**
	 * Get a single payment gateway.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$id               = $request['id'];
		$payment_gateways = WC()->payment_gateways->payment_gateways();

		if ( ! isset( $payment_gateways[ $id ] ) ) {
			return new WP_Error( 'woocommerce_rest_payment_gateway_invalid_id', __( 'Invalid payment gateway ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$gateway = $payment_gateways[ $id ];

		// Get gateway-specific schema.
		$schema = $this->get_schema_for_gateway( $id );

		$data = $schema->get_item_response( $gateway, $request );

		return rest_ensure_response( $data );
	}

	/**
	 * Check if a given request has access to read payment gateways.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( $this->post_type, 'read' ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to update payment gateways.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'payment_gateways', 'edit' ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}

	/**
	 * Get a gateway based on the current request object.
	 *
	 * @param string $id Gateway ID.
	 *
	 * @return WC_Payment_Gateway|null
	 */
	private function get_payment_gateway( $id ): ?WC_Payment_Gateway {
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		return $payment_gateways[ $id ] ?? null;
	}

	/**
	 * Get the appropriate schema for a payment gateway.
	 *
	 * @param string $gateway_id Gateway ID.
	 * @return AbstractPaymentGatewaySettingsSchema
	 */
	private function get_schema_for_gateway( string $gateway_id ): AbstractPaymentGatewaySettingsSchema {
		switch ( $gateway_id ) {
			case 'bacs':
				return new BacsGatewaySettingsSchema();
			case 'cod':
				return new CodGatewaySettingsSchema();
			default:
				// Use generic schema for unknown gateways.
				return new PaymentGatewaySettingsSchema();
		}
	}

	/**
	 * Update a payment gateway's settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$id      = $request['id'];
		$gateway = $this->get_payment_gateway( $id );

		if ( ! $gateway ) {
			return new WP_Error(
				'woocommerce_rest_payment_gateway_invalid_id',
				__( 'Invalid payment gateway ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		// Get gateway-specific schema.
		$schema = $this->get_schema_for_gateway( $id );

		// Get field values from the values parameter.
		$params           = $request->get_params();
		$values_to_update = $params['values'] ?? null;

		if ( empty( $values_to_update ) || ! is_array( $values_to_update ) ) {
			return new WP_Error(
				'rest_missing_callback_param',
				__( 'Missing parameter(s): values', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Handle top-level gateway fields from within values.
		$gateway->init_form_fields();

		if ( isset( $values_to_update['enabled'] ) ) {
			$gateway->enabled             = wc_bool_to_string( $values_to_update['enabled'] );
			$gateway->settings['enabled'] = $gateway->enabled;
			unset( $values_to_update['enabled'] );
		}

		if ( isset( $values_to_update['title'] ) ) {
			$gateway->title             = sanitize_text_field( $values_to_update['title'] );
			$gateway->settings['title'] = $gateway->title;
			unset( $values_to_update['title'] );
		}

		if ( isset( $values_to_update['description'] ) ) {
			$gateway->description             = wp_kses_post( $values_to_update['description'] );
			$gateway->settings['description'] = $gateway->description;
			unset( $values_to_update['description'] );
		}

		if ( isset( $values_to_update['order'] ) ) {
			$order                = absint( $values_to_update['order'] );
			$gateway_order        = (array) get_option( 'woocommerce_gateway_order', array() );
			$gateway_order[ $id ] = $order;
			update_option( 'woocommerce_gateway_order', $gateway_order );
			unset( $values_to_update['order'] );
		}

		// Separate standard fields from special fields.
		$standard_values = array();
		$special_values  = array();

		foreach ( $values_to_update as $key => $value ) {
			// Check if this is a special field.
			if ( $schema->is_special_field( $key ) ) {
				$special_values[ $key ] = $value;
			} elseif ( isset( $gateway->form_fields[ $key ] ) ) {
				$standard_values[ $key ] = $value;
			}
			// Silently skip unknown fields.
		}

		// Validate and sanitize standard settings.
		$validated_settings = $schema->validate_and_sanitize_settings(
			$gateway,
			$standard_values
		);

		if ( is_wp_error( $validated_settings ) ) {
			return $validated_settings;
		}

		// Validate and sanitize special fields.
		$validated_special = $schema->validate_and_sanitize_special_fields(
			$gateway,
			$special_values
		);

		if ( is_wp_error( $validated_special ) ) {
			return $validated_special;
		}

		// Update standard settings.
		foreach ( $validated_settings as $key => $value ) {
			$gateway->settings[ $key ] = $value;
		}

		// Save standard settings to database.
		update_option( $gateway->get_option_key(), $gateway->settings );

		// Update special fields.
		$schema->update_special_fields( $gateway, $validated_special );

		// Return updated gateway data.
		$data = $schema->get_item_response( $gateway, $request );
		return rest_ensure_response( $data );
	}

	/**
	 * Get the item response for a payment gateway.
	 *
	 * @param WC_Payment_Gateway $item    Payment gateway object.
	 * @param WP_REST_Request    $request Request object.
	 * @return array The item response.
	 */
	protected function get_item_response( $item, WP_REST_Request $request ): array {
		// Get gateway-specific schema.
		$schema = $this->get_schema_for_gateway( $item->id );

		return $schema->get_item_response( $item, $request );
	}
}
