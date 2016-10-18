<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * External Product Class.
 *
 * External products cannot be bought; they link offsite. Extends simple products.
 *
 * @class 		WC_Product_External
 * @version		2.7.0
 * @package		WooCommerce/Classes/Products
 * @category	Class
 * @author 		WooThemes
 */
class WC_Product_External extends WC_Product {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'product_url' => '',
		'button_text' => '',
	);

	/**
	 * Merges external product data into the parent object.
	 * @param int|WC_Product|object $product Product to init.
	 */
	public function __construct( $product = 0 ) {
		$this->data = array_merge( $this->data, $this->extra_data );
		parent::__construct( $product );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the product object.
	*/

	/**
	 * Get internal type.
	 * @return string
	 */
	public function get_type() {
		return 'external';
	}

	/**
	 * Get product url.
	 *
	 * @return string
	 */
	public function get_product_url() {
		return esc_url( $this->data['product_url'] );
	}

	/**
	 * Get button text.
	 *
	 * @return string
	 */
	public function get_button_text() {
		return $this->data['button_text'] ? $this->data['button_text'] : __( 'Buy product', 'woocommerce' );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting product data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/

	/**
	 * Set product URL.
	 *
	 * @since 2.7.0
	 * @param string $product_url Product URL.
	 */
	public function set_product_url( $product_url ) {
		$this->data['product_url'] = $product_url;
	}

	/**
	 * Set button text.
	 *
	 * @since 2.7.0
	 * @param string $button_text Button text.
	 */
	public function set_button_text( $button_text ) {
		$this->data['button_text'] = $button_text;
	}

	/*
	|--------------------------------------------------------------------------
	| Other Actions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @access public
	 * @return bool
	 */
	public function is_purchasable() {
		return apply_filters( 'woocommerce_is_purchasable', false, $this );
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @access public
	 * @return string
	 */
	public function add_to_cart_url() {
		return apply_filters( 'woocommerce_product_add_to_cart_url', $this->get_product_url(), $this );
	}

	/**
	 * Get the add to cart button text for the single page.
	 *
	 * @access public
	 * @return string
	 */
	public function single_add_to_cart_text() {
		return apply_filters( 'woocommerce_product_single_add_to_cart_text', $this->get_button_text(), $this );
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @access public
	 * @return string
	 */
	public function add_to_cart_text() {
		return apply_filters( 'woocommerce_product_single_add_to_cart_text', $this->get_button_text(), $this );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Reads a product from the database and sets its data to the class.
	 *
	 * @since 2.7.0
	 * @param int $id Product ID.
	 */
	public function read( $id ) {
		parent::read( $id );
		$this->set_props( array(
			'product_url' => get_post_meta( $id, '_product_url', true ),
			'button_text' => get_post_meta( $id, '_button_text', true ),
		) );
		do_action( 'woocommerce_product_loaded', $this );
		do_action( 'woocommerce_product_' . $this->get_type() . '_loaded', $this );
	}

	/**
	 * Helper method that updates all the post meta for an external product.
	 *
	 * @since 2.7.0
	 */
	protected function update_post_meta() {
		parent::update_post_meta();
		update_post_meta( $this->get_id(), '_product_url', $this->get_product_url() );
		update_post_meta( $this->get_id(), '_button_text', $this->get_button_text() );
	}
}
