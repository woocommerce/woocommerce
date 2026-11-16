<?php
function read_cart_session( WC_Session $session ) {
    return array( 'cart' => $session->get( 'cart', array() ), 'coupons' => $session->get( 'coupons', array() ) );
}
class Export_Custom_Session extends WC_Session {
    public function init() {}
}
$session = new Export_Custom_Session();
$session->set( 'cart', array( 'item-hash' => array( 'product_id' => 42, 'quantity' => 1 ) ) );
$cart = read_cart_session( $session );
