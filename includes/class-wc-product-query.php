<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for parameter-based Product querying.
 * Args and usage: {wiki link here}
 *
 * @version  3.2.0
 * @since    3.2.0
 * @package  WooCommerce/Classes
 * @category Class
 */
class WC_Product_Query extends WC_Object_Query {
/*
		'_visibility',
		'_sku',
		'_price',
		'_regular_price',
		'_sale_price',
		'_sale_price_dates_from',
		'_sale_price_dates_to',
		'total_sales',
		'_tax_status',
		'_tax_class',
		'_manage_stock',
		'_stock',
		'_stock_status',
		'_backorders',
		'_sold_individually',
		'_weight',
		'_length',
		'_width',
		'_height',
		'_upsell_ids',
		'_crosssell_ids',
		'_purchase_note',
		'_default_attributes',
		'_product_attributes',
		'_virtual',
		'_downloadable',
		'_featured',
		'_downloadable_files',
		'_wc_rating_count',
		'_wc_average_rating',
		'_wc_review_count',
		'_variation_description',
		'_thumbnail_id',
		'_file_paths',
		'_product_image_gallery',
		'_product_version',
		'_wp_old_slug',
		'_edit_last',
		'_edit_lock',
*/
	/**
	 * Valid query vars for products.
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			array(
				'status'                => 'publish',
				'type'                  => array( 'product', 'product_variation' ),


			)
		);
	}

	/**
	 * Get products matching the current query vars.
	 * @return array of WC_Product objects
	 */
	public function get_products() {
		$args = apply_filters( 'woocommerce_product_query_args', $this->get_query_vars() );
		$results = array();//WC_Data_Store::load( 'product' )->query( $args );
		return apply_filters( 'woocommerce_product_query', $results, $args );
	}
}
