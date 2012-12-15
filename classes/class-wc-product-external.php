<?php
/**
 * External Product Class
 *
 * External products cannot be bought; they link offsite. Extends simple products.
 *
 * @class 		WC_Product_External
 * @version		2.0.0
 * @package		WooCommerce/Classes/Products
 * @author 		WooThemes
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Product_External extends WC_Product_Simple {

	/** @var string URL to external product. */
	public $product_url;

	/** @var string Text for the buy/link button. */
	public $button_text;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $product
	 * @param array $args Contains arguments to set up this product
	 */
	public function __construct( $product, $args ) {

		parent::__construct( $product, $args );

		$this->product_type = 'external';
		$this->downloadable = 'no';
		$this->virtual      = 'no';
		$this->stock        = '';
		$this->stock_status = 'instock';
		$this->manage_stock = 'no';
		$this->weight       = '';
		$this->length       = '';
		$this->width        = '';
		$this->height       = '';

		$this->load_product_data( array(
			'product_url' => '',
			'button_text' => 'no'
		) );
	}

	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @access public
	 * @return cool
	 */
	public function is_purchasable() {
		return apply_filters( 'woocommerce_is_purchasable', false, $this );
	}

	/**
	 * get_product_url function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_product_url() {
		return $this->product_url;
	}

	/**
	 * get_button_text function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_button_text() {
		return $this->button_text ? $this->button_text : __( 'Buy product', 'woocommerce' );
	}
}