<?php
/**
 * Order Line Item (coupon).
 *
 * @class 		WC_Order_Item_Coupon
 * @version		2.6.0
 * @since       2.6.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Order_Item_Coupon extends WC_Order_Item {

    /**
	 * Data properties of this order item object.
	 * @since 2.6.0
	 * @var array
	 */
    protected $_data = array(
        'order_id'      => 0,
		'order_item_id' => 0,
        'code'          => '',
        'discount'      => 0,
        'discount_tax'  => 0,
    );

    /**
     * offsetGet for ArrayAccess/Backwards compatibility.
     * @todo Add deprecation notices in future release.
     * @param string $offset
     * @return mixed
     */
    public function offsetGet( $offset ) {
        if ( 'discount_amount' === $offset ) {
            $offset = 'discount';
        }
        elseif ( 'discount_amount_tax' === $offset ) {
            $offset = 'discount_tax';
        }
        return parent::offsetGet( $offset );
    }

    /**
     * offsetExists for ArrayAccess
     * @param string $offset
     * @return bool
     */
    public function offsetExists( $offset ) {
        if ( in_array( $offset, array( 'discount_amount', 'discount_amount_tax' ) ) ) {
            return true;
        }
        return parent::offsetExists( $offset );
    }

    /**
     * Read/populate data properties specific to this order item.
     */
    public function read( $id ) {
        parent::read( $id );
        if ( $this->get_order_item_id() ) {
            $this->set_discount( get_metadata( 'order_item', $this->get_order_item_id(), 'discount_amount', true ) );
            $this->set_discount_tax( get_metadata( 'order_item', $this->get_order_item_id(), 'discount_amount_tax', true ) );
        }
    }

    /**
     * Save properties specific to this order item.
     */
    public function save() {
        parent::save();
        if ( $this->get_order_item_id() ) {
            wc_update_order_item_meta( $this->get_order_item_id(), 'discount_amount', $this->get_discount() );
            wc_update_order_item_meta( $this->get_order_item_id(), 'discount_amount_tax', $this->get_discount_tax() );
        }
    }

    /**
     * Internal meta keys we don't want exposed as part of meta_data.
     * @return array()
     */
    protected function get_internal_meta_keys() {
        return array( 'discount_amount', 'discount_amount_tax' );
    }

    /*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

    /**
     * Set order item name.
     * @param string $value
     */
    public function set_name( $value ) {
        $this->set_code( $value );
    }

    /**
     * Set code.
     * @param string $value
     */
    public function set_code( $value ) {
        $this->_data['code'] = wc_clean( $value );
    }

    /**
     * Set discount amount.
     * @param string $value
     */
    public function set_discount( $value ) {
        $this->_data['discount'] =  wc_format_decimal( $value );
    }

    /**
     * Set discounted tax amount.
     * @param string $value
     */
    public function set_discount_tax( $value ) {
        $this->_data['discount_tax'] = wc_format_decimal( $value );
    }

    /*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

    /**
     * Get order item type.
     * @return string
     */
    public function get_type() {
        return 'coupon';
    }

    /**
     * Get order item name.
     * @return string
     */
    public function get_name() {
        return $this->get_code();
    }

    /**
     * Get coupon code.
     * @return string
     */
    public function get_code() {
        return $this->_data['code'];
    }

    /**
     * Get discount amount.
     * @return string
     */
    public function get_discount() {
        return wc_format_decimal( $this->_data['discount'] );
    }

    /**
     * Get discounted tax amount.
     * @return string
     */
    public function get_discount_tax() {
        return wc_format_decimal( $this->_data['discount_tax'] );
    }
}
