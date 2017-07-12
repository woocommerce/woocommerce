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

	/**
	 * Valid query vars for products.
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			array(
				'status'             => 'publish',
				'type'               => array( 'product', 'product_variation' ),
				'slug'               => '',
				'date_created'       => '',
				'date_modified'      => '',
				'featured'           => '',
				'catalog_visibility' => '',
				'description'        => '',
				'short_description'  => '',
				'sku'                => '',
				'price'              => '',
				'regular_price'      => '',
				'sale_price'         => '',
				'date_on_sale_from'  => '',
				'date_on_sale_to'    => '',
				'total_sales'        => '',
				'tax_status'         => '',
				'tax_class'          => '',
				'manage_stock'       => '',
				'stock_quantity'     => '',
				'stock_status'       => '',
				'backorders'         => '',
				'sold_individually'  => '',
				'weight'             => '',
				'length'             => '',
				'width'              => '',
				'height'             => '',
				'upsell_ids'         => array(),
				'cross_sell_ids'     => array(),
				'reviews_allowed'    => '',
				'purchase_note'      => '',
				'attributes'         => array(),
				'default_attributes' => array(),
				'menu_order'         => '',
				'virtual'            => '',
				'downloadable'       => '',
				'category_ids'       => array(),
				'tag_ids'            => array(),
				'shipping_class_id'  => '',
				'image_id'           => '',
				'download_limit'     => '',
				'download_expiry'    => '',
				'rating_counts'      => array(),
				'average_rating'     => '',
				'review_count'       => '',
			)
		);
	}

	/**
	 * Get products matching the current query vars.
	 * @return array of WC_Product objects
	 */
	public function get_products() {
		$args = apply_filters( 'woocommerce_product_query_args', $this->get_query_vars() );
		$results = WC_Data_Store::load( 'product' )->query( $args );
		return apply_filters( 'woocommerce_product_query', $results, $args );
	}
}
