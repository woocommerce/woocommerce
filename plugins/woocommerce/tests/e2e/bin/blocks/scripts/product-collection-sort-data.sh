#!/usr/bin/env bash

set -euo pipefail

wp eval "$(cat <<'PHP'
const PRODUCT_COLLECTION_SORT_FIXTURE_CREATED_VIA = 'blocks_product_collection_sort_fixture';

global $wpdb;

function wc_blocks_e2e_get_product_by_title( string $title ): WC_Product {
	$posts = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'title'          => $title,
		)
	);

	if ( empty( $posts ) ) {
		WP_CLI::error( sprintf( 'Could not find product "%s".', $title ) );
	}

	$product = wc_get_product( $posts[0]->ID );

	if ( ! $product instanceof WC_Product ) {
		WP_CLI::error( sprintf( 'Post for "%s" is not a product.', $title ) );
	}

	return $product;
}

function wc_blocks_e2e_normalize_rating_counts( array $rating_counts ): array {
	$normalized = array();

	foreach ( $rating_counts as $rating => $count ) {
		$normalized[ (int) $rating ] = (int) $count;
	}

	ksort( $normalized );

	return $normalized;
}

function wc_blocks_e2e_set_product_rating( string $title, float $average_rating, array $rating_counts, int $review_count ): void {
	$product = wc_blocks_e2e_get_product_by_title( $title );
	$product->set_average_rating( $average_rating );
	$product->set_rating_counts( $rating_counts );
	$product->set_review_count( $review_count );
	$product->save();
}

function wc_blocks_e2e_assert_product_rating_meta( string $title, float $expected_average_rating, array $expected_rating_counts, int $expected_review_count ): void {
	$product = wc_blocks_e2e_get_product_by_title( $title );
	$product_id = $product->get_id();

	$actual_average_rating = (float) get_post_meta( $product_id, '_wc_average_rating', true );

	if ( abs( $actual_average_rating - $expected_average_rating ) > 0.001 ) {
		WP_CLI::error(
			sprintf(
				'Expected "%s" average rating to be %s, got %s.',
				$title,
				$expected_average_rating,
				$actual_average_rating
			)
		);
	}

	$actual_rating_counts = get_post_meta( $product_id, '_wc_rating_count', true );
	$actual_rating_counts = is_array( $actual_rating_counts ) ? $actual_rating_counts : array();

	if ( wc_blocks_e2e_normalize_rating_counts( $actual_rating_counts ) !== wc_blocks_e2e_normalize_rating_counts( $expected_rating_counts ) ) {
		WP_CLI::error(
			sprintf(
				'Expected "%s" rating counts to be %s, got %s.',
				$title,
				wp_json_encode( wc_blocks_e2e_normalize_rating_counts( $expected_rating_counts ) ),
				wp_json_encode( wc_blocks_e2e_normalize_rating_counts( $actual_rating_counts ) )
			)
		);
	}

	$actual_review_count = (int) get_post_meta( $product_id, '_wc_review_count', true );

	if ( $actual_review_count !== $expected_review_count ) {
		WP_CLI::error(
			sprintf(
				'Expected "%s" review count to be %d, got %d.',
				$title,
				$expected_review_count,
				$actual_review_count
			)
		);
	}
}

function wc_blocks_e2e_assert_approved_product_reviews( string $title, int $expected_review_count ): void {
	$product = wc_blocks_e2e_get_product_by_title( $title );
	$actual_review_count = (int) get_comments(
		array(
			'post_id' => $product->get_id(),
			'status'  => 'approve',
			'type'    => 'review',
			'count'   => true,
		)
	);

	if ( $actual_review_count !== $expected_review_count ) {
		WP_CLI::error(
			sprintf(
				'Expected "%s" to preserve %d approved product reviews, got %d.',
				$title,
				$expected_review_count,
				$actual_review_count
			)
		);
	}
}

function wc_blocks_e2e_get_rating_sorted_product_titles(): array {
	$posts = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'meta_key'       => '_wc_average_rating',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);

	return array_map( 'get_the_title', $posts );
}

function wc_blocks_e2e_get_popularity_sorted_product_titles(): array {
	global $wpdb;

	return $wpdb->get_col(
		"
		SELECT posts.post_title
		FROM {$wpdb->posts} posts
		LEFT JOIN {$wpdb->wc_product_meta_lookup} product_lookup ON posts.ID = product_lookup.product_id
		WHERE posts.post_type = 'product'
			AND posts.post_status = 'publish'
		ORDER BY product_lookup.total_sales DESC, product_lookup.product_id DESC
		LIMIT 5
		"
	);
}

function wc_blocks_e2e_assert_product_titles( array $expected_titles, array $actual_titles, string $description ): void {
	if ( $actual_titles !== $expected_titles ) {
		WP_CLI::error(
			sprintf(
				'Expected %s products to be %s, got %s.',
				$description,
				wp_json_encode( $expected_titles ),
				wp_json_encode( $actual_titles )
			)
		);
	}
}

$reviewed_product_titles = array( 'Hoodie', 'Cap' );

$rating_fixtures = array(
	'V-Neck T-Shirt'   => array(
		'average_rating' => 5.0,
		'rating_counts'  => array( 5 => 1 ),
		'review_count'   => 1,
	),
	'Hoodie with Logo' => array(
		'average_rating' => 4.0,
		'rating_counts'  => array( 4 => 1 ),
		'review_count'   => 1,
	),
	'T-Shirt'          => array(
		'average_rating' => 3.5,
		'rating_counts'  => array(
			4 => 1,
			3 => 1,
		),
		'review_count'   => 2,
	),
	'Beanie'           => array(
		'average_rating' => 3.0,
		'rating_counts'  => array( 3 => 1 ),
		'review_count'   => 1,
	),
);

$expected_top_rated_product_titles = array(
	'V-Neck T-Shirt',
	'Hoodie',
	'Hoodie with Logo',
	'T-Shirt',
	'Beanie',
);

$sales_quantities = array(
	'Album'             => 5,
	'Hoodie'            => 4,
	'Single'            => 3,
	'Hoodie with Logo'  => 2,
	'T-Shirt with Logo' => 1,
);

$target_rating_product_titles = array_merge( $reviewed_product_titles, array_keys( $rating_fixtures ) );

foreach ( wc_get_products( array( 'limit' => -1 ) ) as $product ) {
	$product->set_total_sales( 0 );

	if ( ! in_array( $product->get_name(), $target_rating_product_titles, true ) ) {
		$product->set_average_rating( 0 );
		$product->set_rating_counts( array() );
		$product->set_review_count( 0 );
	}

	$product->save();
}

// Keep the existing real review fixtures intact, but recalculate their product
// aggregates so Product Collection rating order can depend on stable metadata.
foreach ( $reviewed_product_titles as $reviewed_product_title ) {
	WC_Comments::clear_transients( wc_blocks_e2e_get_product_by_title( $reviewed_product_title )->get_id() );
}

foreach ( $rating_fixtures as $title => $rating_fixture ) {
	wc_blocks_e2e_set_product_rating( $title, $rating_fixture['average_rating'], $rating_fixture['rating_counts'], $rating_fixture['review_count'] );
}

$fixture_orders = wc_get_orders(
	array(
		'created_via' => PRODUCT_COLLECTION_SORT_FIXTURE_CREATED_VIA,
		'limit'       => -1,
		'return'      => 'objects',
		'status'      => 'any',
		'type'        => 'shop_order',
	)
);

foreach ( $fixture_orders as $fixture_order ) {
	$fixture_order->delete( true );
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$order = wc_create_order(
	array(
		'created_via' => PRODUCT_COLLECTION_SORT_FIXTURE_CREATED_VIA,
	)
);

$order->set_date_created( new WC_DateTime( '2020-01-01 00:00:00', new DateTimeZone( 'UTC' ) ) );
$order->set_billing_first_name( 'Product' );
$order->set_billing_last_name( 'Collection' );
$order->set_billing_email( 'product-collection-sort-fixture@example.com' );

foreach ( $sales_quantities as $title => $quantity ) {
	$order->add_product( wc_blocks_e2e_get_product_by_title( $title ), $quantity );
}

$order->calculate_totals();
$order->set_status( 'completed' );
$order->save();

// Make the popularity fixture idempotent even when this script is run against
// an existing local database without first emptying the site.
foreach ( $sales_quantities as $title => $quantity ) {
	$product = wc_blocks_e2e_get_product_by_title( $title );
	$product->set_total_sales( $quantity );
	$product->save();
}

wc_update_product_lookup_tables();

wc_blocks_e2e_assert_approved_product_reviews( 'Hoodie', 2 );
wc_blocks_e2e_assert_approved_product_reviews( 'Cap', 2 );
wc_blocks_e2e_assert_product_rating_meta( 'Hoodie', 4.5, array( 5 => 1, 4 => 1 ), 2 );
wc_blocks_e2e_assert_product_rating_meta( 'Cap', 1.5, array( 2 => 1, 1 => 1 ), 2 );

foreach ( $rating_fixtures as $title => $rating_fixture ) {
	wc_blocks_e2e_assert_product_rating_meta( $title, $rating_fixture['average_rating'], $rating_fixture['rating_counts'], $rating_fixture['review_count'] );
}

foreach ( $sales_quantities as $title => $quantity ) {
	$product = wc_blocks_e2e_get_product_by_title( $title );
	$actual_total_sales = (int) get_post_meta( $product->get_id(), 'total_sales', true );
	$actual_lookup_total_sales = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT total_sales FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d",
			$product->get_id()
		)
	);

	if ( $actual_total_sales !== $quantity || $actual_lookup_total_sales !== $quantity ) {
		WP_CLI::error(
			sprintf(
				'Expected "%s" total sales to be %d, got meta %d and lookup %d.',
				$title,
				$quantity,
				$actual_total_sales,
				$actual_lookup_total_sales
			)
		);
	}
}

wc_blocks_e2e_assert_product_titles(
	$expected_top_rated_product_titles,
	wc_blocks_e2e_get_rating_sorted_product_titles(),
	'top rated'
);

wc_blocks_e2e_assert_product_titles(
	array_keys( $sales_quantities ),
	wc_blocks_e2e_get_popularity_sorted_product_titles(),
	'best sellers'
);
PHP
)"
