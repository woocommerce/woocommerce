<?php
/* Scenario context: All supported session handlers implement get(key,default) and may observe call order. Both cart and coupons default to empty arrays. */
function read_cart_session( $session ) {
    return array( 'cart' => $session->get( 'cart', array() ), 'coupons' => $session->get( 'coupons', array() ) );
}
