<?php
/**
 * A zone for Germany with a flat rate, found by its name and reset on every seed.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @return array{id: int, flat_rate: int, name: string}
 */
return static function (): array {
	$name = 'MCP fixture zone';
	foreach ( WC_Shipping_Zones::get_zones() as $data ) {
		if ( $name === $data['zone_name'] ) {
			( new WC_Shipping_Zone( $data['id'] ) )->delete();
		}
	}

	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( $name );
	$zone->set_locations(
		array(
			array(
				'code' => 'DE',
				'type' => 'country',
			),
		)
	);
	$zone->save();

	$instance_id = $zone->add_shipping_method( 'flat_rate' );
	update_option(
		"woocommerce_flat_rate_{$instance_id}_settings",
		array(
			'title'      => 'MCP flat rate',
			'tax_status' => 'taxable',
			'cost'       => '5',
		)
	);

	return array(
		'id'        => $zone->get_id(),
		'flat_rate' => $instance_id,
		'name'      => $name,
	);
};
