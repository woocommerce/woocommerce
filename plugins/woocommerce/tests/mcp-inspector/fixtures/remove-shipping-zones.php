<?php
/**
 * Deletes the shipping zones with the given names, with their methods.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{names: list<string>} $args
 * @return list<int> The deleted zone IDs.
 */
return static function ( array $args ): array {
	$ids = array();
	foreach ( WC_Shipping_Zones::get_zones() as $data ) {
		if ( in_array( $data['zone_name'], $args['names'], true ) ) {
			( new WC_Shipping_Zone( $data['id'] ) )->delete();
			$ids[] = (int) $data['id'];
		}
	}

	return $ids;
};
