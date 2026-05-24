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

		if ( null !== $settings && is_array( $settings ) ) {
			$settings = $this->sanitize_pickup_location_settings( $settings );
			update_option( 'woocommerce_pickup_location_settings', $settings );
		}

		if ( null !== $locations && is_array( $locations ) ) {
			$locations = $this->sanitize_pickup_locations( $locations );
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
	 * Sanitize the pickup_location_settings payload before persisting.
	 *
	 * The WP REST dispatcher only auto-sanitizes top-level args, so nested
	 * object properties need to be cleaned here as defense in depth against
	 * stored HTML/JS in admin surfaces.
	 *
	 * @param array $settings Raw settings payload.
	 * @return array Sanitized settings payload.
	 */
	private function sanitize_pickup_location_settings( array $settings ): array {
		$sanitized = array();

		if ( isset( $settings['enabled'] ) ) {
			// The schema enum will already reject anything other than 'yes'/'no',
			// but normalize defensively in case the dispatcher is bypassed.
			$sanitized['enabled'] = in_array( $settings['enabled'], array( 'yes', 'no' ), true )
				? $settings['enabled']
				: 'no';
		}

		if ( isset( $settings['title'] ) ) {
			$sanitized['title'] = sanitize_text_field( (string) $settings['title'] );
		}

		if ( isset( $settings['tax_status'] ) ) {
			$sanitized['tax_status'] = in_array( $settings['tax_status'], array( 'taxable', 'none' ), true )
				? $settings['tax_status']
				: 'none';
		}

		if ( isset( $settings['cost'] ) ) {
			// Cost may be a math expression (e.g. "5 + 1.50"), so strip HTML
			// without coercing to float — floatval would break formula syntax.
			$sanitized['cost'] = wp_strip_all_tags( (string) $settings['cost'] );
		}

		return $sanitized;
	}

	/**
	 * Sanitize the pickup_locations payload before persisting.
	 *
	 * @param array $locations Raw list of pickup locations.
	 * @return array Sanitized list of pickup locations.
	 */
	private function sanitize_pickup_locations( array $locations ): array {
		$sanitized = array();

		foreach ( $locations as $location ) {
			if ( ! is_array( $location ) ) {
				continue;
			}

			$entry = array();

			if ( isset( $location['name'] ) ) {
				$entry['name'] = sanitize_text_field( (string) $location['name'] );
			}

			if ( isset( $location['address'] ) && is_array( $location['address'] ) ) {
				$address     = $location['address'];
				$address_out = array();
				foreach ( array( 'address_1', 'city', 'state', 'postcode', 'country' ) as $field ) {
					if ( isset( $address[ $field ] ) ) {
						$address_out[ $field ] = sanitize_text_field( (string) $address[ $field ] );
					}
				}
				$entry['address'] = $address_out;
			}

			if ( isset( $location['details'] ) ) {
				// Details may contain limited HTML — match the rendering side
				// in ShippingController::show_local_pickup_details() which uses
				// wp_kses_post().
				$entry['details'] = wp_kses_post( (string) $location['details'] );
			}

			if ( isset( $location['enabled'] ) ) {
				$entry['enabled'] = rest_sanitize_boolean( $location['enabled'] );
			}

			$sanitized[] = $entry;
		}

		return $sanitized;
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
