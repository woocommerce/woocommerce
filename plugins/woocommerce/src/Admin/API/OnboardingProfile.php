<?php
/**
 * REST API Onboarding Profile Controller
 *
 * Handles requests to /onboarding/profile
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile as Profile;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProducts;
use Automattic\Jetpack\Connection\Manager as Jetpack_Connection_Manager;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Onboarding Profile controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class OnboardingProfile extends \WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'onboarding/profile';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// This endpoint is experimental. For internal use only.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/experimental_get_email_prefill',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_email_prefill' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/progress',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_profile_progress' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/progress/core-profiler/complete',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'core_profiler_step_complete' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
					'args'                => array(
						'step' => array(
							'required'    => true,
							'type'        => 'string',
							'description' => __( 'The Core Profiler step to mark as complete.', 'woocommerce' ),
							'enum'        => array(
								'intro-opt-in',
								'skip-guided-setup',
								'user-profile',
								'business-info',
								'plugins',
								'intro-builder',
								'skip-guided-setup',
							),
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update-store-currency-and-measurement-units',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_store_currency_and_measurement_units' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
					'args'                => array(
						'country_code' => array(
							'description' => __( 'Country code.', 'woocommerce' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to read onboarding profile data.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check whether a given request has permission to edit onboarding profile data.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot edit this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Return all onboarding profile data.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-options.php';

		$onboarding_data             = get_option( Profile::DATA_OPTION, array() );
		$onboarding_data['industry'] = isset( $onboarding_data['industry'] ) ? $this->filter_industries( $onboarding_data['industry'] ) : null;
		$item_schema                 = $this->get_item_schema();
		$items                       = array();
		foreach ( $item_schema['properties'] as $key => $property_schema ) {
			$items[ $key ] = isset( $onboarding_data[ $key ] ) ? $onboarding_data[ $key ] : null;
		}

		$wccom_auth               = \WC_Helper_Options::get( 'auth' );
		$items['wccom_connected'] = empty( $wccom_auth['access_token'] ) ? false : true;

		$item = $this->prepare_item_for_response( $items, $request );
		$data = $this->prepare_response_for_collection( $item );

		return rest_ensure_response( $data );
	}

	/**
	 * Filter the industries.
	 *
	 * @param  array $industries List of industries.
	 * @return array
	 */
	protected function filter_industries( $industries ) {
		/**
		 * Filter the list of industries.
		 *
		 * @since 6.5.0
		 * @param array $industries List of industries.
		 */
		return apply_filters(
			'woocommerce_admin_onboarding_industries',
			$industries
		);
	}

	/**
	 * Update onboarding profile data.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_items( $request ) {
		$params     = $request->get_json_params();
		$query_args = $this->prepare_objects_query( $params );

		$onboarding_data = (array) get_option( Profile::DATA_OPTION, array() );
		$profile_data    = array_merge( $onboarding_data, $query_args );
		update_option( Profile::DATA_OPTION, $profile_data );

		/**
		 * Fires when onboarding profile data is updated via the REST API.
		 *
		 * @since 6.5.0
		 * @param array $onboarding_data Previous onboarding data.
		 * @param array $query_args New data being set.
		 */
		do_action( 'woocommerce_onboarding_profile_data_updated', $onboarding_data, $query_args );

		$result = array(
			'status'  => 'success',
			'message' => __( 'Onboarding profile data has been updated.', 'woocommerce' ),
		);

		$response = $this->prepare_item_for_response( $result, $request );
		$data     = $this->prepare_response_for_collection( $response );

		return rest_ensure_response( $data );
	}

	/**
	 * Returns a default email to be pre-filled in OBW. Prioritizes Jetpack if connected,
	 * otherwise will default to WordPress general settings.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_email_prefill( $request ) {
		$result = array(
			'email' => '',
		);

		// Attempt to get email from Jetpack.
		if ( class_exists( Jetpack_Connection_Manager::class ) ) {
			$jetpack_connection_manager = new Jetpack_Connection_Manager();
			if ( $jetpack_connection_manager->is_active() ) {
				$jetpack_user = $jetpack_connection_manager->get_connected_user_data();

				$result['email'] = $jetpack_user['email'];
			}
		}

		// Attempt to get email from WordPress general settings.
		if ( empty( $result['email'] ) ) {
			$result['email'] = get_option( 'admin_email' );
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Mark a core profiler step as complete.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function core_profiler_step_complete( $request ) {
		$json = $request->get_json_params();
		$step = $json['step'];

		$onboarding_progress = (array) get_option( Profile::PROGRESS_OPTION, array() );

		if ( ! isset( $onboarding_progress['core_profiler_completed_steps'] ) ) {
			$onboarding_progress['core_profiler_completed_steps'] = array();
		}

		$onboarding_progress['core_profiler_completed_steps'][ $step ] = array(
			'completed_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);

		update_option( Profile::PROGRESS_OPTION, $onboarding_progress );

		/**
		 * Fires when a core profiler step is completed.
		 *
		 * @since 6.5.0
		 * @param string $step The completed step name.
		 */
		do_action( 'woocommerce_core_profiler_step_complete', $step );

		$response_data = array(
			'results' => $onboarding_progress,
			'status'  => 'success',
		);

		$response = rest_ensure_response( $response_data );

		return $response;
	}

	/**
	 * Get the onboarding profile progress.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_profile_progress( $request ) {
		$onboarding_progress = (array) get_option( Profile::PROGRESS_OPTION, array() );
		return rest_ensure_response( $onboarding_progress );
	}


	/**
	 * Update store's currency and measurement units.
	 * Requires 'country' code to be passed in the request.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_store_currency_and_measurement_units( WP_REST_Request $request ) {
		$country_code = $request->get_param( 'country_code' );
		$locale_info  = include WC()->plugin_path() . '/i18n/locale-info.php';

		if ( empty( $country_code ) || ! isset( $locale_info[ $country_code ] ) ) {
			return new WP_Error(
				'woocommerce_rest_invalid_country_code',
				__( 'Invalid country code.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$country_info = $locale_info[ $country_code ];

		$currency_settings = array(
			'woocommerce_currency'           => $country_info['currency_code'],
			'woocommerce_currency_pos'       => $country_info['currency_pos'],
			'woocommerce_price_thousand_sep' => $country_info['thousand_sep'],
			'woocommerce_price_decimal_sep'  => $country_info['decimal_sep'],
			'woocommerce_price_num_decimals' => $country_info['num_decimals'],
			'woocommerce_weight_unit'        => $country_info['weight_unit'],
			'woocommerce_dimension_unit'     => $country_info['dimension_unit'],
		);

		foreach ( $currency_settings as $key => $value ) {
			update_option( $key, $value );
		}

		return new WP_REST_Response( array(), 204 );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param  array $params The params sent in the request.
	 * @return array
	 */
	protected function prepare_objects_query( $params ) {
		$args       = array();
		$properties = self::get_profile_properties();

		foreach ( $properties as $key => $property ) {
			if ( isset( $params[ $key ] ) ) {
				$args[ $key ] = $params[ $key ];
			}
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @since 6.5.0
		 * @param array $args Key value array of query var to query value.
		 * @param array $params The params sent in the request.
		 */
		$args = apply_filters( 'woocommerce_rest_onboarding_profile_object_query', $args, $params );

		return $args;
	}


	/**
	 * Prepare the data object for response.
	 *
	 * @param object          $item Data object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data     = $this->add_additional_fields_to_object( $item, $request );
		$data     = $this->filter_response_by_context( $data, 'view' );
		$response = rest_ensure_response( $data );

		/**
		 * Filter the list returned from the API.
		 *
		 * @since 6.5.0
		 * @param WP_REST_Response $response The response object.
		 * @param array            $item     The original item.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_onboarding_prepare_profile', $response, $item, $request );
	}

	/**
	 * Get onboarding profile properties.
	 *
	 * @return array
	 */
	public static function get_profile_properties() {
		$properties = array(
			'completed'               => array(
				'type'              => 'boolean',
				'description'       => __( 'Whether or not the profile was completed.', 'woocommerce' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'skipped'                 => array(
				'type'              => 'boolean',
				'description'       => __( 'Whether or not the profile was skipped.', 'woocommerce' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'industry'                => array(
				'type'              => 'array',
				'description'       => __( 'Industry.', 'woocommerce' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'nullable'          => true,
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => array(
					'type' => 'string',
				),
			),
			'business_extensions'     => array(
				'type'              => 'array',
				'description'       => __( 'Extra business extensions to install.', 'woocommerce' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'sanitize_callback' => 'wp_parse_slug_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => array(
					'type' => 'string',
				),
			),
			'is_agree_marketing'      => array(
				'type'              => 'boolean',
				'description'       => __( 'Whether or not this store agreed to receiving marketing contents from WooCommerce.com.', 'woocommerce' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'store_email'             => array(
				'type'              => 'string',
				'description'       => __( 'Store email address.', 'woocommerce' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'nullable'          => true,
				'validate_callback' => array( __CLASS__, 'rest_validate_marketing_email' ),
			),
			'is_store_country_set'    => array(
				'type'              => 'boolean',
				'description'       => __( 'Whether or not this store country is set via onboarding profiler.', 'woocommerce' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'is_plugins_page_skipped' => array(
				'type'              => 'boolean',
				'description'       => __( 'Whether or not plugins step in core profiler was skipped.', 'woocommerce' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'business_choice'         => array(
				'type'        => 'string',
				'description' => __( 'Business choice.', 'woocommerce' ),
				'context'     => array( 'view' ),
				'readonly'    => true,
				'nullable'    => true,
			),
			'selling_online_answer'   => array(
				'type'        => 'string',
				'description' => __( 'Selling online answer.', 'woocommerce' ),
				'context'     => array( 'view' ),
				'readonly'    => true,
				'nullable'    => true,
			),
			'selling_platforms'       => array(
				'type'        => array( 'array', 'null' ),
				'description' => __( 'Selling platforms.', 'woocommerce' ),
				'context'     => array( 'view' ),
				'readonly'    => true,
				'nullable'    => true,
				'items'       => array(
					'type' => array( 'string', 'null' ),
				),
			),
		);

		/**
		 * Filters the Onboarding Profile REST API JSON Schema.
		 *
		 * @since 6.5.0
		 * @param array $properties List of properties.
		 */
		return apply_filters( 'woocommerce_rest_onboarding_profile_properties', $properties );
	}

	/**
	 * Optionally validates email if user agreed to marketing or if email is not empty.
	 *
	 * @param mixed           $value Email value.
	 * @param WP_REST_Request $request Request object.
	 * @param string          $param Parameter name.
	 * @return true|WP_Error
	 */
	public static function rest_validate_marketing_email( $value, $request, $param ) {
		$is_agree_marketing = $request->get_param( 'is_agree_marketing' );
		if (
			( $is_agree_marketing || ! empty( $value ) ) &&
			! is_email( $value ) ) {
			return new \WP_Error( 'rest_invalid_email', __( 'Invalid email address', 'woocommerce' ) );
		}
		return true;
	}

	/**
	 * Get the schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		// Unset properties used for collection params.
		$properties = self::get_profile_properties();
		foreach ( $properties as $key => $property ) {
			unset( $properties[ $key ]['default'] );
			unset( $properties[ $key ]['items'] );
			unset( $properties[ $key ]['validate_callback'] );
			unset( $properties[ $key ]['sanitize_callback'] );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'onboarding_profile',
			'type'       => 'object',
			'properties' => $properties,
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		// Unset properties used for item schema.
		$params = self::get_profile_properties();
		foreach ( $params as $key => $param ) {
			unset( $params[ $key ]['context'] );
			unset( $params[ $key ]['readonly'] );
		}

		$params['context'] = $this->get_context_param( array( 'default' => 'view' ) );

		/**
		 * Filters the Onboarding Profile REST API collection parameters.
		 *
		 * @since 6.5.0
		 * @param array $params Collection parameters.
		 */
		return apply_filters( 'woocommerce_rest_onboarding_profile_collection_params', $params );
	}
}
