<?php
/**
 * REST API Options Controller
 *
 * Handles requests to get and update options in the wp_options table.
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Options Controller.
 *
 * @extends WC_REST_Data_Controller
 */
class Options extends \WC_REST_Data_Controller {
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
	protected $rest_base = 'options';

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
					'callback'            => array( $this, 'get_options' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_options' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to get options.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$params = explode( ',', $request['options'] );

		if ( ! isset( $request['options'] ) || ! is_array( $params ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'You must supply an array of options.', 'woocommerce' ), 500 );
		}

		foreach ( $params as $option ) {
			if ( ! $this->user_has_permission( $option, $request ) ) {
				return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view these options.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		return true;
	}

	/**
	 * Check if the user has permission given an option name.
	 *
	 * @param  string          $option Option name.
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function user_has_permission( $option, $request ) {
		$permissions = $this->get_option_permissions( $request );

		if ( isset( $permissions[ $option ] ) ) {
			return $permissions[ $option ];
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if a given request has access to update options.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$params = $request->get_json_params();

		if ( ! is_array( $params ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_update', __( 'You must supply an array of options and values.', 'woocommerce' ), 500 );
		}

		foreach ( $params as $option_name => $option_value ) {
			if ( ! $this->user_has_permission( $option_name, $request ) ) {
				return new \WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot manage these options.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		return true;
	}

	/**
	 * Get an array of options and respective permissions for the current user.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	public function get_option_permissions( $request ) {
		$permissions = array(
			'theme_mods_' . get_stylesheet()               => current_user_can( 'edit_theme_options' ),
			'woocommerce_setup_jetpack_opted_in'           => current_user_can( 'manage_woocommerce' ),
			'woocommerce_stripe_settings'                  => current_user_can( 'manage_woocommerce' ),
			'woocommerce-ppcp-settings'                    => current_user_can( 'manage_woocommerce' ),
			'woocommerce_ppcp-gateway_setting'             => current_user_can( 'manage_woocommerce' ),
			'woocommerce_demo_store'                       => current_user_can( 'manage_woocommerce' ),
			'woocommerce_demo_store_notice'                => current_user_can( 'manage_woocommerce' ),
			'woocommerce_ces_tracks_queue'                 => current_user_can( 'manage_woocommerce' ),
			'woocommerce_navigation_intro_modal_dismissed' => current_user_can( 'manage_woocommerce' ),
		);

		return apply_filters( 'woocommerce_rest_api_option_permissions', $permissions, $request );
	}

	/**
	 * Gets an array of options and respective values.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array Options object with option values.
	 */
	public function get_options( $request ) {
		$params  = explode( ',', $request['options'] );
		$options = array();

		if ( ! is_array( $params ) ) {
			return array();
		}

		foreach ( $params as $option ) {
			$options[ $option ] = get_option( $option );
		}

		return $options;
	}

	/**
	 * Updates an array of objects.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array Options object with a boolean if the option was updated.
	 */
	public function update_options( $request ) {
		$params  = $request->get_json_params();
		$updated = array();

		if ( ! is_array( $params ) ) {
			return array();
		}

		foreach ( $params as $key => $value ) {
			$updated[ $key ] = update_option( $key, $value );
		}

		return $updated;
	}

	/**
	 * Get the schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'options',
			'type'       => 'object',
			'properties' => array(
				'options' => array(
					'type'        => 'array',
					'description' => __( 'Array of options with associated values.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
