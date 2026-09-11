#!/usr/bin/env bash

set -euo pipefail

# `wp site empty` does not remove shipping zones or methods. Converge the full
# topology so this profile always uses only its zone 0 Flat/Free pair, even
# after another profile creates a named smart-default zone.
wp eval "$(cat <<'PHP'
foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
	WC_Shipping_Zones::delete_zone( $zone_data['zone_id'] );
}

$remaining_zones = WC_Shipping_Zones::get_zones();
if ( ! empty( $remaining_zones ) ) {
	WP_CLI::error( sprintf( 'Unable to delete %d named shipping zone(s).', count( $remaining_zones ) ) );
}

$zone = WC_Shipping_Zones::get_zone( 0 );

foreach ( $zone->get_shipping_methods() as $method ) {
	$zone->delete_shipping_method( $method->instance_id );
}

update_option( 'woocommerce_admin_created_default_shipping_zones', 'yes' );
if ( 'yes' !== get_option( 'woocommerce_admin_created_default_shipping_zones' ) ) {
	WP_CLI::error( 'Unable to persist the smart-default shipping zone marker.' );
}
PHP
)"

wp wc shipping_zone_method create 0 \
	--order=1 \
	--enabled=true \
	--user=1 \
	--settings='{"title":"Flat rate shipping", "cost": "10"}' \
	--method_id=flat_rate

wp wc shipping_zone_method create 0 \
	--order=2 \
	--enabled=true \
	--user=1 \
	--settings='{"title":"Free shipping"}' \
	--method_id=free_shipping
