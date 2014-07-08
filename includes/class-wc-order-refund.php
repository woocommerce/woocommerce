<?php
/**
 * Order refund
 *
 * @class    WC_Order
 * @version  2.2.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */

class WC_Order_Refund extends WC_Abstract_Order {

	/**
	 * Initialize the order refund.
	 *
	 * @param int|WC_Order $order
	 */
	public function __construct( $order ) {
		$this->order_type = 'refund';

		parent::__construct( $order );
	}
}
