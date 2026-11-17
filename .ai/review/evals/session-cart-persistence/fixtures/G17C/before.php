<?php
function read_cart_session( WC_Session $session ) {
    $data = $session->get_session_data();
    return array( 'cart' => $data['cart'] ?? array(), 'coupons' => $data['coupons'] ?? array() );
}
class Export_Custom_Session extends WC_Session {
    public function init() {}
}
$session = new Export_Custom_Session();
$session->set( 'cart', array( 'item-hash' => array( 'product_id' => 42, 'quantity' => 1 ) ) );
$cart = read_cart_session( $session );
