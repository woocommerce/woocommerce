<?php
/**
 * A global attribute found by its slug, with its terms reset to the given names in that order.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{name: string, slug: string, terms?: list<string>} $args
 * @return array{id: int, taxonomy: string}
 */
return static function ( array $args ): array {
	$id = wc_attribute_taxonomy_id_by_name( $args['slug'] );
	if ( ! $id ) {
		$id = wc_create_attribute(
			array(
				'name' => $args['name'],
				'slug' => $args['slug'],
			)
		);
	}

	// An attribute created in this request has no taxonomy until the next init.
	$taxonomy = wc_attribute_taxonomy_name( $args['slug'] );
	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy( $taxonomy, 'product' );
	}

	foreach ( get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	) as $term_id ) {
		wp_delete_term( $term_id, $taxonomy );
	}
	foreach ( $args['terms'] ?? array() as $order => $name ) {
		$term = wp_insert_term( $name, $taxonomy );
		update_term_meta( $term['term_id'], 'order', $order );
	}

	return array(
		'id'       => $id,
		'taxonomy' => $taxonomy,
	);
};
