<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\Shipping;

/**
 * REST controller for Local Pickup location settings.
 *
 * Exposes /wc/v3/pickup-locations so users with the manage_woocommerce
 * capability (e.g. Shop Managers) can save Local Pickup settings without
 * requiring the manage_options capability needed by /wp/v2/settings.
 *
 * @since 11.0.0
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
	 *
	 * @return void
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
					'args'                => array(
						'pickup_location_settings' => array(
							'description' => __( 'Local pickup method settings.', 'woocommerce' ),
							'type'        => 'object',
						),
						'pickup_locations'         => array(
							'description' => __( 'List of local pickup locations.', 'woocommerce' ),
							'type'        => 'array',
						),
					),
				),
			)
		);
	}

	/**
	 * Check whether the current user can update pickup location settings.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
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
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_settings( $request ) {
		$settings  = $request->get_param( 'pickup_location_settings' );
		$locations = $request->get_param( 'pickup_locations' );

		if ( is_array( $settings ) ) {
			$settings = $this->sanitize_pickup_location_settings( $settings );
			update_option( 'woocommerce_pickup_location_settings', $settings );
		}

		if ( is_array( $locations ) ) {
			$locations = $this->sanitize_pickup_locations( $locations );
			update_option( 'pickup_location_pickup_locations', $locations );
		}

		// The settings UI always saves both arrays together; a Tracks snapshot is
		// only meaningful with both present, so skip partial (non-UI) updates.
		if ( is_array( $settings ) && is_array( $locations ) ) {
			$this->record_save_event( $settings, $locations );
		}

		return rest_ensure_response(
			array(
				'pickup_location_settings' => $settings,
				'pickup_locations'         => $locations,
			)
		);
	}

	/**
	 * Record a Tracks event summarising a Local Pickup settings save.
	 *
	 * @param array $settings  Sanitized method settings.
	 * @param array $locations Sanitized list of pickup locations.
	 * @return void
	 */
	private function record_save_event( array $settings, array $locations ): void {
		$cost = $settings['cost'] ?? '';

		\WC_Tracks::record_event(
			'local_pickup_save_changes',
			array(
				'local_pickup_enabled'     => 'yes' === ( $settings['enabled'] ?? '' ),
				'title'                    => __( 'Pickup', 'woocommerce' ) === ( $settings['title'] ?? '' ),
				'price'                    => '' === $cost,
				'cost'                     => '' === $cost ? 0 : $cost,
				'taxes'                    => $settings['tax_status'] ?? '',
				'total_pickup_locations'   => count( $locations ),
				'pickup_locations_enabled' => count(
					array_filter(
						$locations,
						function ( $location ) {
							return ! empty( $location['enabled'] );
						}
					)
				),
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

			$name = isset( $location['name'] ) ? sanitize_text_field( (string) $location['name'] ) : '';

			// A pickup location with no name is unusable, and incomplete entries
			// would later trigger undefined-index notices in
			// ShippingController::hydrate_client_settings(), which reads these
			// fields unconditionally. Skip nameless entries and always emit every
			// key with a safe default for the ones we keep.
			if ( '' === $name ) {
				continue;
			}

			// Always emit every address key with a safe default. Downstream
			// readers such as ShippingController::filter_taxable_address() access
			// state/postcode/city unconditionally once country is set, so a
			// partial address (e.g. only country) would trigger undefined-index
			// notices.
			$address = array();
			if ( isset( $location['address'] ) && is_array( $location['address'] ) ) {
				foreach ( array( 'address_1', 'city', 'state', 'postcode', 'country' ) as $field ) {
					$address[ $field ] = isset( $location['address'][ $field ] )
						? sanitize_text_field( (string) $location['address'][ $field ] )
						: '';
				}
			}

			$enabled = isset( $location['enabled'] ) ? rest_sanitize_boolean( (string) $location['enabled'] ) : false;

			$sanitized[] = array(
				'name'    => $name,
				'address' => $address,
				// Details may contain limited HTML. Match the rendering side
				// in ShippingController::show_local_pickup_details() which uses
				// wp_kses_post().
				'details' => isset( $location['details'] ) ? wp_kses_post( (string) $location['details'] ) : '',
				'enabled' => $enabled,
			);
		}

		return $sanitized;
	}
}
