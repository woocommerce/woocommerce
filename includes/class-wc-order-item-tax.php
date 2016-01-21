<?php
/**
 * Order Line Item (tax).
 *
 * @class 		WC_Order_Item_Tax
 * @version		2.6.0
 * @since       2.6.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Order_Item_Tax extends WC_Order_Item {

    /**
	 * Data properties of this order item object.
	 * @since 2.6.0
	 * @var array
	 */
    protected $data = array(
        'order_id'           => 0,
		'order_item_id'      => 0,
        'rate_code'          => '',
        'rate_id'            => 0,
        'label'              => '',
        'compound'           => false,
        'tax_total'          => 0,
        'shipping_tax_total' => 0
    );

    /**
     * Read/populate data properties specific to this order item.
     */
    protected function read( $id ) {
        parent::read( $id );
        if ( $this->get_order_item_id() ) {
            $this->set_rate_id( get_metadata( 'order_item', $this->get_order_item_id(), 'rate_id', true ) );
            $this->set_label( get_metadata( 'order_item', $this->get_order_item_id(), 'label', true ) );
            $this->set_compound( get_metadata( 'order_item', $this->get_order_item_id(), 'compound', true ) );
            $this->set_tax_total( get_metadata( 'order_item', $this->get_order_item_id(), 'tax_amount', true ) );
            $this->set_shipping_tax_total( get_metadata( 'order_item', $this->get_order_item_id(), 'shipping_tax_amount', true ) );
        }
    }

    /**
     * Save properties specific to this order item.
     */
    protected function save() {
        parent::save();
        if ( $this->get_order_item_id() ) {
            wc_update_order_item_meta( $this->get_order_item_id(), 'rate_id', $this->get_rate_id() );
            wc_update_order_item_meta( $this->get_order_item_id(), 'label', $this->get_label() );
            wc_update_order_item_meta( $this->get_order_item_id(), 'compound', $this->get_compound() );
            wc_update_order_item_meta( $this->get_order_item_id(), 'tax_amount', $this->get_tax_amount() );
            wc_update_order_item_meta( $this->get_order_item_id(), 'shipping_tax_amount', $this->get_shipping_tax_amount() );
        }
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
        $this->data['rate_code'] = wc_clean( $value );
    }

    /**
     * Set item name.
     * @param string $value
     */
    public function set_rate_code( $value ) {
        $this->set_name( $value );
    }

    /**
     * Set tax rate id.
     * @param int $value
     */
    public function set_rate_id( $value ) {
        $this->data['rate_id'] = absint( $value );
    }

    /**
     * Set tax_amount.
     * @param string $value
     */
    public function set_tax_amount( $value ) {
        $this->data['tax_amount'] = wc_format_decimal( $value );
    }

    /**
     * Set shipping_tax_amount
     * @param string $value
     */
    public function set_shipping_tax_amount( $value ) {
        $this->data['shipping_tax_amount'] = wc_format_decimal( $value );
    }

    /**
     * Set compound
     * @param bool $value
     */
    public function set_compound( $value ) {
        $this->data['compound'] = (bool) $value;
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
        return 'tax';
    }

    /**
     * Get fee name.
     * @return string
     */
    public function get_rate_code() {
        return $this->get_name();
    }

    /**
     * Get tax rate ID.
     * @return int
     */
    public function get_rate_id() {
        return absint( $this->data['rate_id'] );
    }

    /**
     * Get tax_amount
     * @return string
     */
    public function get_tax_amount() {
        return wc_format_decimal( $this->data['tax_amount'] );
    }

    /**
     * Get shipping_tax_amount
     * @return string
     */
    public function get_shipping_tax_amount() {
        return wc_format_decimal( $this->data['shipping_tax_amount'] );
    }

    /**
     * Get compound.
     * @return bool
     */
    public function get_compound() {
        return (bool) $this->data['compound'];
    }
}
