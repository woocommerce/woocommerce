<?php

/**
 * Order
 *
 * @class 		WC_Order
 * @version		2.1.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */

class WC_Order extends WC_Abstract_Order {
	public function __construct( $order ) {
		$this->order_type = 'simple';
		parent::__construct( $order );
	}
}