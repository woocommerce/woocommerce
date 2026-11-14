<?php
/**
 * Deletes the global attributes with the given slugs, with their terms.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{slugs: list<string>} $args
 * @return list<int> The deleted attribute IDs.
 */
return static function ( array $args ): array {
	$ids = array();
	foreach ( $args['slugs'] as $slug ) {
		$id = wc_attribute_taxonomy_id_by_name( $slug );
		if ( $id && wc_delete_attribute( $id ) ) {
			$ids[] = $id;
		}
	}

	return $ids;
};
