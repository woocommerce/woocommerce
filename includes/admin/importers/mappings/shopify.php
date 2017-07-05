<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add mappings for Shopify CSV format.
 *
 * @since 3.2.0
 * @param array $mappings
 * @return array
 */
function wc_importer_shopify_mappings( $mappings, $raw_headers ) {
	// Is this shopify?
	if ( 0 === count( array_diff( array( 'Handle', 'Title', 'Body (HTML)', 'Type', 'Variant SKU' ), $raw_headers ) ) ) {
		$shopify_mappings = array(
			'Handle'                       => 'slug',
			'Title'                        => 'name',
			'Body (HTML)'                  => 'description',
			'Type'                         => 'category_ids',
			'Tags'                         => 'tag_ids',
			'Published'                    => 'published',
			'Option 1 Name'                => 'attributes:name',
			'Option 1 Value'               => 'attributes:value',
			'Option2 Name'                 => 'attributes:name',
			'Option2 Value'                => 'attributes:value',
			'Option3 Name'                 => 'attributes:name',
			'Option3 Value'                => 'attributes:value',
			'Variant SKU'                  => 'sku',
			'Variant Grams'                => 'weight',
			'Variant Inventory Quantity'   => 'stock_quantity',
			'Variant Price'                => 'sale_price',
			'Variant Compare at Price'     => 'regular_price',
			// 'Variant Requires Shipping' => '',
			'Variant Taxable'              => 'tax_status',
			'Image Src'                    => 'Images',
		);
		$mappings = array_merge( $mappings, $shopify_mappings );
	}
	return $mappings;
}
add_filter( 'woocommerce_csv_product_import_mapping_default_columns', 'wc_importer_shopify_mappings', 20, 2 );
