#!/usr/bin/env bash

set -euo pipefail

# `wp site empty` does not remove shipping zone methods: they are rows in the
# woocommerce_shipping_zone_methods table rather than posts or terms, so they
# survive it. Creating them again on a re-seed leaves zone 0 with four methods
# instead of two, at exit 0, and the specs that rely on the seeded shape then
# fail somewhere far from the seed. Clear the zone first so this is re-runnable.
wp eval "$(cat <<'PHP'
$zone = WC_Shipping_Zones::get_zone( 0 );

foreach ( $zone->get_shipping_methods() as $method ) {
	$zone->delete_shipping_method( $method->instance_id );
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
