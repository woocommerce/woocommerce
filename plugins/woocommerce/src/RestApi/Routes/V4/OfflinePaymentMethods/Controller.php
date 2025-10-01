<?php
/**
 * REST API Offline Payment Methods Controller
 *
 * Handles requests to the /payments/offline-methods endpoint.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\OfflinePaymentMethods;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the offline payment method.', 'woocommerce' ),
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
	 * Check if a given request has access to read a single payment gateway.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Get a single offline payment gateway.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$gateway = $this->get_offline_gateway( $request );

		if ( is_null( $gateway ) ) {
			return new WP_Error(
				'woocommerce_rest_payment_gateway_invalid',
				__( 'Resource does not exist.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$gateway_data = $this->prepare_item_for_response( $gateway, $request );
		return rest_ensure_response( $gateway_data );
	}

	/**
	 * Check whether a given request has permission to edit payment gateways.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'payment_gateways', 'edit' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_edit',
				__( 'Sorry, you are not allowed to edit this resource.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Update a single offline payment method.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$gateway = $this->get_offline_gateway( $request );

		if ( is_null( $gateway ) ) {
			return new WP_Error(
				'woocommerce_rest_payment_gateway_invalid',
				__( 'Resource does not exist.', 'woocommerce' ),
				array( 'status' => 404 )
			);
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
				return new WP_Error(
					'rest_setting_value_invalid',
					__( 'An invalid setting value was passed.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}
		}

		// Update if this method is enabled or not.
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
		update_option( $gateway->get_option_key(), apply_filters( 'woocommerce_gateway_' . $gateway->id . '_settings_values', $settings, $gateway ) );

		// Update order.
		if ( isset( $request['order'] ) ) {
			$order                 = (array) get_option( 'woocommerce_gateway_order' );
			$order[ $gateway->id ] = absint( $request['order'] );
			update_option( 'woocommerce_gateway_order', $order );
			$gateway->order = absint( $request['order'] );
		}

		$gateway_data = $this->prepare_item_for_response( $gateway, $request );
		return rest_ensure_response( $gateway_data );
	}

	/**
	 * Get an offline payment gateway based on the current request object.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return \WC_Payment_Gateway|null
	 */
	private function get_offline_gateway( $request ) {
		$gateway          = null;
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$offline_ids      = array( 'bacs', 'cheque', 'cod' );

		foreach ( $payment_gateways as $payment_gateway_id => $payment_gateway ) {
			if ( $request['id'] !== $payment_gateway_id ) {
				continue;
			}

			// Only allow offline payment gateways.
			if ( ! in_array( $payment_gateway_id, $offline_ids, true ) ) {
				continue;
			}

			$payment_gateway->id = $payment_gateway_id;
			$gateway             = $payment_gateway;
			break;
		}

		return $gateway;
	}

	/**
	 * Validate text based settings.
	 *
	 * @param string $value The submitted value.
	 * @param array  $setting The field settings.
	 * @return string
	 */
	public function validate_setting_text_field( $value, $setting ) {
		$value = is_null( $value ) ? '' : $value;
		return wp_kses_post( trim( stripslashes( $value ) ) );
	}

	/**
	 * Validate textarea based settings.
	 *
	 * @param string $value The submitted value.
	 * @param array  $setting The field settings.
	 * @return string
	 */
	public function validate_setting_textarea_field( $value, $setting ) {
		$value = is_null( $value ) ? '' : $value;
		return wp_kses(
			trim( stripslashes( $value ) ),
			array_merge(
				array(
					'iframe' => array(
						'src'   => true,
						'style' => true,
						'id'    => true,
						'class' => true,
					),
				),
				wp_kses_allowed_html( 'post' )
			)
		);
	}

	/**
	 * Validate checkbox based settings (with support for yes/no strings).
	 *
	 * @param string|bool $value The submitted value.
	 * @param array       $setting The field settings.
	 * @return string
	 */
	public function validate_setting_checkbox_field( $value, $setting ) {
		return wc_bool_to_string( $value );
	}

	/**
	 * Validate select based settings.
	 *
	 * @param string $value The submitted value.
	 * @param array  $setting The field settings.
	 * @return string|WP_Error
	 */
	public function validate_setting_select_field( $value, $setting ) {
		if ( array_key_exists( $value, $setting['options'] ) ) {
			return $value;
		} else {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'woocommerce' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Validate multiselect based settings.
	 *
	 * @param array|string $values The submitted values.
	 * @param array        $setting The field settings.
	 * @return array|WP_Error
	 */
	public function validate_setting_multiselect_field( $values, $setting ) {
		if ( empty( $values ) ) {
			return array();
		}

		if ( ! is_array( $values ) ) {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$final_values = array();
		foreach ( $values as $value ) {
			if ( array_key_exists( $value, $setting['options'] ) ) {
				$final_values[] = $value;
			}
		}

		return $final_values;
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
