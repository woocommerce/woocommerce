<?php
/**
 * Permanently deletes the coupons with the given codes, including drafts and trashed ones.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{codes: list<string>} $args
 * @return list<int> The deleted coupon IDs.
 */
return static function ( array $args ): array {
	$ids = get_posts(
		array(
			'post_type'      => 'shop_coupon',
			'post_status'    => 'any,trash',
			'post_name__in'  => array_map( 'sanitize_title', $args['codes'] ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}

	return array_map( 'intval', $ids );
};
