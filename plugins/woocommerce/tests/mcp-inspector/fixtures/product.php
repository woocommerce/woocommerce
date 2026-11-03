<?php
/**
 * A simple product found by its SKU, reset on every seed to the defaults below merged with the given props.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @param array<string, mixed> $props Product props, `sku` required.
 * @return array{id: int, sku: string}
 */
return static function ( array $props ): array {
	$id      = wc_get_product_id_by_sku( $props['sku'] );
	$product = $id ? wc_get_product( $id ) : new WC_Product_Simple();

	$product->set_props(
		array_merge(
			array(
				'name'          => 'MCP fixture ' . $props['sku'],
				'regular_price' => '10',
				'sale_price'    => '',
				'status'        => 'publish',
				'stock_status'  => 'instock',
				'manage_stock'  => false,
				'virtual'       => false,
			),
			$props
		)
	);
	$product->save();

	return array(
		'id'  => $product->get_id(),
		'sku' => $product->get_sku(),
	);
};
