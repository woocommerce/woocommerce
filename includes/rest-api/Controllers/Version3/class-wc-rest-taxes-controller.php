<?php
/**
 * REST API Taxes controller
 *
 * Handles requests to the /taxes endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Taxes controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Taxes_V2_Controller
 */
class WC_REST_Taxes_Controller extends WC_REST_Taxes_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Add tax rate locales to the response array.
	 *
	 * @param array    $data Response data.
	 * @param stdClass $tax  Tax object.
	 *
	 * @return array
	 */
	protected function add_tax_rate_locales( $data, $tax ) {
		global $wpdb;

		$data              = parent::add_tax_rate_locales( $data, $tax );
		$data['postcodes'] = array();
		$data['cities']    = array();

		// Get locales from a tax rate.
		$locales = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT location_code, location_type
				FROM {$wpdb->prefix}woocommerce_tax_rate_locations
				WHERE tax_rate_id = %d
				",
				$tax->tax_rate_id
			)
		);

		if ( ! is_wp_error( $tax ) && ! is_null( $tax ) ) {
			foreach ( $locales as $locale ) {
				if ( 'postcode' === $locale->location_type ) {
					$data['postcodes'][] = $locale->location_code;
				} elseif ( 'city' === $locale->location_type ) {
					$data['cities'][] = $locale->location_code;
				}
			}
		}

		return $data;
	}

	/**
	 * Get the taxes schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['postcodes'] = array(
			'description' => __( 'List of postcodes / ZIPs.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['cities'] = array(
			'description' => __( 'List of city names.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
			'context'     => array( 'view', 'edit' ),
		);

		return $schema;
	}

	/**
	 * Create a single tax.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response The response, or an error.
	 */
	public function create_item( $request ) {
		$this->adjust_cities_and_postcodes( $request );

		return parent::create_item( $request );
	}

	/**
	 * Update a single tax.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response The response, or an error.
	 */
	public function update_item( $request ) {
		$this->adjust_cities_and_postcodes( $request );

		return parent::update_item( $request );
	}

	/**
	 * Convert array "cities" and "postcodes" parameters
	 * into semicolon-separated strings "city" and "postcode".
	 *
	 * @param WP_REST_Request $request The request to adjust.
	 */
	private function adjust_cities_and_postcodes( &$request ) {
		if ( isset( $request['cities'] ) ) {
			$request['city'] = join( ';', $request['cities'] );
		}
		if ( isset( $request['postcodes'] ) ) {
			$request['postcode'] = join( ';', $request['postcodes'] );
		}
	}
}
