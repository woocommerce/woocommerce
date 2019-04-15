<?php
/**
 * Shopify mappings
 *
 * @package WooCommerce\Admin\Importers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Shopify mappings.
 *
 * @since 3.7.0
 * @param array $mappings    Importer columns mappings.
 * @param array $raw_headers Raw headers from CSV being imported.
 * @return array
 */
function wc_importer_shopify_mappings( $mappings, $raw_headers ) {
	// Only map if this is looks like a Shopify export.
	if ( 0 !== count( array_diff( array( 'Title', 'Body (HTML)', 'Type', 'Variant SKU' ), $raw_headers ) ) ) {
		return $mappings;
	}
	$shopify_mappings = array(
		'Variant SKU'              => 'sku',
		'Title'                    => 'name',
		'Body (HTML)'              => 'description',
		'Quantity'                 => 'stock_quantity',
		'Variant Inventory Qty'    => 'stock_quantity',
		'Image Src'                => 'images',
		'Variant Image'            => 'images',
		'Variant SKU'              => 'sku',
		'Variant Price'            => 'sale_price',
		'Variant Compare At Price' => 'regular_price',
		'Type'                     => 'category_ids',
		'Variant Grams'            => 'weight',
	);
	return array_merge( $mappings, $shopify_mappings );
}
add_filter( 'woocommerce_csv_product_import_mapping_default_columns', 'wc_importer_shopify_mappings', 10, 2 );
