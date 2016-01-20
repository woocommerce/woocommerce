<?php
/**
 * Order Line Item (shipping).
 *
 * @class 		WC_Order_Item_Shipping
 * @version		2.6.0
 * @since       2.6.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Order_Item_Shipping extends WC_Order_Item {

    /**
	 * Constructor.
	 */
    public function __construct( $item = 0 ) {
        $this->set_order_item_type( 'shipping' );
        parent::__construct( $item );
    }

    /**
     * Set shipping method title.
     * @param string $value
     */
    public function set_method_title( $value ) {
        $this->set_order_item_name( $value );
    }

    /**
     * Set shipping method id.
     * @param string $value
     */
    public function set_method_id( $value ) {
        $item->add_meta_data( 'method_id', $value );
    }

    /**
     * Set shipping cost.
     * @param string $value
     */
    public function set_cost( $value ) {
        $item->add_meta_data( 'cost', wc_format_decimal( $value ) );
    }

    /**
     * Set shipping taxes.
     *
     * This is an array of tax ID keys with total amount values.
     * @param array $value
     */
    public function set_taxes( $value ) {
        $item->add_meta_data( 'taxes', array_map( 'wc_format_decimal', $value ) );
    }

    /**
     * Get method title.
     * @return string
     */
    public function get_method_title() {
        return $this->get_order_item_name();
    }

    /**
     * Get method ID.
     * @return string
     */
    public function get_method_id() {
        return $this->meta_data['method_id'];
    }

    /**
     * Get shipping cost.
     * @return string
     */
    public function get_cost() {
        return wc_format_decimal( $this->meta_data['cost'] );
    }

    /**
     * Get shipping taxes.
     * @return array
     */
    public function get_taxes() {
        return $this->meta_data['taxes'];
    }
}
