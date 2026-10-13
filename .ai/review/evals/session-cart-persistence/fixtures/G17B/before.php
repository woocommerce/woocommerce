<?php
/* Checkout session adapter. */
function read_cart_session( $session ) {
    return array( 'cart' => $session->get( 'cart', array() ) );
}

class Export_Custom_Session extends WC_Session {
    public function init() {}
    public function get_customer_id() { return 'export-customer'; }
}
$cart = read_cart_session( new Export_Custom_Session() );
