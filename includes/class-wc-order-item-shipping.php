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
	 * Data properties of this order item object.
	 * @since 2.6.0
	 * @var array
	 */
    protected $data = array(
        'order_id'      => 0,
		'order_item_id' => 0,
        'name'          => '',
        'method_id'     => '',
        'total'         => 0,
        'total_tax'     => 0,
        'taxes'         => array(
            'total' => array()
        )
    );

    /**
     * Read/populate data properties specific to this order item.
     */
    protected function read( $id ) {
        parent::read( $id );
        if ( $this->get_order_item_id() ) {
            $this->set_method_id( get_metadata( 'order_item', $this->get_order_item_id(), 'method_id', true ) );
            $this->set_total( get_metadata( 'order_item', $this->get_order_item_id(), 'cost', true ) );
            $this->set_total_tax( get_metadata( 'order_item', $this->get_order_item_id(), 'total_tax', true ) );
            $this->set_taxes( get_metadata( 'order_item', $this->get_order_item_id(), 'taxes', true ) );
        }
    }

    /**
     * Save properties specific to this order item.
     */
    protected function save() {
        parent::save();
        if ( $this->get_order_item_id() ) {
            wc_update_order_item_meta( $this->get_order_item_id(), 'method_id', $this->get_method_id() );
            wc_update_order_item_meta( $this->get_order_item_id(), 'cost', $this->get_total() );
            wc_update_order_item_meta( $this->get_order_item_id(), 'total_tax', $this->get_total_tax() );
            wc_update_order_item_meta( $this->get_order_item_id(), 'taxes', $this->get_taxes() );
        }
    }

    /*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

    /**
     * Set shipping method id.
     * @param string $value
     */
    public function set_method_id( $value ) {
        $this->data['method_id'] = wc_clean( $value );
    }

    /**
     * Set total.
     * @param string $value
     */
    public function set_total( $value ) {
        $this->data['total'] = wc_format_decimal( $value );
    }

    /**
     * Set total tax.
     * @param string $value
     */
    public function set_total_tax( $value ) {
        $this->data['total_tax'] = wc_format_decimal( $value );
    }

    /**
     * Set taxes.
     *
     * This is an array of tax ID keys with total amount values.
     * @param array $raw_tax_data
     */
    public function set_taxes( $raw_tax_data ) {
        $raw_tax_data = maybe_unserialize( $raw_tax_data );
        $tax_data     = array(
            'total'    => array()
        );
        if ( ! empty( $raw_tax_data['total'] ) ) {
            $tax_data['total']    = array_map( 'wc_format_decimal', $raw_tax_data['total'] );
        }
        $this->data['taxes'] = $tax_data;
        $this->set_total_tax( array_sum( $tax_data['total'] ) );
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
        return 'shipping';
    }

    /**
     * Get method ID.
     * @return string
     */
    public function get_method_id() {
        return $this->meta_data['method_id'];
    }

    /**
     * Get total cost.
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
     * Get taxes.
     * @return array
     */
    public function get_taxes() {
        return $this->data['taxes'];
    }
}
