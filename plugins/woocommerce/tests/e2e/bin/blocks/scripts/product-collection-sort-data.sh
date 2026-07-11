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

$products = array();

foreach ( wc_get_products( array( 'limit' => -1 ) ) as $product ) {
	$products[ $product->get_name() ] = $product;
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
	$product->save();

	// Cap is the existing one-star Rating Filter fixture. Leave its rating state intact.
	if ( 'Cap' !== $product_name ) {
		update_post_meta(
			$product->get_id(),
			'_wc_average_rating',
			$rating_fixtures[ $product_name ] ?? 0
		);
	}
}

// Product saves update changed rows. Regeneration also repairs stale lookup rows on reruns.
wc_update_product_lookup_tables();

foreach ( $rating_fixtures as $product_name => $expected_rating ) {
	$actual_rating = (float) get_post_meta(
		$products[ $product_name ]->get_id(),
		'_wc_average_rating',
		true
	);

	if ( abs( $actual_rating - $expected_rating ) > 0.001 ) {
		WP_CLI::error(
			sprintf(
				'Expected "%s" average rating to be %s, got %s.',
				$product_name,
				$expected_rating,
				$actual_rating
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

foreach ( array( 'Hoodie', 'Cap' ) as $product_name ) {
	$actual_review_count = (int) get_comments(
		array(
			'post_id' => $products[ $product_name ]->get_id(),
			'status'  => 'approve',
			'type'    => 'review',
			'count'   => true,
		)
	);

	if ( 2 !== $actual_review_count ) {
		WP_CLI::error(
			sprintf(
				'Expected "%s" to retain 2 approved reviews, got %d.',
				$product_name,
				$actual_review_count
			)
		);
	}
}
PHP
)"
