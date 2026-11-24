<?php
/**
 * Deletes the tax rates with the given names.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array{names: list<string>} $args
 * @return list<int> The deleted tax rate IDs.
 */
return static function ( array $args ): array {
	global $wpdb;

	$ids = array();
	foreach ( $args['names'] as $name ) {
		$ids = array_merge(
			$ids,
			array_map(
				'intval',
				$wpdb->get_col( $wpdb->prepare( "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_name = %s", $name ) )
			)
		);
	}

	foreach ( $ids as $id ) {
		WC_Tax::_delete_tax_rate( $id );
	}

	return $ids;
};
