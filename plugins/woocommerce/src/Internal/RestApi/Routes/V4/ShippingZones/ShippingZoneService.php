<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZones;

use WC_Shipping_Zones;
use WC_Shipping_Zone;
use WP_Error;
use WP_Http;

/**
 * A service class to manage shipping zones their locations.
 */
class ShippingZoneService {
		/**
		 * Get all shipping zones sorted by order.
		 *
		 * @return array Array of shipping zones sorted by zone_order.
		 */
	public function get_sorted_shipping_zones() {
		$zones             = WC_Shipping_Zones::get_zones();
		$rest_of_the_world = WC_Shipping_Zones::get_zone_by( 'zone_id', 0 );

		$rest_data                            = $rest_of_the_world->get_data();
		$rest_data['zone_id']                 = $rest_of_the_world->get_id();
		$rest_data['formatted_zone_location'] = array();
		$rest_data['shipping_methods']        = $rest_of_the_world->get_shipping_methods( false, 'admin' );
		$zones[0]                             = $rest_data;

		uasort(
			$zones,
			function ( $a, $b ) {
				return $a['zone_order'] <=> $b['zone_order'];
			}
		);

		return $zones;
	}

	/**
	 * Create a new shipping zone.
	 *
	 * @param array $params {
	 *     Zone parameters.
	 *
	 *     @type string $name      Zone name.
	 *     @type int    $order     Zone order for sorting.
	 *     @type array  $locations Array of location objects with 'code' and 'type' keys.
	 * }
	 * @return WC_Shipping_Zone|WP_Error Zone object on success, WP_Error on failure.
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
	 * Update an existing shipping zone.
	 *
	 * @param WC_Shipping_Zone $zone   Zone object to update.
	 * @param array            $params {
	 *     Zone parameters to update. All parameters are optional.
	 *
	 *     @type string $name      Zone name. Cannot be changed for "Rest of the World" zone (ID 0).
	 *     @type int    $order     Zone order for sorting. Cannot be changed for "Rest of the World" zone.
	 *     @type array  $locations Array of location objects. Cannot be changed for "Rest of the World" zone.
	 *                             Each location should have 'code' (string) and 'type' (string) keys.
	 *                             Valid types: 'postcode', 'state', 'country', 'continent'.
	 * }
	 * @return WC_Shipping_Zone|WP_Error Updated zone object on success, WP_Error on failure.
	 */
	public function update_shipping_zone( $zone, $params ) {
		$params = wp_parse_args(
			$params,
			array(
				'name'      => null,
				'order'     => null,
				'locations' => null,
			)
		);

		$is_rest_of_world = 0 === $zone->get_id();

		if ( ! is_null( $params['name'] ) ) {
			if ( $is_rest_of_world ) {
				return new WP_Error(
					'woocommerce_rest_cannot_edit_zone',
					__( 'Cannot change name of "Rest of the World" zone.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

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

		if ( ! is_null( $params['order'] ) ) {
			if ( $is_rest_of_world ) {
				return new WP_Error(
					'woocommerce_rest_cannot_edit_zone',
					__( 'Cannot change order of "Rest of the World" zone.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			$zone->set_zone_order( $params['order'] );
		}

		$locations_being_cleared = false;
		if ( ! is_null( $params['locations'] ) ) {
			if ( $is_rest_of_world ) {
				return new WP_Error(
					'woocommerce_rest_cannot_edit_zone',
					__( 'Cannot change locations of "Rest of the World" zone.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			$raw_locations = $params['locations'];
			$locations     = array();

			foreach ( (array) $raw_locations as $raw_location ) {
				$locations_being_cleared = false;
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

			$locations_being_cleared = empty( $locations );

			$zone->set_locations( $locations );
		}

		$zone->save();

		// WORKAROUND: WC_Data::apply_changes() uses array_replace_recursive() which doesn't
		// properly clear array properties when set to empty arrays. After save(), get_zone_locations()
		// returns stale cached data. Only reload when clearing locations to get accurate state.
		if ( $locations_being_cleared ) {
			$zone = WC_Shipping_Zones::get_zone( $zone->get_id() );
		}

		return $zone;
	}
}
