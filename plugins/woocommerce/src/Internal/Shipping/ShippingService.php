<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Shipping;

use WC_Shipping_Zones;
use WC_Shipping_Zone;
use WP_Error;
use WP_Http;

/**
 * A service class to manage shipping zones and their methods & locations.
 *
 * @internal
 */
class ShippingService {

	public function get_sorted_shipping_zones() {
		// Get all zones including "Rest of the World".
		$zones             = WC_Shipping_Zones::get_zones();
		$rest_of_the_world = WC_Shipping_Zones::get_zone_by( 'zone_id', 0 );

		// Add "Rest of the World" zone at the end.
		$zones[0] = $rest_of_the_world->get_data();

		// Sort zones by order.
		uasort(
			$zones,
			function ( $a, $b ) {
				return $a['zone_order'] <=> $b['zone_order'];
			}
		);

		return $zones;
	}

	/**
	 * Create a shipping zone from REST API request.
	 *
	 * @param array $params Request parameters.
	 * @return WC_Shipping_Zone|WP_Error True on success, WP_Error on failure.
	 */
	public function create_shipping_zone( $params ) {
		$zone   = new WC_Shipping_Zone( null );
		$result = $this->update_shipping_zone( $zone, $params );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return $zone;
	}

	/**
	 * Update zone details from REST API request.
	 *
	 * @param WC_Shipping_Zone $zone zone to be updated.
	 * @param array            $params Request parameters.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function update_shipping_zone( $zone, $params ) {

		// Prevent updating "Rest of the World" zone name, order, or locations.
		if ( 0 === $zone->get_id() ) {
			if ( isset( $params['name'] ) && ! is_null( $params['name'] ) ) {
				return new WP_Error(
					'woocommerce_rest_cannot_edit_zone',
					__( 'Cannot change name of "Rest of the World" zone.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			if ( isset( $params['order'] ) && ! is_null( $params['order'] ) ) {
				return new WP_Error(
					'woocommerce_rest_cannot_edit_zone',
					__( 'Cannot change order of "Rest of the World" zone.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			if ( isset( $params['locations'] ) && ! is_null( $params['locations'] ) ) {
				return new WP_Error(
					'woocommerce_rest_cannot_edit_zone',
					__( 'Cannot change locations of "Rest of the World" zone.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
		}

		// Set zone name if provided.
		if ( isset( $params['name'] ) && ! is_null( $params['name'] ) ) {
			$name = trim( $params['name'] );
			if ( '' === $name ) {
				return new WP_Error(
					'woocommerce_rest_invalid_zone_name',
					__( 'Zone name cannot be empty.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			$zone->set_zone_name( $name );
		}

		// Set zone order if provided.
		if ( isset( $params['order'] ) && ! is_null( $params['order'] ) ) {
			$zone->set_zone_order( $params['order'] );
		}

		// Set locations if provided.
		if ( isset( $params['locations'] ) && ! is_null( $params['locations'] ) ) {
			$raw_locations = $params['locations'];
			$locations     = array();

			foreach ( (array) $raw_locations as $raw_location ) {
				if ( empty( $raw_location['code'] ) ) {
					continue;
				}

				$type = ! empty( $raw_location['type'] ) ? $raw_location['type'] : 'country';

				// Normalize 'country:state' to 'state' for v4 API backward compatibility.
				if ( 'country:state' === $type ) {
					$type = 'state';
				}

				if ( ! $zone->is_valid_location_type( $type ) ) {
					continue;
				}

				$locations[] = array(
					'code' => $raw_location['code'],
					'type' => $type,
				);
			}

			$zone->set_locations( $locations );
		}

		// Save the zone.
		$zone->save();

		return true;
	}
}
