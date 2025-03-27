<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Automattic\WooCommerce\Internal\Admin\WCPayPromotion\Init as WCPayPromotion;

/**
 * Controller for the REST endpoints to service the Payments settings page.
 */
class PaymentsRestController extends RestApiControllerBase {

	/**
	 * The root namespace for the JSON REST API endpoints.
	 *
	 * @var string
	 */
	protected string $route_namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'settings/payments';

	/**
	 * The payments settings page service.
	 *
	 * @var Payments
	 */
	private Payments $payments;

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-admin';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 *
	 * @param bool $override Whether to override the existing routes. Useful for testing.
	 */
	public function register_routes( bool $override = false ) {
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/woopay-eligibility',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_woopay_eligibility' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/country',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'set_country' ),
					'validation_callback' => 'rest_validate_request_arg',
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'The ISO3166 alpha-2 country code to save for the current user.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
							'required'          => true,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/providers',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_providers' ),
					'validation_callback' => 'rest_validate_request_arg',
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => __( 'ISO3166 alpha-2 country code. Defaults to WooCommerce\'s base location country.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
					),
				),
				'schema' => fn() => $this->get_schema_for_get_payment_providers(),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/providers/order',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'update_providers_order' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'order_map' => array(
							'description'       => __( 'A map of provider ID to integer values representing the sort order.', 'woocommerce' ),
							'type'              => 'object',
							'required'          => true,
							'validate_callback' => fn( $value ) => $this->check_providers_order_map_arg( $value ),
							'sanitize_callback' => fn( $value ) => $this->sanitize_providers_order_arg( $value ),
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/suggestion/(?P<id>[\w\d\-]+)/attach',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'attach_payment_extension_suggestion' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/suggestion/(?P<id>[\w\d\-]+)/hide',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'hide_payment_extension_suggestion' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/suggestion/(?P<suggestion_id>[\w\d\-]+)/incentive/(?P<incentive_id>[\w\d\-]+)/dismiss',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'dismiss_payment_extension_suggestion_incentive' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'context' => array(
							'description'       => __( 'The context ID for which to dismiss the incentive. If not provided, will dismiss the incentive for all contexts.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			),
			$override
		);
	}

	/**
	 * Initialize the class instance.
	 *
	 * @param Payments $payments The payments settings page service.
	 *
	 * @internal
	 */
	final public function init( Payments $payments ): void {
		$this->payments = $payments;
	}

	/**
	 * Get the payment providers for the given location.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_Error|WP_REST_Response
	 */
	protected function get_providers( WP_REST_Request $request ) {
		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		try {
			$providers = $this->payments->get_payment_providers( $location );
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_rest_payment_providers_error', $e->getMessage(), array( 'status' => 500 ) );
		}

		try {
			$suggestions = $this->get_extension_suggestions( $location );
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_rest_payment_providers_error', $e->getMessage(), array( 'status' => 500 ) );
		}

		// Separate the offline PMs from the main providers list.
		$offline_payment_providers = array_values(
			array_filter(
				$providers,
				fn( $provider ) => PaymentProviders::TYPE_OFFLINE_PM === $provider['_type']
			)
		);
		$providers                 = array_values(
			array_filter(
				$providers,
				fn( $provider ) => PaymentProviders::TYPE_OFFLINE_PM !== $provider['_type']
			)
		);

		$response = array(
			'providers'               => $providers,
			'offline_payment_methods' => $offline_payment_providers,
			'suggestions'             => $suggestions,
			'suggestion_categories'   => $this->payments->get_payment_extension_suggestion_categories(),
		);

		return rest_ensure_response( $this->prepare_payment_providers_response( $response ) );
	}

	/**
	 * Set the country for the payment providers.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	protected function set_country( WP_REST_Request $request ) {
		$location = $request->get_param( 'location' );

		$result = $this->payments->set_country( $location );

		return rest_ensure_response( array( 'success' => $result ) );
	}

	/**
	 * Update the payment providers order.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	protected function update_providers_order( WP_REST_Request $request ) {
		$order_map = $request->get_param( 'order_map' );

		$result = $this->payments->update_payment_providers_order_map( $order_map );

		return rest_ensure_response( array( 'success' => $result ) );
	}

	/**
	 * Attach a payment extension suggestion.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	protected function attach_payment_extension_suggestion( WP_REST_Request $request ) {
		$suggestion_id = $request->get_param( 'id' );

		try {
			$result = $this->payments->attach_payment_extension_suggestion( $suggestion_id );
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_rest_payment_extension_suggestion_error', $e->getMessage(), array( 'status' => 400 ) );
		}

		return rest_ensure_response( array( 'success' => $result ) );
	}

	/**
	 * Hide a payment extension suggestion.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	protected function hide_payment_extension_suggestion( WP_REST_Request $request ) {
		$suggestion_id = $request->get_param( 'id' );

		try {
			$result = $this->payments->hide_payment_extension_suggestion( $suggestion_id );
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_rest_payment_extension_suggestion_error', $e->getMessage(), array( 'status' => 400 ) );
		}

		return rest_ensure_response( array( 'success' => $result ) );
	}

	/**
	 * Dismiss a payment extension suggestion incentive.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	protected function dismiss_payment_extension_suggestion_incentive( WP_REST_Request $request ) {
		$suggestion_id = $request->get_param( 'suggestion_id' );
		$incentive_id  = $request->get_param( 'incentive_id' );
		$context       = $request->get_param( 'context' ) ?? 'all';

		try {
			$result = $this->payments->dismiss_extension_suggestion_incentive( $suggestion_id, $incentive_id, $context );
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_rest_payment_extension_suggestion_incentive_error', $e->getMessage(), array( 'status' => 400 ) );
		}

		return rest_ensure_response( array( 'success' => $result ) );
	}

	/**
	 * Get the payment extension suggestions (other) for the given location.
	 *
	 * @param string $location The location for which the suggestions are being fetched.
	 *
	 * @return array[]   The payment extension suggestions for the given location,
	 *                   excluding the ones part of the main providers list.
	 * @throws Exception If there are malformed or invalid suggestions.
	 */
	private function get_extension_suggestions( string $location ): array {
		// If the requesting user can't install plugins, we don't suggest any extensions.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return array();
		}

		$suggestions = $this->payments->get_payment_extension_suggestions( $location );

		return $suggestions['other'] ?? array();
	}

	/**
	 * General permissions check for payments settings REST API endpoint.
	 *
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 * @return bool|WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or False if no error is available for the request method.
	 */
	private function check_permissions( WP_REST_Request $request ) {
		$context = 'read';
		if ( 'POST' === $request->get_method() ) {
			$context = 'edit';
		} elseif ( 'DELETE' === $request->get_method() ) {
			$context = 'delete';
		}

		if ( wc_rest_check_manager_permissions( 'payment_gateways', $context ) ) {
			return true;
		}

		$error_information = $this->get_authentication_error_by_method( $request->get_method() );
		if ( is_null( $error_information ) ) {
			return false;
		}

		return new WP_Error(
			$error_information['code'],
			$error_information['message'],
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Validate the location argument.
	 *
	 * @param mixed           $value   Value of the argument.
	 * @param WP_REST_Request $request The current request object.
	 *
	 * @return WP_Error|true True if the location argument is valid, otherwise a WP_Error object.
	 */
	private function check_location_arg( $value, WP_REST_Request $request ) {
		// If the 'location' argument is not a string return an error.
		if ( ! is_string( $value ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The location argument must be a string.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		// Get the registered attributes for this endpoint request.
		$attributes = $request->get_attributes();

		// Grab the location param schema.
		$args = $attributes['args']['location'];

		// If the location param doesn't match the regex pattern then we should return an error as well.
		if ( ! preg_match( '/^' . $args['pattern'] . '$/', $value ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The location argument must be a valid ISO3166 alpha-2 country code.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Validate the providers order map argument.
	 *
	 * @param mixed $value Value of the argument.
	 *
	 * @return WP_Error|true True if the providers order map argument is valid, otherwise a WP_Error object.
	 */
	private function check_providers_order_map_arg( $value ) {
		if ( ! is_array( $value ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The ordering argument must be an object.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		foreach ( $value as $provider_id => $order ) {
			if ( ! is_string( $provider_id ) || ! is_numeric( $order ) ) {
				return new WP_Error( 'rest_invalid_param', esc_html__( 'The ordering argument must be an object with provider IDs as keys and numeric values as values.', 'woocommerce' ), array( 'status' => 400 ) );
			}

			if ( sanitize_key( $provider_id ) !== $provider_id ) {
				return new WP_Error( 'rest_invalid_param', esc_html__( 'The provider ID must be a valid string.', 'woocommerce' ), array( 'status' => 400 ) );
			}

			if ( false === filter_var( $order, FILTER_VALIDATE_INT ) ) {
				return new WP_Error( 'rest_invalid_param', esc_html__( 'The order value must be an integer.', 'woocommerce' ), array( 'status' => 400 ) );
			}
		}

		return true;
	}

	/**
	 * Sanitize the providers ordering argument.
	 *
	 * @param array $value Value of the argument.
	 *
	 * @return array
	 */
	private function sanitize_providers_order_arg( array $value ): array {
		// Sanitize the ordering object to ensure that the order values are integers and the provider IDs are safe strings.
		foreach ( $value as $provider_id => $order ) {
			$id           = sanitize_key( $provider_id );
			$value[ $id ] = intval( $order );
		}

		return $value;
	}

	/**
	 * Prepare the response for the GET payment providers request.
	 *
	 * @param array $response The response to prepare.
	 *
	 * @return array The prepared response.
	 */
	private function prepare_payment_providers_response( array $response ): array {
		$response = $this->prepare_payment_providers_response_recursive( $response, $this->get_schema_for_get_payment_providers() );

		$response['providers'] = $this->add_provider_links( $response['providers'] );

		return $response;
	}

	/**
	 * Recursively prepare the response items for the GET payment providers request.
	 *
	 * @param mixed $response_item The response item to prepare.
	 * @param array $schema        The schema to use for preparing the response.
	 *
	 * @return mixed The prepared response item.
	 */
	private function prepare_payment_providers_response_recursive( $response_item, array $schema ) {
		if ( is_null( $response_item ) ||
			! array_key_exists( 'properties', $schema ) ||
			! is_array( $schema['properties'] ) ) {
			return $response_item;
		}

		$prepared_response = array();
		foreach ( $schema['properties'] as $key => $property_schema ) {
			if ( is_array( $response_item ) && array_key_exists( $key, $response_item ) ) {
				if ( is_array( $property_schema ) && array_key_exists( 'properties', $property_schema ) ) {
					$prepared_response[ $key ] = $this->prepare_payment_providers_response_recursive( $response_item[ $key ], $property_schema );
				} elseif ( is_array( $property_schema ) && array_key_exists( 'items', $property_schema ) ) {
					$prepared_response[ $key ] = array_map(
						fn( $item ) => $this->prepare_payment_providers_response_recursive( $item, $property_schema['items'] ),
						$response_item[ $key ]
					);
				} else {
					$prepared_response[ $key ] = $response_item[ $key ];
				}
			}
		}

		// Ensure the order is the same as in the schema.
		$prepared_response = array_merge( array_fill_keys( array_keys( $schema['properties'] ), null ), $prepared_response );

		// Remove any null values from the response.
		$prepared_response = array_filter( $prepared_response, fn( $value ) => ! is_null( $value ) );

		return $prepared_response;
	}

	/**
	 * Add links to providers list items.
	 *
	 * @param array $providers The providers list.
	 *
	 * @return array The providers list with added links.
	 */
	private function add_provider_links( array $providers ): array {
		foreach ( $providers as $key => $provider ) {
			if ( empty( $provider['_links'] ) ) {
				$providers[ $key ]['_links'] = array();
			}

			// If this is a suggestion, add dedicated links.
			if ( ! empty( $provider['_type'] ) &&
				PaymentProviders::TYPE_SUGGESTION === $provider['_type'] &&
				! empty( $provider['_suggestion_id'] )
			) {
				$providers[ $key ]['_links']['attach'] = array(
					'href' => rest_url( sprintf( '/%s/%s/suggestion/%s/attach', $this->route_namespace, $this->rest_base, $provider['_suggestion_id'] ) ),
				);
				$providers[ $key ]['_links']['hide']   = array(
					'href' => rest_url( sprintf( '/%s/%s/suggestion/%s/hide', $this->route_namespace, $this->rest_base, $provider['_suggestion_id'] ) ),
				);
			}

			// If we have an incentive, add a link to dismiss it.
			if ( ! empty( $provider['_incentive'] ) && ! empty( $provider['_suggestion_id'] ) ) {
				if ( empty( $provider['_incentive']['_links'] ) ) {
					$providers[ $key ]['_incentive']['_links'] = array();
				}

				$providers[ $key ]['_incentive']['_links']['dismiss'] = array(
					'href' => rest_url( sprintf( '/%s/%s/suggestion/%s/incentive/%s/dismiss', $this->route_namespace, $this->rest_base, $provider['_suggestion_id'], $provider['_incentive']['id'] ) ),
				);
			}
		}

		return $providers;
	}

	/**
	 * Get the schema for the GET payment providers request.
	 *
	 * @return array[]
	 */
	private function get_schema_for_get_payment_providers(): array {
		$schema               = array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'WooCommerce Settings Payments providers for the given location.',
			'type'    => 'object',
		);
		$schema['properties'] = array(
			'providers'               => array(
				'type'        => 'array',
				'description' => esc_html__( 'The ordered providers list. This includes registered payment gateways, suggestions, and offline payment methods group entry. The individual offline payment methods are separate.', 'woocommerce' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => $this->get_schema_for_payment_provider(),
			),
			'offline_payment_methods' => array(
				'type'        => 'array',
				'description' => esc_html__( 'The ordered offline payment methods providers list.', 'woocommerce' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => $this->get_schema_for_payment_provider(),
			),
			'suggestions'             => array(
				'type'        => 'array',
				'description' => esc_html__( 'The list of suggestions, excluding the ones part of the providers list.', 'woocommerce' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => $this->get_schema_for_suggestion(),
			),
			'suggestion_categories'   => array(
				'type'        => 'array',
				'description' => esc_html__( 'The suggestion categories.', 'woocommerce' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'        => 'object',
					'description' => esc_html__( 'A suggestion category.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'id'          => array(
							'type'        => 'string',
							'description' => esc_html__( 'The unique identifier for the category.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'_priority'   => array(
							'type'        => 'integer',
							'description' => esc_html__( 'The priority of the category.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'title'       => array(
							'type'        => 'string',
							'description' => esc_html__( 'The title of the category.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'description' => array(
							'type'        => 'string',
							'description' => esc_html__( 'The description of the category.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),

					),
				),
			),
		);

		return $schema;
	}

	/**
	 * Get the schema for a payment provider.
	 *
	 * @return array The schema for a payment provider.
	 */
	private function get_schema_for_payment_provider(): array {
		return array(
			'type'        => 'object',
			'description' => esc_html__( 'A payment provider in the context of the main Payments Settings page list.', 'woocommerce' ),
			'properties'  => array(
				'id'             => array(
					'type'        => 'string',
					'description' => esc_html__( 'The unique identifier for the provider.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'_order'         => array(
					'type'        => 'integer',
					'description' => esc_html__( 'The sort order of the provider.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'_type'          => array(
					'type'        => 'string',
					'description' => esc_html__( 'The type of payment provider. Use this to differentiate between the various items in the list and determine their intended use.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'          => array(
					'type'        => 'string',
					'description' => esc_html__( 'The title of the provider.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description'    => array(
					'type'        => 'string',
					'description' => esc_html__( 'The description of the provider.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'supports'       => array(
					'description' => __( 'Supported features for this provider.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type' => 'string',
					),
				),
				'plugin'         => array(
					'type'        => 'object',
					'description' => esc_html__( 'The corresponding plugin details of the provider.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'_type'  => array(
							'type'        => 'string',
							'enum'        => array( PaymentProviders::EXTENSION_TYPE_WPORG ),
							'description' => esc_html__( 'The type of the plugin.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'slug'   => array(
							'type'        => 'string',
							'description' => esc_html__( 'The slug of the plugin.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'file'   => array(
							'type'        => 'string',
							'description' => esc_html__( 'The plugin main file. This is a relative path to the plugins directory.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'status' => array(
							'type'        => 'string',
							'enum'        => array(
								PaymentProviders::EXTENSION_NOT_INSTALLED,
								PaymentProviders::EXTENSION_INSTALLED,
								PaymentProviders::EXTENSION_ACTIVE,
							),
							'description' => esc_html__( 'The status of the plugin.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'image'          => array(
					'type'        => 'string',
					'description' => esc_html__( 'The URL of the provider image.', 'woocommerce' ),
					'readonly'    => true,
				),
				'icon'           => array(
					'type'        => 'string',
					'description' => esc_html__( 'The URL of the provider icon (square aspect ratio - 72px by 72px).', 'woocommerce' ),
					'readonly'    => true,
				),
				'links'          => array(
					'type'        => 'array',
					'description' => __( 'Links for the provider.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'_type' => array(
								'type'        => 'string',
								'description' => esc_html__( 'The type of the link.', 'woocommerce' ),
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'url'   => array(
								'type'        => 'string',
								'description' => esc_html__( 'The URL of the link.', 'woocommerce' ),
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'state'          => array(
					'type'        => 'object',
					'description' => esc_html__( 'The general state of the provider with regards to it\'s payments processing.', 'woocommerce' ),
					'properties'  => array(
						'enabled'           => array(
							'type'        => 'boolean',
							'description' => esc_html__( 'Whether the provider is enabled for use on checkout.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'account_connected' => array(
							'type'        => 'boolean',
							'description' => esc_html__( 'Whether the provider has a payments processing account connected.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'needs_setup'       => array(
							'type'        => 'boolean',
							'description' => esc_html__( 'Whether the provider needs setup.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'test_mode'         => array(
							'type'        => 'boolean',
							'description' => esc_html__( 'Whether the provider is in test mode for payments processing.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'dev_mode'          => array(
							'type'        => 'boolean',
							'description' => esc_html__( 'Whether the provider is in dev mode. Having this true usually leads to forcing test payments. ', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'management'     => array(
					'type'        => 'object',
					'description' => esc_html__( 'The management details of the provider.', 'woocommerce' ),
					'properties'  => array(
						'_links' => array(
							'type'       => 'object',
							'context'    => array( 'view', 'edit' ),
							'readonly'   => true,
							'properties' => array(
								'settings' => array(
									'type'        => 'object',
									'description' => esc_html__( 'The link to the settings page for the payment gateway.', 'woocommerce' ),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
									'properties'  => array(
										'href' => array(
											'type'        => 'string',
											'description' => esc_html__( 'The URL to the settings page for the payment gateway.', 'woocommerce' ),
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
									),
								),
							),
						),
					),
				),
				'onboarding'     => array(
					'type'        => 'object',
					'description' => esc_html__( 'Onboarding-related details for the provider.', 'woocommerce' ),
					'properties'  => array(
						'state'                       => array(
							'type'        => 'object',
							'description' => esc_html__( 'The state of the onboarding process.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'properties'  => array(
								'started'   => array(
									'type'        => 'boolean',
									'description' => esc_html__( 'Whether the onboarding process has been started.', 'woocommerce' ),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'completed' => array(
									'type'        => 'boolean',
									'description' => esc_html__( 'Whether the onboarding process has been completed.', 'woocommerce' ),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'test_mode' => array(
									'type'        => 'boolean',
									'description' => esc_html__( 'Whether the onboarding process happens in test mode (aka sandbox or test-drive).', 'woocommerce' ),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
						'_links'                      => array(
							'type'       => 'object',
							'context'    => array( 'view', 'edit' ),
							'readonly'   => true,
							'properties' => array(
								'onboard' => array(
									'type'        => 'object',
									'description' => esc_html__( 'The start/continue onboarding link for the payment gateway.', 'woocommerce' ),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
									'properties'  => array(
										'href' => array(
											'type'        => 'string',
											'description' => esc_html__( 'The URL to start/continue onboarding for the payment gateway.', 'woocommerce' ),
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
									),
								),
							),
						),
						'recommended_payment_methods' => array(
							'type'        => 'array',
							'description' => esc_html__( 'The list of recommended payment methods details for the payment gateway.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'items'       => array(
								'type'        => 'object',
								'description' => esc_html__( 'The details for a recommended payment method.', 'woocommerce' ),
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'properties'  => array(
									'id'          => array(
										'type'        => 'string',
										'description' => esc_html__( 'The unique identifier for the payment method.', 'woocommerce' ),
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'_order'      => array(
										'type'        => 'integer',
										'description' => esc_html__( 'The sort order of the payment method.', 'woocommerce' ),
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'enabled'     => array(
										'type'        => 'boolean',
										'description' => esc_html__( 'Whether the payment method should be recommended as enabled or not.', 'woocommerce' ),
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'required'    => array(
										'type'        => 'boolean',
										'description' => esc_html__( 'Whether the payment method should be required (and force-enabled) or not.', 'woocommerce' ),
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'title'       => array(
										'type'        => 'string',
										'description' => esc_html__( 'The title of the payment method. Does not include HTML tags.', 'woocommerce' ),
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'description' => array(
										'type'        => 'string',
										'description' => esc_html__( 'The description of the payment method. It can contain basic HTML.', 'woocommerce' ),
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'icon'        => array(
										'type'        => 'string',
										'description' => esc_html__( 'The URL of the payment method icon or a base64-encoded SVG image.', 'woocommerce' ),
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
								),
							),
						),
					),
				),
				'tags'           => array(
					'type'        => 'array',
					'description' => esc_html__( 'The tags associated with the provider.', 'woocommerce' ),
					'uniqueItems' => true,
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'        => 'string',
						'description' => esc_html__( 'Tag associated with the provider.', 'woocommerce' ),
						'readonly'    => true,
					),
				),
				'_suggestion_id' => array(
					'type'        => 'string',
					'description' => esc_html__( 'The suggestion ID matching this provider.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'_incentive'     => $this->get_schema_for_incentive(),
				'_links'         => array(
					'type'       => 'object',
					'context'    => array( 'view', 'edit' ),
					'readonly'   => true,
					'properties' => array(
						'attach' => array(
							'type'        => 'object',
							'description' => esc_html__( 'The link to mark the suggestion as attached. This should be called when an extension is installed.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'href' => array(
									'type'        => 'string',
									'description' => esc_html__( 'The URL to attach the suggestion.', 'woocommerce' ),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
						'hide'   => array(
							'type'        => 'object',
							'description' => esc_html__( 'The link to hide the suggestion.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'href' => array(
									'type'        => 'string',
									'description' => esc_html__( 'The URL to hide the suggestion.', 'woocommerce' ),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get the schema for a suggestion.
	 *
	 * @return array The schema for a suggestion.
	 */
	private function get_schema_for_suggestion(): array {
		return array(
			'type'        => 'object',
			'description' => esc_html__( 'A suggestion with full details.', 'woocommerce' ),
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
			'properties'  => array(
				'id'          => array(
					'type'        => 'string',
					'description' => esc_html__( 'The unique identifier for the suggestion.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'_priority'   => array(
					'type'        => 'integer',
					'description' => esc_html__( 'The priority of the suggestion.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'_type'       => array(
					'type'        => 'string',
					'description' => esc_html__( 'The type of the suggestion.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'       => array(
					'type'        => 'string',
					'description' => esc_html__( 'The title of the suggestion.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description' => array(
					'type'        => 'string',
					'description' => esc_html__( 'The description of the suggestion.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'plugin'      => array(
					'type'       => 'object',
					'context'    => array( 'view', 'edit' ),
					'readonly'   => true,
					'properties' => array(
						'_type'  => array(
							'type'        => 'string',
							'enum'        => array( PaymentProviders::EXTENSION_TYPE_WPORG ),
							'description' => esc_html__( 'The type of the plugin.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'slug'   => array(
							'type'        => 'string',
							'description' => esc_html__( 'The slug of the plugin.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'status' => array(
							'type'        => 'string',
							'enum'        => array(
								PaymentProviders::EXTENSION_NOT_INSTALLED,
								PaymentProviders::EXTENSION_INSTALLED,
								PaymentProviders::EXTENSION_ACTIVE,
							),
							'description' => esc_html__( 'The status of the plugin.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'image'       => array(
					'type'        => 'string',
					'description' => esc_html__( 'The URL of the image.', 'woocommerce' ),
					'readonly'    => true,
				),
				'icon'        => array(
					'type'        => 'string',
					'description' => esc_html__( 'The URL of the icon (square aspect ratio).', 'woocommerce' ),
					'readonly'    => true,
				),
				'links'       => array(
					'type'     => 'array',
					'context'  => array( 'view', 'edit' ),
					'readonly' => true,
					'items'    => array(
						'type'       => 'object',
						'properties' => array(
							'_type' => array(
								'type'        => 'string',
								'description' => esc_html__( 'The type of the link.', 'woocommerce' ),
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'url'   => array(
								'type'        => 'string',
								'description' => esc_html__( 'The URL of the link.', 'woocommerce' ),
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'_incentive'  => $this->get_schema_for_incentive(),
				'tags'        => array(
					'description' => esc_html__( 'The tags associated with the suggestion.', 'woocommerce' ),
					'type'        => 'array',
					'uniqueItems' => true,
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'        => 'string',
						'description' => esc_html__( 'The tags associated with the suggestion.', 'woocommerce' ),
						'readonly'    => true,
					),
				),
				'category'    => array(
					'type'        => 'string',
					'description' => esc_html__( 'The category of the suggestion.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Get the schema for an incentive.
	 *
	 * @return array The incentive schema.
	 */
	private function get_schema_for_incentive(): array {
		return array(
			'type'        => 'object',
			'description' => esc_html__( 'The active incentive for the provider.', 'woocommerce' ),
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
			'properties'  => array(
				'id'                => array(
					'type'        => 'string',
					'description' => esc_html__( 'The incentive unique ID. This ID needs to be used for incentive dismissals.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'promo_id'          => array(
					'type'        => 'string',
					'description' => esc_html__( 'The incentive promo ID. This ID need to be fed into the onboarding flow.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'             => array(
					'type'        => 'string',
					'description' => esc_html__( 'The incentive title. It can contain stylistic HTML.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description'       => array(
					'type'        => 'string',
					'description' => esc_html__( 'The incentive description. It can contain stylistic HTML.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'short_description' => array(
					'type'        => 'string',
					'description' => esc_html__( 'The short description of the incentive. It can contain stylistic HTML.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'cta_label'         => array(
					'type'        => 'string',
					'description' => esc_html__( 'The call to action label for the incentive.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'tc_url'            => array(
					'type'        => 'string',
					'description' => esc_html__( 'The URL to the terms and conditions for the incentive.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'badge'             => array(
					'type'        => 'string',
					'description' => esc_html__( 'The badge label for the incentive.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'_dismissals'       => array(
					'type'        => 'array',
					'description' => esc_html__( 'The dismissals list for the incentive. Each dismissal entry includes a context and a timestamp. The `all` entry means the incentive was dismissed for all contexts.', 'woocommerce' ),
					'uniqueItems' => true,
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'context'   => array(
								'type'        => 'string',
								'description' => esc_html__( 'Context ID in which the incentive was dismissed.', 'woocommerce' ),
								'readonly'    => true,
							),
							'timestamp' => array(
								'type'        => 'integer',
								'description' => esc_html__( 'Unix timestamp representing when the incentive was dismissed.', 'woocommerce' ),
								'readonly'    => true,
							),
						),
					),
				),
				'_links'            => array(
					'type'       => 'object',
					'context'    => array( 'view', 'edit' ),
					'readonly'   => true,
					'properties' => array(
						'dismiss' => array(
							'type'        => 'object',
							'description' => esc_html__( 'The link to dismiss the incentive.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'href' => array(
									'type'        => 'string',
									'description' => esc_html__( 'The URL to dismiss the incentive.', 'woocommerce' ),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
						'onboard' => array(
							'type'        => 'object',
							'description' => esc_html__( 'The start/continue onboarding link for the payment gateway.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'href' => array(
									'type'        => 'string',
									'description' => esc_html__( 'The URL to start/continue onboarding for the payment gateway.', 'woocommerce' ),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get WooPay eligibility status.
	 *
	 * @return array The WooPay eligibility status.
	 */
	protected function get_woopay_eligibility() {
		return rest_ensure_response(
			array(
				'is_eligible' => WCPayPromotion::is_woopay_eligible(),
			)
		);
	}
}
