<?php

class WC_Order_Simple extends WC_Order {
	public function __construct( $order ) {
		$this->order_type = 'simple';
		parent::__construct( $order );
	}
}