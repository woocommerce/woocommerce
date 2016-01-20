<?php
/**
 * Order Line Item (fee).
 *
 * @class 		WC_Order_Item_Shipping
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

}
