<?php
/**
 * A French tax rate in the reduced rate class with the given name, replaced on every seed.
 *
 * The reduced rate class keeps it out of the taxes of the standard rate products that other tests order.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

$mcp_remove_tax_rates = require __DIR__ . '/remove-tax-rates.php';

/**
 * @param array{name: string} $args
 * @return array{id: int}
 */
return static function ( array $args ) use ( $mcp_remove_tax_rates ): array {
	$mcp_remove_tax_rates( array( 'names' => array( $args['name'] ) ) );

	$id = WC_Tax::_insert_tax_rate(
		array(
			'tax_rate_country'  => 'FR',
			'tax_rate'          => '5.5000',
			'tax_rate_name'     => $args['name'],
			'tax_rate_priority' => 1,
			'tax_rate_shipping' => 1,
			'tax_rate_class'    => 'reduced-rate',
		)
	);
	WC_Tax::_update_tax_rate_postcodes( $id, '75001' );

	return array( 'id' => $id );
};
