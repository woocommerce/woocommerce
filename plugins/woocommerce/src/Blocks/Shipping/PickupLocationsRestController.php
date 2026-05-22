<?php
namespace Automattic\WooCommerce\Blocks\Shipping;

/**
 * REST controller for Local Pickup location settings.
 *
 * Exposes /wc/v3/pickup-locations so users with the manage_woocommerce
 * capability (e.g. Shop Managers) can save Local Pickup settings without
 * requiring the manage_options capability needed by /wp/v2/settings.
 *
 * @since 10.9.0
 */
class PickupLocationsRestController extends \WP_REST_Controller {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * REST API resource base.
	 *
	 * @var string
	 */
	protected $rest_base = 'pickup-locations';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
					'args'                => $this->get_endpoint_args(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether the current user can update pickup location settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function update_settings_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new \WP_Error(
				'woocommerce_rest_cannot_edit',
				__( 'Sorry, you cannot edit this resource.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Save pickup location settings and return the saved values.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_settings( $request ) {
		$settings  = $request->get_param( 'pickup_location_settings' );
		$locations = $request->get_param( 'pickup_locations' );

		if ( null !== $settings ) {
			update_option( 'woocommerce_pickup_location_settings', $settings );
		}

		if ( null !== $locations ) {
			update_option( 'pickup_location_pickup_locations', $locations );
		}

		return rest_ensure_response(
			array(
				'pickup_location_settings' => $settings,
				'pickup_locations'         => $locations,
			)
		);
	}

	/**
	 * Get the schema for the request args.
	 *
	 * @return array
	 */
	private function get_endpoint_args() {
		return array(
			'pickup_location_settings' => array(
				'description' => __( 'Local pickup method settings.', 'woocommerce' ),
				'type'        => 'object',
				'required'    => false,
				'properties'  => array(
					'enabled'    => array(
						'description' => __( 'Whether local pickup is enabled.', 'woocommerce' ),
						'type'        => 'string',
						'enum'        => array( 'yes', 'no' ),
					),
					'title'      => array(
						'description' => __( 'Title shown to customers during checkout.', 'woocommerce' ),
						'type'        => 'string',
					),
					'tax_status' => array(
						'description' => __( 'Tax status applied to the pickup cost.', 'woocommerce' ),
						'type'        => 'string',
						'enum'        => array( 'taxable', 'none' ),
					),
					'cost'       => array(
						'description' => __( 'Optional cost charged for local pickup.', 'woocommerce' ),
						'type'        => 'string',
					),
				),
			),
			'pickup_locations'         => array(
				'description' => __( 'List of local pickup locations.', 'woocommerce' ),
				'type'        => 'array',
				'required'    => false,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'    => array(
							'type' => 'string',
						),
						'address' => array(
							'type'       => 'object',
							'properties' => array(
								'address_1' => array( 'type' => 'string' ),
								'city'      => array( 'type' => 'string' ),
								'state'     => array( 'type' => 'string' ),
								'postcode'  => array( 'type' => 'string' ),
								'country'   => array( 'type' => 'string' ),
							),
						),
						'details' => array(
							'type' => 'string',
						),
						'enabled' => array(
							'type' => 'boolean',
						),
					),
				),
			),
		);
	}

	/**
	 * Get the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'pickup-locations',
			'type'       => 'object',
			'properties' => $this->get_endpoint_args(),
		);
	}
}
