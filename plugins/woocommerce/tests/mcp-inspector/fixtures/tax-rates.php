<?php
/**
 * Tax rates replaced on every seed: German standard and reduced rates, and one for every country.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

$mcp_remove_tax_rates = require __DIR__ . '/remove-tax-rates.php';

/**
 * @return array{standard: int, reduced: int, everywhere: int}
 */
return static function () use ( $mcp_remove_tax_rates ): array {
	$mcp_remove_tax_rates( array( 'names' => array( 'MCP DE standard', 'MCP DE reduced', 'MCP everywhere' ) ) );

	$rate = static function ( string $name, string $country, string $rate, string $tax_class, array $postcodes = array() ): int {
		$id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => $country,
				'tax_rate'          => $rate,
				'tax_rate_name'     => $name,
				'tax_rate_priority' => 1,
				'tax_rate_shipping' => 1,
				'tax_rate_class'    => $tax_class,
			)
		);
		if ( $postcodes ) {
			WC_Tax::_update_tax_rate_postcodes( $id, implode( ';', $postcodes ) );
		}
		return $id;
	};

	return array(
		'standard'   => $rate( 'MCP DE standard', 'DE', '19.0000', '', array( '10115', '10117' ) ),
		'reduced'    => $rate( 'MCP DE reduced', 'DE', '7.0000', 'reduced-rate' ),
		'everywhere' => $rate( 'MCP everywhere', '', '1.0000', '' ),
	);
};
