<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * REST API Payment Gateways controller
 *
 * Handles route registration, permissions, CRUD operations, and schema definition.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\PaymentGateways;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\RestApi\Routes\V4\PaymentGateways\Schema\PaymentGatewaySchema;
use WC_Payment_Gateway;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * PaymentGateways Controller.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'payment-gateways';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected string $post_type = 'payment_gateways';

	/**
	 * Schema class for this route.
	 *
	 * @var PaymentGatewaySchema
	 */
	protected PaymentGatewaySchema $item_schema;

	/**
	 * Initialize the controller.
	 *
	 * @param PaymentGatewaySchema $item_schema Payment gateway schema class.
	 * @internal
	 */
	final public function init( PaymentGatewaySchema $item_schema ): void {
		$this->item_schema = $item_schema;
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
	 * Register the routes for payment gateways.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
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
	 * Check if a given request has access to read a payment gateway.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
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
	 * Get a collection of payment gateways.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$items            = array();

		foreach ( $payment_gateways as $payment_gateway_id => $payment_gateway ) {
			$payment_gateway->id = $payment_gateway_id;
			$response            = $this->prepare_item_for_response( $payment_gateway, $request );
			$items[]             = $this->prepare_response_for_collection( $response );
		}

		return rest_ensure_response( $items );
	}

	/**
	 * Get a single payment gateway.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$gateway = $this->get_gateway( $request );

		if ( is_null( $gateway ) ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		return $this->prepare_item_for_response( $gateway, $request );
	}

	/**
	 * Update a single payment gateway.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$gateway = $this->get_gateway( $request );

		if ( is_null( $gateway ) ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		// Get settings.
		$gateway->init_form_fields();
		$settings = $gateway->settings;

		// Update settings.
		if ( isset( $request['settings'] ) ) {
			$errors_found = false;
			foreach ( $gateway->form_fields as $key => $field ) {
				if ( isset( $request['settings'][ $key ] ) ) {
					if ( is_callable( array( $this, 'validate_setting_' . $field['type'] . '_field' ) ) ) {
						$value = $this->{'validate_setting_' . $field['type'] . '_field'}( $request['settings'][ $key ], $field );
					} else {
						$value = $this->validate_setting_text_field( $request['settings'][ $key ], $field );
					}
					if ( is_wp_error( $value ) ) {
						$errors_found = true;
						break;
					}
					$settings[ $key ] = $value;
				}
			}

			if ( $errors_found ) {
				return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'woocommerce' ), array( 'status' => 400 ) );
			}
		}

		// Update enabled status.
		if ( isset( $request['enabled'] ) ) {
			$settings['enabled'] = wc_bool_to_string( $request['enabled'] );
			$gateway->enabled    = $settings['enabled'];
		}

		// Update title.
		if ( isset( $request['title'] ) ) {
			$settings['title'] = $this->validate_setting_text_field( $request['title'], $gateway->form_fields['title'] ?? array() );
			$gateway->title    = $settings['title'];
		}

		// Update description.
		if ( isset( $request['description'] ) ) {
			$settings['description'] = $this->validate_setting_text_field( $request['description'], $gateway->form_fields['description'] ?? array() );
			$gateway->description    = $settings['description'];
		}

		// Update options.
		$gateway->settings = $settings;
		/**
		 * Filter gateway settings before saving.
		 *
		 * The dynamic portion of the hook name, `$gateway->id`, refers to the gateway ID.
		 *
		 * @param array              $settings Gateway settings.
		 * @param WC_Payment_Gateway $gateway  Payment gateway instance.
		 * @since 10.2.0
		 */
		update_option( $gateway->get_option_key(), apply_filters( "woocommerce_gateway_{$gateway->id}_settings_values", $settings, $gateway ) );

		// Update order.
		if ( isset( $request['order'] ) ) {
			$order                 = (array) get_option( 'woocommerce_gateway_order' );
			$order[ $gateway->id ] = absint( $request['order'] );
			update_option( 'woocommerce_gateway_order', $order );
			$gateway->order = absint( $request['order'] );
		}

		return $this->prepare_item_for_response( $gateway, $request );
	}

	/**
	 * Get a gateway based on the current request object.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WC_Payment_Gateway|null
	 */
	protected function get_gateway( WP_REST_Request $request ): ?WC_Payment_Gateway {
		$gateway          = null;
		$payment_gateways = WC()->payment_gateways->payment_gateways();

		foreach ( $payment_gateways as $payment_gateway_id => $payment_gateway ) {
			if ( $request['id'] !== $payment_gateway_id ) {
				continue;
			}
			$payment_gateway->id = $payment_gateway_id;
			$gateway             = $payment_gateway;
		}

		return $gateway;
	}

	/**
	 * Prepare a payment gateway for response.
	 *
	 * @param WC_Payment_Gateway $gateway Payment gateway object.
	 * @param WP_REST_Request    $request Request object.
	 * @return array
	 */
	protected function get_item_response( $gateway, WP_REST_Request $request ): array {
		return $this->item_schema->get_item_response( $gateway, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed            $item WordPress representation of the item.
	 * @param WP_REST_Request  $request Request object.
	 * @param WP_REST_Response $response Response object.
	 * @return array
	 */
	protected function prepare_links( $item, WP_REST_Request $request, WP_REST_Response $response ): array {
		return array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $item->id ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);
	}

	/**
	 * TODO: Validation rules could possibly be implemented on an abstract level and overriden if needed.
	 *
	 * Validate text based settings.
	 *
	 * @param mixed $value  Field value.
	 * @param array $setting Setting data.
	 * @return string
	 */
	public function validate_setting_text_field( $value, array $setting ): string {
		$value = is_null( $value ) ? '' : $value;
		return wp_kses_post( trim( stripslashes( $value ) ) );
	}

	/**
	 * Validate multiselect based settings.
	 *
	 * @param array|string $values  The submitted values.
	 * @param array        $setting The field settings.
	 * @return array|WP_Error
	 */
	public function validate_setting_multiselect_field( $values, array $setting ) {
		if ( empty( $values ) ) {
			return array();
		}

		if ( ! is_array( $values ) ) {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$valid_keys = $this->flatten_options_keys( $setting['options'] );

		$final_values = array();
		foreach ( $values as $value ) {
			if ( in_array( $value, $valid_keys, true ) ) {
				$final_values[] = $value;
			}
		}

		return $final_values;
	}

	/**
	 * Helper: Recursively flatten option keys.
	 *
	 * @param array $options Nested options array.
	 * @return array Flat list of valid keys.
	 */
	private function flatten_options_keys( array $options ): array {
		$keys = array();

		foreach ( $options as $key => $value ) {
			if ( is_array( $value ) ) {
				$keys = array_merge( $keys, $this->flatten_options_keys( $value ) );
			} else {
				$keys[] = $key;
			}
		}

		return $keys;
	}

	/**
	 * Validate select based settings.
	 *
	 * @param string $value  Field value.
	 * @param array  $setting Setting data.
	 * @return string|WP_Error
	 */
	public function validate_setting_select_field( string $value, array $setting ) {
		if ( array_key_exists( $value, $setting['options'] ) ) {
			return $value;
		} else {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'woocommerce' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Validate checkbox based settings.
	 *
	 * @since 3.0.0
	 * @param string $value Value.
	 * @param array  $setting Setting.
	 * @return string|WP_Error
	 */
	public function validate_setting_checkbox_field( $value, $setting ) {
		if ( in_array( $value, array( 'yes', 'no' ), true ) ) {
			return $value;
		} elseif ( empty( $value ) ) {
			return isset( $setting['default'] ) ? $setting['default'] : 'no';
		} else {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'woocommerce' ), array( 'status' => 400 ) );
		}
	}


	/**
	 * Validate textarea based settings.
	 *
	 * TODO: Consider making this more restrictive (e.g., wp_kses with limited tags or sanitize_textarea_field) to prevent potential XSS if payment gateway settings don't need HTML.
	 *
	 * @param mixed $value  Field value.
	 * @param array $setting Setting data.
	 * @return string
	 */
	public function validate_setting_textarea_field( $value, array $setting ) {
		$value = is_null( $value ) ? '' : $value;
		return wp_kses_post( trim( stripslashes( $value ) ) );
	}
}
