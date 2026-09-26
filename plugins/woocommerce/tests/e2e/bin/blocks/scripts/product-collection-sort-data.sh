#!/usr/bin/env bash

set -euo pipefail

wp eval "$(cat <<'PHP'
global $wpdb;

$rating_fixtures = array(
	'V-Neck T-Shirt'   => 5.0,
	'Hoodie'           => 4.5,
	'Hoodie with Logo' => 4.0,
	'T-Shirt'          => 3.5,
	'Beanie'           => 3.0,
);

$sales_fixtures = array(
	'Album'             => 5,
	'Hoodie'            => 4,
	'Single'            => 3,
	'Hoodie with Logo'  => 2,
	'T-Shirt with Logo' => 1,
);

/**
 * Build the smallest rating distribution whose mean is the requested average.
 *
 * Whole averages need a single bucket; half steps need the two buckets around
 * them. Products carry a rating count alongside the average so their aggregate
 * state stays reachable by a real store: an average with a zero count renders
 * stars in loop and block contexts while the single product template hides
 * them, which reads as a bug to the next spec that looks at it.
 *
 * @param float $rating Target average rating.
 * @return array Rating counts keyed by star value.
 */
$rating_counts_for = static function ( $rating ) {
	if ( $rating <= 0 ) {
		return array();
	}

	$low  = (int) floor( $rating );
	$high = (int) ceil( $rating );

	return $low === $high ? array( $low => 1 ) : array( $low => 1, $high => 1 );
};

$products = array();

foreach ( wc_get_products( array( 'limit' => -1 ) ) as $product ) {
	$product_name = $product->get_name();

	if ( isset( $products[ $product_name ] ) ) {
		WP_CLI::error(
			sprintf(
				'Expected product names to be unique, but found "%1$s" on product IDs %2$d and %3$d.',
				$product_name,
				$products[ $product_name ]->get_id(),
				$product->get_id()
			)
		);
	}

	$products[ $product_name ] = $product;
}

$required_product_names = array_unique(
	array_merge( array_keys( $rating_fixtures ), array_keys( $sales_fixtures ), array( 'Cap' ) )
);

foreach ( $required_product_names as $product_name ) {
	if ( ! isset( $products[ $product_name ] ) ) {
		WP_CLI::error( sprintf( 'Could not find product "%s".', $product_name ) );
	}
}

foreach ( $products as $product_name => $product ) {
	$product->set_total_sales( $sales_fixtures[ $product_name ] ?? 0 );

	// Cap is the existing one-star Rating Filter fixture. Leave its rating state intact.
	if ( 'Cap' !== $product_name ) {
		$target_rating = $rating_fixtures[ $product_name ] ?? 0;

		// Write the rating as a product property rather than as post meta. Only a
		// changed `average_rating` property makes the data store recompute the
		// `rated-N` product_visibility terms that the Rating Filter matches on;
		// a bare update_post_meta() call moves the sorting aggregate while
		// leaving those terms behind, so the Rating Filter ends up offering
		// options that its own results cannot satisfy.
		$product->set_average_rating( $target_rating );
		$product->set_rating_counts( $rating_counts_for( $target_rating ) );
	}

	// Products the fixtures do not name already hold the values set above, so
	// saving them writes nothing but a fresh post_modified stamp.
	if ( $product->get_changes() ) {
		$product->save();
	}
}

// This runs synchronously under WP-CLI and also repairs stale lookup rows on reruns.
wc_update_product_lookup_tables();

foreach ( $rating_fixtures as $product_name => $expected_rating ) {
	$product_id = $products[ $product_name ]->get_id();

	$actual_rating        = (float) get_post_meta( $product_id, '_wc_average_rating', true );
	$actual_lookup_rating = (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT average_rating FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d",
			$product_id
		)
	);

	if (
		abs( $actual_rating - $expected_rating ) > 0.001 ||
		abs( $actual_lookup_rating - $expected_rating ) > 0.001
	) {
		WP_CLI::error(
			sprintf(
				'Expected "%s" average rating to be %s, got meta %s and lookup %s.',
				$product_name,
				$expected_rating,
				$actual_rating,
				$actual_lookup_rating
			)
		);
	}

	// The sorting aggregate and the Rating Filter read different storage, so
	// assert the visibility term rather than assuming the save propagated.
	$expected_term = 'rated-' . min( 5, (int) round( $expected_rating ) );
	$actual_terms  = wp_get_post_terms( $product_id, 'product_visibility', array( 'fields' => 'names' ) );

	if ( is_wp_error( $actual_terms ) || ! in_array( $expected_term, $actual_terms, true ) ) {
		WP_CLI::error(
			sprintf(
				'Expected "%s" to carry the "%s" product_visibility term, got [%s].',
				$product_name,
				$expected_term,
				is_wp_error( $actual_terms ) ? $actual_terms->get_error_message() : implode( ', ', $actual_terms )
			)
		);
	}
}

foreach ( $sales_fixtures as $product_name => $expected_sales ) {
	$product_id          = $products[ $product_name ]->get_id();
	$actual_meta_sales   = (int) get_post_meta( $product_id, 'total_sales', true );
	$actual_lookup_sales = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT total_sales FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d",
			$product_id
		)
	);

	if ( $actual_meta_sales !== $expected_sales || $actual_lookup_sales !== $expected_sales ) {
		WP_CLI::error(
			sprintf(
				'Expected "%s" total sales to be %d, got meta %d and lookup %d.',
				$product_name,
				$expected_sales,
				$actual_meta_sales,
				$actual_lookup_sales
			)
		);
	}
}

PHP
)"
