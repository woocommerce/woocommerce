<?php

namespace Automattic\WooCommerce\Internal\Admin;

/**
 * WCAdminUser Class.
 */
class WCAdminUser {

	/**
	 * Class instance.
	 *
	 * @var WCAdminUser instance
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_user_data' ) );
		add_filter( 'rest_prepare_user', array( $this, 'handle_rest_prepare_user' ), 10, 3 );
	}

	/**
	 * Get class instance.
	 *
	 * @return WCAdminUser Instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers WooCommerce specific user data to the WordPress user API.
	 */
	public function register_user_data() {
		register_rest_field(
			'user',
			'is_super_admin',
			array(
				'get_callback' => function ( $user, $attr, $request ) {
					return $this->current_user_can_read_user_data( $user['id'] ?? 0, $request ) && is_super_admin( $user['id'] );
				},
				'schema'       => array(
					'type'     => 'boolean',
					'context'  => array( 'view', 'edit' ),
					'readonly' => true,
				),
			)
		);
		register_rest_field(
			'user',
			'woocommerce_meta',
			array(
				'get_callback'    => array( $this, 'get_user_data_values' ),
				'update_callback' => array( $this, 'update_user_data_values' ),
				'schema'          => array(
					'type'    => 'object',
					'context' => array( 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * Handle the rest_prepare_user filter.
	 *
	 * Removes the WooCommerce user fields from responses the current user isn't allowed to read them in,
	 * so that they show up as missing instead of as false or empty values.
	 *
	 * @internal
	 *
	 * @param mixed $response The response for the user.
	 * @param mixed $user     The user the response is for.
	 * @param mixed $request  The current request.
	 * @return mixed The response, without the WooCommerce user fields if the current user can't read them.
	 */
	public function handle_rest_prepare_user( $response, $user, $request ) {
		if ( ! $response instanceof \WP_REST_Response || ! $user instanceof \WP_User ) {
			return $response;
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) || $this->current_user_can_read_user_data( $user->ID, $request ) ) {
			return $response;
		}

		unset( $data['is_super_admin'], $data['woocommerce_meta'] );
		$response->set_data( $data );
		return $response;
	}

	/**
	 * For all the registered user data fields (  Loader::get_user_data_fields ), fetch the data
	 * for returning via the REST API.
	 *
	 * Returns an empty array if the current user isn't allowed to read the data of the requested user.
	 *
	 * @param array                  $user The prepared user data from the users endpoint response.
	 * @param mixed                  $attr The name of the requested field.
	 * @param \WP_REST_Request|array $request The current request.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>>|array $request
	 */
	public function get_user_data_values( $user, $attr = null, $request = array() ) {
		if ( ! $this->current_user_can_read_user_data( $user['id'] ?? 0, $request ) ) {
			return array();
		}

		$values = array();
		foreach ( $this->get_user_data_fields() as $field ) {
			$values[ $field ] = self::get_user_data_field( $user['id'], $field );
		}
		return $values;
	}

	/**
	 * Checks whether the current user is allowed to read the extra data registered by
	 * WooCommerce for a given user.
	 *
	 * Users can always read their own data. Reading another user's data requires the 'edit' context
	 * and the same capabilities WordPress requires for that context in the users endpoint.
	 *
	 * @param int   $user_id The id of the user whose data is being read.
	 * @param mixed $request The current request.
	 * @return bool True if the current user can read the extra data for the given user.
	 */
	private function current_user_can_read_user_data( $user_id, $request ): bool {
		$user_id = (int) $user_id;
		if ( 0 === $user_id ) {
			return false;
		}

		if ( get_current_user_id() === $user_id ) {
			return true;
		}

		$context = ( is_array( $request ) || $request instanceof \ArrayAccess ) ? ( $request['context'] ?? null ) : null;
		return 'edit' === $context && ( current_user_can( 'edit_user', $user_id ) || current_user_can( 'list_users' ) );
	}

	/**
	 * For all the registered user data fields ( Loader::get_user_data_fields ), update the data
	 * for the REST API.
	 *
	 * @param array   $values   The new values for the meta.
	 * @param WP_User $user     The current user.
	 * @param string  $field_id The field id for the user meta.
	 */
	public function update_user_data_values( $values, $user, $field_id ) {
		if ( empty( $values ) || ! is_array( $values ) || 'woocommerce_meta' !== $field_id ) {
			return;
		}
		$fields  = $this->get_user_data_fields();
		$updates = array();
		foreach ( $values as $field => $value ) {
			if ( in_array( $field, $fields, true ) ) {
				$updates[ $field ] = $value;
				self::update_user_data_field( $user->ID, $field, $value );
			}
		}
		return $updates;
	}

	/**
	 * We store some WooCommerce specific user meta attached to users endpoint,
	 * so that we can track certain preferences or values such as the inbox activity panel last open time.
	 * Additional fields can be added in the function below, and then used via wc-admin's currentUser data.
	 *
	 * @return array Fields to expose over the WP user endpoint.
	 */
	public function get_user_data_fields() {
		/**
		 * Filter user data fields exposed over the WordPress user endpoint.
		 *
		 * @since 4.0.0
		 * @param array $fields Array of fields to expose over the WP user endpoint.
		 */
		return apply_filters( 'woocommerce_admin_get_user_data_fields', array( 'variable_product_tour_shown' ) );
	}

	/**
	 * Helper to update user data fields.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $field Field name.
	 * @param mixed  $value  Field value.
	 */
	public static function update_user_data_field( $user_id, $field, $value ) {
		update_user_meta( $user_id, 'woocommerce_admin_' . $field, $value );
	}

	/**
	 * Helper to retrieve user data fields.
	 *
	 * Migrates old key prefixes as well.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $field Field name.
	 * @return mixed The user field value.
	 */
	public static function get_user_data_field( $user_id, $field ) {
		$meta_value = get_user_meta( $user_id, 'woocommerce_admin_' . $field, true );

		// Migrate old meta values (prefix changed from `wc_admin_` to `woocommerce_admin_`).
		if ( '' === $meta_value ) {
			$old_meta_value = get_user_meta( $user_id, 'wc_admin_' . $field, true );

			if ( '' !== $old_meta_value ) {
				self::update_user_data_field( $user_id, $field, $old_meta_value );
				delete_user_meta( $user_id, 'wc_admin_' . $field );

				$meta_value = $old_meta_value;
			}
		}

		return $meta_value;
	}

	/**
	 * Get the current user data.
	 *
	 * @return array User data.
	 */
	public static function get_user_data() {
		// The controller below reads $wp_rest_additional_fields, which is only filled once
		// rest_api_init has fired. On a wp-admin request it has not, so register our fields
		// explicitly instead of relying on something else having booted the REST server first.
		// register_rest_field() only writes to that global, so this does not build the server.
		self::get_instance()->register_user_data();

		$user_controller = new \WP_REST_Users_Controller();
		$request         = new \WP_REST_Request();
		$request->set_query_params( array( 'context' => 'edit' ) );
		$user_response     = $user_controller->get_current_item( $request );
		$current_user_data = is_wp_error( $user_response ) ? (object) array() : $user_response->get_data();
		$current_user_data = self::filter_user_capabilities( $current_user_data );

		return $current_user_data;
	}

	/**
	 * Filter user capabilities to respect file modification restrictions.
	 *
	 * @param array $user_data User data.
	 * @return array Filtered user data.
	 */
	private static function filter_user_capabilities( $user_data ) {
		if ( ! is_array( $user_data ) || ! isset( $user_data['capabilities'] ) ) {
			return $user_data;
		}

		// If the user has install_plugins capability, check if file modifications are allowed.
		if ( isset( $user_data['capabilities']->install_plugins ) && $user_data['capabilities']->install_plugins ) {
			$user_data['capabilities']->install_plugins = wp_is_file_mod_allowed( 'woocommerce' );
		}

		return $user_data;
	}
}
