<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart Item.
 *
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart_Item implements ArrayAccess {

	/**
	 * Cart Data array.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_data = array(
		'product_id' => 0,
		'quantity'   => 0,
		'variation'  => array(),
	);

	/**
	 * Product this item represents.
	 * @var WC_Product
	 */
	protected $_product = null;

	/**
	 * Constructor.
	 * @param array $data
	 */
	public function __construct( $data = array() ) {
		$this->set_data( $data );
	}

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
			case 'variation_id' :
				return is_callable( array( $this, 'get_variation_id' ) ) ? $this->get_product()->get_variation_id() : 0;
		}
		return isset( $this->_data[ $offset ] ) ? $this->_data[ $offset ] : '';
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
			case 'variation_id' :
				$this->set_product( wc_get_product( $value ) );
				break;
			default :
				$this->_data[ $offset ] = $value;
				break;
		}
	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		if ( in_array( $offset, array( 'data' ) ) || isset( $this->_data[ $offset ] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * offsetUnset for ArrayAccess
	 * @param string $offset
	 */
	public function offsetUnset( $offset ) {
		unset( $this->_data[ $offset ] );
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
		$this->_product = $value;
		$this->_data['product_id'] = is_callable( array( $this->_product, 'get_variation_id' ) ) ? $this->_product->get_variation_id() : $this->_product->get_id();
	}

	/**
	 * Get product object.
	 * @return WC_Product
	 */
	public function get_product() {
		return ! is_null( $this->_product ) ? $this->_product : ( $this->_product = wc_get_product( $this->get_product_id() ) );
	}

	/**
	 * Get all item data.
	 * @return array
	 */
	public function get_data() {
		return $this->_data;
	}

	/**
	 * Product or variation ID this item represents.
	 * @return int
	 */
	public function get_product_id() {
		return $this->_data['product_id'];
	}

	/**
	 * Get quantity in cart.
	 * @return int
	 */
	public function get_quantity() {
		return $this->_data['quantity'];
	}

	/**
	 * Get variation data.
	 * @return array
	 */
	public function get_variation() {
		return $this->_data['variation'];
	}

	/**
	 * Set product ID.
	 * @param int $value
	 */
	public function set_product_id( $value ) {
		$this->_data['product_id'] = absint( $value );
		$this->_product = null;
	}

	/**
	 * Set Quantity.
	 * @param int $value
	 */
	public function set_quantity( $value ) {
		$this->_data['quantity'] = wc_stock_amount( $value );
	}

	/**
	 * Set variation data.
	 * @param array $value
	 */
	public function set_variation( $value ) {
		$this->_data['variation'] = (array) $value;
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
				$this->_data[ $key ] = $value;
			}
		}
	}
}
