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
	 * Data properties of this order item object.
	 * @since 2.6.0
	 * @var array
	 */
    protected $data = array(
        'order_id'      => 0,
		'order_item_id' => 0,
        'name'          => '',
        'product_id'    => 0,
        'variation_id'  => 0,
        'qty'           => 0,
        'tax_class'     => '',
        'subtotal'      => 0,
        'subtotal_tax'  => 0,
        'total'         => 0,
        'total_tax'     => 0,
        'taxes'         => array(
            'subtotal' => array(),
            'total'    => array()
        ),
        'meta_data'     => array(),
    );

    /**
     * Read/populate data properties specific to this order item.
     */
    protected function read( $id ) {
        parent::read( $id );
        if ( $this->get_order_item_id() ) {
            $this->set_product_id( get_metadata( 'order_item', $this->get_order_item_id(), '_product_id', true ) );
            $this->set_variation_id( get_metadata( 'order_item', $this->get_order_item_id(), '_variation_id', true ) );
            $this->set_qty( get_metadata( 'order_item', $this->get_order_item_id(), '_qty', true ) );
            $this->set_tax_class( get_metadata( 'order_item', $this->get_order_item_id(), '_tax_class', true ) );
            $this->set_subtotal( get_metadata( 'order_item', $this->get_order_item_id(), '_line_subtotal', true ) );
            $this->set_subtotal_tax( get_metadata( 'order_item', $this->get_order_item_id(), '_line_subtotal_tax', true ) );
            $this->set_total( get_metadata( 'order_item', $this->get_order_item_id(), '_line_total', true ) );
            $this->set_total_tax( get_metadata( 'order_item', $this->get_order_item_id(), '_line_tax', true ) );
            $this->set_taxes( get_metadata( 'order_item', $this->get_order_item_id(), '_line_tax_data', true ) );
            $this->set_meta_data( $this->get_all_item_meta_data() );
        }
    }

    /**
     * Save properties specific to this order item.
     */
    protected function save() {
        parent::save();
        if ( $this->get_order_item_id() ) {
            wc_update_order_item_meta( $this->get_order_item_id(), '_product_id', $this->get_product_id() );
            wc_update_order_item_meta( $this->get_order_item_id(), '_variation_id', $this->get_variation_id() );
            wc_update_order_item_meta( $this->get_order_item_id(), '_qty', $this->get_qty() );
            wc_update_order_item_meta( $this->get_order_item_id(), '_tax_class', $this->get_tax_class() );
            wc_update_order_item_meta( $this->get_order_item_id(), '_line_subtotal', $this->get_subtotal() );
            wc_update_order_item_meta( $this->get_order_item_id(), '_line_subtotal_tax', $this->get_subtotal_tax() );
            wc_update_order_item_meta( $this->get_order_item_id(), '_line_total', $this->get_total() );
            wc_update_order_item_meta( $this->get_order_item_id(), '_line_tax', $this->get_total_tax() );
            wc_update_order_item_meta( $this->get_order_item_id(), '_line_tax_data', $this->get_taxes() );
        }
    }

    /**
     * Get the associated product.
     * @return WC_Product|bool
     */
    public function get_product() {
        if ( $this->get_variation_id() ) {
			return wc_get_product( $this->get_variation_id() );
		} else {
			return wc_get_product( $this->get_product_id() );
		}
    }

    /*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

    /**
     * Set qty.
     * @param int $value
     */
    public function set_qty( $value ) {
        $this->data['qty'] = wc_stock_amount( $value );
    }

    /**
     * Set tax class.
     * @param string $value
     */
    public function set_tax_class( $value ) {
        $this->data['tax_class'] = $value;
    }

    /**
     * Set Product ID
     * @param int $value
     */
    public function set_product_id( $value ) {
        $this->data['product_id'] = absint( $value );
    }

    /**
     * Set variation ID.
     * @param int $value
     */
    public function set_variation_id( $value ) {
        $this->data['variation_id'] = absint( $value );
    }

    /**
     * Line subtotal (before discounts).
     * @param string $value
     */
    public function set_subtotal( $value ) {
        $this->data['subtotal'] = wc_format_decimal( $value );
    }

    /**
     * Line total (after discounts).
     * @param string $value
     */
    public function set_total( $value ) {
        $this->data['total'] = wc_format_decimal( $value );
    }

    /**
     * Line subtotal tax (before discounts).
     * @param string $value
     */
    public function set_subtotal_tax( $value ) {
        $this->data['subtotal_tax'] = wc_format_decimal( $value );
    }

    /**
     * Line total tax (after discounts).
     * @param string $value
     */
    public function set_total_tax( $value ) {
        $this->data['total_tax'] = wc_format_decimal( $value );
    }

    /**
     * Set line taxes.
     * @param array $raw_tax_data
     */
    public function set_taxes( $raw_tax_data ) {
        $tax_data = array(
            'total'    => array(),
            'subtotal' => array()
        );
        if ( ! empty( $raw_tax_data['total'] ) && ! empty( $raw_tax_data['subtotal'] ) ) {
            $tax_data['total']    = array_map( 'wc_format_decimal', $raw_tax_data['total'] );
            $tax_data['subtotal'] = array_map( 'wc_format_decimal', $raw_tax_data['subtotal'] );
        }
        $this->data['taxes'] = $tax_data;
    }

    /**
     * Set variation data (stored as meta data - write only).
     * @param array $data Key/Value pairs
     */
    public function set_variations( $data ) {
        foreach ( $data as $key => $value ) {
            $this->data['meta_data'][ str_replace( 'attribute_', '', $key ) ] = $value;
        }
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
     * Get qty.
     * @return int
     */
    public function get_qty() {
        return wc_stock_amount( $this->data['qty'] );
    }

    /**
     * Get tax class.
     * @return string
     */
    public function get_tax_class() {
        return $this->data['tax_class'];
    }

    /**
     * Get subtotal.
     * @return string
     */
    public function get_subtotal() {
        return wc_format_decimal( $this->data['subtotal'] );
    }

    /**
     * Get subtotal tax.
     * @return string
     */
    public function get_subtotal_tax() {
        return wc_format_decimal( $this->data['subtotal_tax'] );
    }

    /**
     * Get total.
     * @return string
     */
    public function get_total() {
        return wc_format_decimal( $this->data['total'] );
    }

    /**
     * Get total tax.
     * @return string
     */
    public function get_total_tax() {
        return wc_format_decimal( $this->data['total_tax'] );
    }

    /**
     * Get fee taxes.
     * @return array
     */
    public function get_taxes() {
        return $this->data['taxes'];
    }

    /**
     * Get meta data.
     * @return array of key/value pairs
     */
    public function get_meta_data() {
        return $this->data['meta_data'];
    }
}
