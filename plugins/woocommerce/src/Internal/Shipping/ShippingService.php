<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Shipping;

use WC_Shipping_Zones;

/**
 * A service class to manage shipping zones and their methods & locations.
 *
 * @internal
 */
class ShippingService {

	public function get_sorted_shipping_zones(){
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
}
