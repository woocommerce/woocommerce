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
	 * Constructor.
	 */
    public function __construct( $item = 0 ) {
        $this->set_order_item_type( 'coupon' );
        parent::__construct( $item );
    }

    public function set_coupon_code( $value ) {
        $this->set_order_item_name( $value );
    }

    public function set_discount_amount( $value ) {
        $item->add_meta_data( 'discount_amount', wc_format_decimal( $value ) );
    }

    public function set_discount_amount_tax( $value ) {
        $item->add_meta_data( 'discount_amount_tax', wc_format_decimal( $value ) );
    }

}
