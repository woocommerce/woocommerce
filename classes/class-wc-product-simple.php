<?php
/**
 * Simple Product Class
 *
 * The default product type kinda product
 *
 * @class 		WC_Product_Simple
 * @version		1.7.0
 * @package		WooCommerce/Classes/Products
 * @author 		WooThemes
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Product_Simple extends WC_Product {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $product
	 * @param array $args Contains arguments to set up this product
	 */
	function __construct( $product, $args ) {

		parent::__construct( $product );

		$this->product_type = 'simple';
		$this->product_custom_fields = get_post_custom( $this->id );

		// Load data from custom fields
		$this->load_product_data( array(
			'sku'			=> '',
			'downloadable' 	=> 'no',
			'virtual' 		=> 'no',
			'price' 		=> '',
			'visibility'	=> 'hidden',
			'stock'			=> 0,
			'stock_status'	=> 'instock',
			'backorders'	=> 'no',
			'manage_stock'	=> 'no',
			'sale_price'	=> '',
			'regular_price' => '',
			'weight'		=> '',
			'length'		=> '',
			'width'		=> '',
			'height'		=> '',
			'tax_status'	=> 'taxable',
			'tax_class'		=> '',
			'upsell_ids'	=> array(),
			'crosssell_ids' => array(),
			'sale_price_dates_from' => '',
			'sale_price_dates_to' 	=> '',
			'featured'		=> 'no'
		) );

		$this->check_sale_price();
	}
}