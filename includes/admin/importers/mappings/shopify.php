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
 * @param array $mappings Importer columns mappings.
 * @return array
 */
function wc_importer_shopify_mappings( $mappings ) {
	$shopify_mappings = array(
		__( 'Variant SKU', 'woocommerce' )   => 'sku',
		__( 'Title', 'woocommerce' )         => 'name',
		__( 'Body (HTML)', 'woocommerce' )   => 'description',
		__( 'Quantity', 'woocommerce' )      => 'stock_quantity',
		__( 'Variant Inventory Qty', 'woocommerce' ) => 'stock_quantity',
		__( 'Image Src', 'woocommerce' )     => 'images',
		__( 'Variant Image', 'woocommerce' ) => 'images',
		__( 'Variant SKU', 'woocommerce' )   => 'sku',
		__( 'Variant Price', 'woocommerce' ) => 'sale_price',
		__( 'Variant Compare At Price', 'woocommerce' ) => 'regular_price',
		__( 'Type', 'woocommerce' )          => 'category_ids',
		__( 'Variant Grams', 'woocommerce' ) => 'weight',
	);
	return array_merge( $mappings, $shopify_mappings );
}
add_filter( 'woocommerce_csv_product_import_mapping_default_columns', 'wc_importer_shopify_mappings' );
