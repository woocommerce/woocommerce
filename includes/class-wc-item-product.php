<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Line Item.
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Item_Product extends WC_Item {

	/**
	 * Data array.
	 * @since 2.7.0
	 * @var array
	 */
	protected $data = array(
		'name'         => '',
		'product_id'   => 0,
		'variation_id' => 0,
		'quantity'     => 1,
	);

	/**
	 * Product this item represents.
	 * @var WC_Product
	 */
	protected $product = null;

	/**
	 * offsetGet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		switch ( $offset ) {
			case 'data' :
				return $this->get_product();
		}
		return isset( $this->data[ $offset ] ) ? $this->data[ $offset ] : '';
	}

	/**
	 * offsetSet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		switch ( $offset ) {
			case 'data' :
				$this->set_product( $value );
				break;
			default :
				$this->data[ $offset ] = $value;
				break;
		}
	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		if ( in_array( $offset, array( 'data' ) ) || isset( $this->data[ $offset ] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * offsetUnset for ArrayAccess
	 * @param string $offset
	 */
	public function offsetUnset( $offset ) {
		unset( $this->data[ $offset ] );
	}

	/**
	 * Get product object.
	 * @return WC_Product
	 */
	public function get_product() {
		return ! is_null( $this->product ) ? $this->product : ( $this->product = wc_get_product( $this->get_variation_id() ? $this->get_variation_id() : $this->get_product_id() ) );
	}

	/**
	 * Gets price of the product.
	 * @return float
	 */
	public function get_price() {
		return $this->get_product() ? $this->get_product()->get_price() : 0;
	}

	/**
	 * Gets price of the product.
	 * @return float
	 */
	public function get_weight() {
		return $this->get_product() ? $this->get_product()->get_weight() : 0;
	}

	/**
	 * Get tax status.
	 * @return string
	 */
	public function get_tax_status() {
		return $this->get_product() ? $this->get_product()->get_tax_status() : 'taxable';
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set order item name.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_name( $value ) {
		$this->data['name'] = wc_clean( $value );
	}

	/**
	 * Set quantity.
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_quantity( $value ) {
		if ( 0 >= $value ) {
			$this->error( 'line_item_product_invalid_quantity', __( 'Quantity must be positive', 'woocommerce' ) );
		}
		$this->data['quantity'] = wc_stock_amount( $value );
	}

	/**
	 * Set tax class.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_tax_class( $value ) {
		if ( $value && ! in_array( $value, WC_Tax::get_tax_classes() ) ) {
			$this->error( 'line_item_product_invalid_tax_class', __( 'Invalid tax class', 'woocommerce' ) );
		}
		$this->data['tax_class'] = $value;
	}

	/**
	 * Set Product ID
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_product_id( $value ) {
		if ( $value > 0 && 'product' !== get_post_type( absint( $value ) ) ) {
			$this->error( 'line_item_product_invalid_product_id', __( 'Invalid product ID', 'woocommerce' ) );
		}
		$this->data['product_id'] = absint( $value );
	}

	/**
	 * Set variation ID.
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_variation_id( $value ) {
		if ( $value > 0 && 'product_variation' !== get_post_type( $value ) ) {
			$this->error( 'line_item_product_invalid_variation_id', __( 'Invalid variation ID', 'woocommerce' ) );
		}
		$this->data['variation_id'] = absint( $value );
	}

	/**
	 * Set variation data (stored as meta data - write only).
	 * @param array $data Key/Value pairs
	 */
	public function set_variation( $data ) {
		foreach ( $data as $key => $value ) {
			$this->add_meta_data( str_replace( 'attribute_', '', $key ), $value, true );
		}
	}

	/**
	 * Set properties based on passed in product object.
	 * @param WC_Product $product
	 * @throws WC_Data_Exception
	 */
	public function set_product( $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$this->error( 'line_item_product_invalid_product', __( 'Invalid product', 'woocommerce' ) );
		}
		$this->product = $product;
		$this->set_product_id( $product->get_id() );
		$this->set_name( $product->get_title() );
		$this->set_tax_class( $product->get_tax_class() );
		$this->set_variation_id( is_callable( array( $product, 'get_variation_id' ) ) ? $product->get_variation_id() : 0 );
		$this->set_variation( is_callable( array( $product, 'get_variation_attributes' ) ) ? $product->get_variation_attributes() : array() );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order item name.
	 * @return string
	 */
	public function get_name() {
		return $this->data['name'];
	}

	/**
	 * Get item type.
	 * @return string
	 */
	public function get_type() {
		return 'line_item';
	}

	/**
	 * Get product ID.
	 * @return int
	 */
	public function get_product_id() {
		return absint( $this->data['product_id'] );
	}

	/**
	 * Get variation ID.
	 * @return int
	 */
	public function get_variation_id() {
		return absint( $this->data['variation_id'] );
	}

	/**
	 * Get quantity.
	 * @return int
	 */
	public function get_quantity() {
		return wc_stock_amount( $this->data['quantity'] );
	}

	/**
	 * Get tax class.
	 * @return string
	 */
	public function get_tax_class() {
		return $this->data['tax_class'];
	}
}
