<?php
/**
 * Permanently deletes the products with the given SKUs, including trashed ones, which SKU lookups skip.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{skus: list<string>} $args
 * @return list<int> The deleted product IDs.
 */
return static function ( array $args ): array {
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => array_keys( get_post_stati() ),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- A handful of fixture products.
				array(
					'key'     => '_sku',
					'value'   => $args['skus'],
					'compare' => 'IN',
				),
			),
			'fields'         => 'ids',
			'posts_per_page' => -1,
		)
	);

	foreach ( $ids as $id ) {
		wc_get_product( $id )->delete( true );
	}

	return array_map( 'intval', $ids );
};
