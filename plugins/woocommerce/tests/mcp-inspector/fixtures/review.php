<?php
/**
 * A product of its own per `sku` with an approved review, a held review and a store reply to the approved one, replaced on every seed.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

$mcp_product = require __DIR__ . '/product.php';

/**
 * @param array{sku: string} $args
 * @return array{product: int, approved: int, held: int, reply: int}
 */
return static function ( array $args ) use ( $mcp_product ): array {
	$product = $mcp_product( array( 'sku' => $args['sku'] ) )['id'];

	foreach ( get_comments(
		array(
			'post_id' => $product,
			'status'  => 'all',
			'fields'  => 'ids',
		)
	) as $comment_id ) {
		wp_delete_comment( $comment_id, true );
	}

	$comment = static fn( array $props ): int => wp_insert_comment(
		$props + array(
			'comment_post_ID' => $product,
			'comment_author'  => 'MCP reviewer',
		)
	);

	$approved = $comment(
		array(
			'comment_type'     => 'review',
			'comment_content'  => 'Sturdy and well made',
			'comment_approved' => 1,
			'comment_meta'     => array( 'rating' => 5 ),
		)
	);

	return array(
		'product'  => $product,
		'approved' => $approved,
		'held'     => $comment(
			array(
				'comment_type'     => 'review',
				'comment_content'  => 'Awaiting moderation',
				'comment_approved' => 0,
				'comment_meta'     => array( 'rating' => 1 ),
			)
		),
		'reply'    => $comment(
			array(
				'comment_type'     => 'comment',
				'comment_content'  => 'Thank you',
				'comment_approved' => 1,
				'comment_parent'   => $approved,
				'user_id'          => get_user_by( 'login', 'admin' )->ID,
			)
		),
	);
};
