<?php
/**
 * Deletes the terms with the given slugs from a product taxonomy.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{taxonomy: string, slugs: list<string>} $args
 * @return list<int> The deleted term IDs.
 */
return static function ( array $args ): array {
	$ids = get_terms(
		array(
			'taxonomy'   => $args['taxonomy'],
			'slug'       => $args['slugs'],
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	foreach ( $ids as $id ) {
		wp_delete_term( $id, $args['taxonomy'] );
	}

	return array_map( 'intval', $ids );
};
