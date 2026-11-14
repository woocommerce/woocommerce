<?php
/**
 * A small catalog whose products differ in one property each, keyed by that difference.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

$mcp_product = require __DIR__ . '/product.php';

/**
 * @return array<string, array{id: int, sku: string}>
 */
return static function () use ( $mcp_product ): array {
	return array(
		'instock'    => $mcp_product( array( 'sku' => 'mcp-catalog-instock' ) ),
		'outofstock' => $mcp_product(
			array(
				'sku'          => 'mcp-catalog-outofstock',
				'stock_status' => 'outofstock',
			)
		),
		'virtual'    => $mcp_product(
			array(
				'sku'     => 'mcp-catalog-virtual',
				'virtual' => true,
			)
		),
		'draft'      => $mcp_product(
			array(
				'sku'    => 'mcp-catalog-draft',
				'status' => 'draft',
			)
		),
	);
};
