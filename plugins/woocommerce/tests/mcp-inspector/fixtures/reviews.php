<?php
/**
 * Two products with reviews, one with none, and a held review, replaced on every seed.
 *
 * Review dates are an hour apart in the order listed, oldest first.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

$mcp_product = require __DIR__ . '/product.php';

/**
 * @return array{loved: int, panned: int, unreviewed: int, reviews: array<string, int>}
 */
return static function () use ( $mcp_product ): array {
	$products = array(
		'loved'      => $mcp_product( array( 'sku' => 'mcp-reviews-loved' ) )['id'],
		'panned'     => $mcp_product( array( 'sku' => 'mcp-reviews-panned' ) )['id'],
		'unreviewed' => $mcp_product( array( 'sku' => 'mcp-reviews-unreviewed' ) )['id'],
	);

	foreach ( get_comments(
		array(
			'post__in' => array_values( $products ),
			'status'   => 'all',
			'fields'   => 'ids',
		)
	) as $comment_id ) {
		wp_delete_comment( $comment_id, true );
	}

	$reviews = array(
		'loved-five'  => array( 'loved', 5, 1, 'Sturdy and well made' ),
		'loved-three' => array( 'loved', 3, 1, 'Fine, but the strap broke' ),
		'panned-two'  => array( 'panned', 2, 1, 'Arrived scratched' ),
		'loved-held'  => array( 'loved', 1, 0, 'Awaiting moderation' ),
	);
	$time    = time() - DAY_IN_SECONDS;
	$ids     = array();
	foreach ( $reviews as $key => list( $product, $rating, $approved, $text ) ) {
		$time       += HOUR_IN_SECONDS;
		$ids[ $key ] = wp_insert_comment(
			array(
				'comment_post_ID'  => $products[ $product ],
				'comment_type'     => 'review',
				'comment_content'  => $text,
				'comment_author'   => 'MCP reviewer',
				'comment_approved' => $approved,
				'comment_date'     => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $time ) ),
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $time ),
				'comment_meta'     => array( 'rating' => $rating ),
			)
		);
	}

	return array_merge( $products, array( 'reviews' => $ids ) );
};
