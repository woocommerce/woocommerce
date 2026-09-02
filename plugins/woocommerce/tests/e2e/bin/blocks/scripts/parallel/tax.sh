#!/usr/bin/env bash

set -euo pipefail

wp option set woocommerce_calc_taxes yes

# `wp site empty` does not remove tax rates either: they are rows in the
# woocommerce_tax_rates table, so a re-seed turns three rates into six at
# exit 0. The duplicates do not change any total, since WooCommerce still
# applies one rate per priority per class, but they leave the fixture lying
# about its own shape. Clear them first so this is re-runnable.
wp eval "$(cat <<'PHP'
global $wpdb;

$rate_ids = $wpdb->get_col( "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates" );

foreach ( $rate_ids as $rate_id ) {
	WC_Tax::_delete_tax_rate( $rate_id );
}

$required_tax_classes = array(
	'reduced-rate' => 'Reduced rate',
	'zero-rate'    => 'Zero rate',
);

foreach ( $required_tax_classes as $slug => $name ) {
	if ( WC_Tax::get_tax_class_by( 'slug', $slug ) ) {
		continue;
	}

	$result = WC_Tax::create_tax_class( $name, $slug );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( sprintf( 'Could not create tax class "%1$s": %2$s', $slug, $result->get_error_message() ) );
	}
}
PHP
)"

wp wc tax create \
    --user=1 \
    --rate=20 \
    --class=standard

wp wc tax create \
    --user=1 \
    --rate=10 \
    --class=reduced-rate

wp wc tax create \
    --user=1 \
    --rate=0 \
    --class=zero-rate
