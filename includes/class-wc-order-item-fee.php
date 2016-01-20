<?php
/**
 * Order Line Item (fee).
 *
 * @class 		WC_Order_Item_Fee
 * @version		2.6.0
 * @since       2.6.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Order_Item_Fee extends WC_Order_Item {

    /**
	 * Constructor.
	 */
    public function __construct( $item = 0 ) {
        $this->set_order_item_type( 'fee' );
        parent::__construct( $item );
    }

    /**
     * Set item name.
     * @param string $value
     */
    public function set_name( $value ) {
        $this->set_order_item_name( $value );
    }

    /**
     * Set tax class.
     * @param string $value
     */
    public function set_tax_class( $value ) {
        $this->meta_data['_tax_class'] = $value;
    }

    /**
     * Set total.
     * @param string $value
     */
    public function set_total( $value ) {
        $this->meta_data['_line_total'] = $value;
    }

    /**
     * Set total tax.
     * @param string $value
     */
    public function set_total_tax( $value ) {
        $this->meta_data['_line_tax'] = $value;
    }

    /**
     * Set taxes.
     *
     * This is an array of tax ID keys with total amount values.
     * @param array $raw_tax_data
     */
    public function set_taxes( $raw_tax_data ) {
        $tax_data = array(
            'total'    => array()
        );
        if ( ! empty( $raw_tax_data['total'] ) ) {
            $tax_data['total']    = array_map( 'wc_format_decimal', $raw_tax_data['total'] );
        }
        $this->meta_data['_line_tax_data'] = $tax_data;
    }

    /**
     * Get fee name.
     * @return string
     */
    public function get_name() {
        return $this->get_order_item_name();
    }

    /**
     * Get tax class.
     * @return string
     */
    public function get_tax_class() {
        return $this->meta_data['_tax_class'];
    }

    /**
     * Get total fee.
     * @return string
     */
    public function get_total() {
        return $this->meta_data['_line_total'];
    }

    /**
     * Get total tax.
     * @return string
     */
    public function get_total_tax() {
        return $this->meta_data['_line_tax'];
    }

    /**
     * Get fee taxes.
     * @return array
     */
    public function get_taxes() {
        return $this->meta_data['_line_tax_data'];
    }
}
