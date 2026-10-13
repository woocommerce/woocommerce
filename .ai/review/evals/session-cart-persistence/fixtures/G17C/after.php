<?php
/* Scenario context: All supported session handlers implement get(key,default) and may observe call order. Both cart and coupons default to empty arrays. */
function read_cart_session( $session ) {
    $data = array();
    foreach ( array( 'cart', 'coupons' ) as $key ) {
        $data[ $key ] = $session->get( $key, array() );
    }
    return $data;
}
