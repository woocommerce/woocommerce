<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Cart_Item
 *
 * Class object which represents an item in the cart.
 *
 * @version  3.2.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   Automattic
 */
class WC_Cart_Item implements ArrayAccess {

	/**
	 * Cart Data array.
	 *
	 * @var array
	 */
	protected $data = array(
		'product_id' => 0,
		'quantity'   => 0,
		'variation'  => array(),
	);

	/**
	 * Product this item represents.
	 *
	 * @var WC_Product
	 */
	protected $product = null;

	/**
	 * Constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data = array() ) {
		$this->set_data( $data );
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
	 * Gets price of the product.
	 * @return float
	 */
	public function get_tax_class() {
		return $this->get_product() ? $this->get_product()->get_tax_class() : '';
	}

	/**
	 * Set product.
	 * @param int $value
	 */
	public function set_product( $value ) {
		$this->product = $value;
		$this->data['product_id'] = is_callable( array( $this->product, 'get_variation_id' ) ) ? $this->product->get_variation_id() : $this->product->get_id();
	}

	/**
	 * Get product object.
	 *
	 * @return WC_Product
	 */
	public function get_product() {
		return ! is_null( $this->product ) ? $this->product : ( $this->product = wc_get_product( $this->get_product_id() ) );
	}

	/**
	 * Get all item data.
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Product or variation ID this item represents.
	 * @return int
	 */
	public function get_product_id() {
		return $this->data['product_id'];
	}

	/**
	 * Get quantity in cart.
	 * @return int
	 */
	public function get_quantity() {
		return $this->data['quantity'];
	}

	/**
	 * Get variation data.
	 * @return array
	 */
	public function get_variation() {
		return $this->data['variation'];
	}

	/**
	 * Set product ID.
	 * @param int $value
	 */
	public function set_product_id( $value ) {
		$this->data['product_id'] = absint( $value );
		$this->product = null;
	}

	/**
	 * Set Quantity.
	 * @param int $value
	 */
	public function set_quantity( $value ) {
		$this->data['quantity'] = wc_stock_amount( $value );
	}

	/**
	 * Set variation data.
	 * @param array $value
	 */
	public function set_variation( $value ) {
		$this->data['variation'] = (array) $value;
	}

	/**
	 * Set all data.
	 * @param array $value
	 */
	public function set_data( $values ) {
		if ( is_a( $values, 'WC_Cart_Item' ) ) {
			$values = $values->get_data();
		}
		foreach ( $values as $key => $value ) {
			if ( in_array( $key, array( 'quantity', 'product_id', 'variation', 'product' ) ) ) {
				$this->{ "set_$key" }( $value );
			} else {
				$this->data[ $key ] = $value;
			}
		}
	}

	/**
	 * ArrayAccess/Backwards compatibility.
	 *
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		switch ( $offset ) {
			case 'data' :
				return $this->get_product();
			case 'variation_id' :
				return is_callable( array( $this, 'get_variation_id' ) ) ? $this->get_product()->get_variation_id() : 0;
		}
		return isset( $this->data[ $offset ] ) ? $this->data[ $offset ] : '';
	}

	/**
	 * ArrayAccess/Backwards compatibility.
	 *
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		switch ( $offset ) {
			case 'data' :
				$this->set_product( $value );
				break;
			case 'variation_id' :
				$this->set_product( wc_get_product( $value ) );
				break;
			default :
				$this->data[ $offset ] = $value;
				break;
		}
	}

	/**
	 * ArrayAccess/Backwards compatibility.
	 *
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
	 * ArrayAccess/Backwards compatibility.
	 *
	 * @param string $offset
	 */
	public function offsetUnset( $offset ) {
		unset( $this->data[ $offset ] );
	}
}
