<?php
/**
 * Order Line Item (product).
 *
 * @class 		WC_Order_Item_Product
 * @version		2.6.0
 * @since       2.6.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Order_Item_Product extends WC_Order_Item {

    /**
	 * Constructor.
	 */
    public function __construct( $item = 0 ) {
        $this->set_order_item_type( 'line_item' );
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
     * Set qty.
     * @param int $value
     */
    public function set_qty( $value ) {
        $this->meta_data['_qty'] = wc_stock_amount( $value );
    }

    /**
     * Set tax class.
     * @param string $value
     */
    public function set_tax_class( $value ) {
        $this->meta_data['_tax_class'] = $value;
    }

    /**
     * Set Product ID
     * @param int $value
     */
    public function set_product_id( $value ) {
        $this->meta_data['_product_id'] = absint( $value );
    }

    /**
     * Set variation ID.
     * @param int $value
     */
    public function set_variation_id( $value ) {
        $this->meta_data['_variation_id'] = absint( $value );
    }

    /**
     * Line subtotal (before discounts).
     * @param string $value
     */
    public function set_line_subtotal( $value ) {
        $this->meta_data['_line_subtotal'] = wc_format_decimal( $value );
    }

    /**
     * Line total (after discounts).
     * @param string $value
     */
    public function set_line_total( $value ) {
        $this->meta_data['_line_total'] = wc_format_decimal( $value );
    }

    /**
     * Line subtotal tax (before discounts).
     * @param string $value
     */
    public function set_line_subtotal_tax( $value ) {
        $this->meta_data['_line_subtotal_tax'] = wc_format_decimal( $value );
    }

    /**
     * Line total tax (after discounts).
     * @param string $value
     */
    public function set_line_tax( $value ) {
        $this->meta_data['_line_tax'] = wc_format_decimal( $value );
    }

    /**
     * Set line taxes.
     * @param array $raw_tax_data
     */
    public function set_line_tax_data( $raw_tax_data ) {
        $tax_data = array(
            'total'    => array(),
            'subtotal' => array()
        );
        if ( ! empty( $raw_tax_data['total'] ) && ! empty( $raw_tax_data['subtotal'] ) ) {
            $tax_data['total']    = array_map( 'wc_format_decimal', $raw_tax_data['total'] );
            $tax_data['subtotal'] = array_map( 'wc_format_decimal', $raw_tax_data['subtotal'] );
        }
        $this->meta_data['_line_tax_data'] = $tax_data;
    }
}
