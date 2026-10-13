<?php
/* Scenario context: session may be any supported WC_Session subclass. The abstract contract includes get(key,default) but not get_session_data; custom handlers implementing only the abstract contract are supported in checkout. Default handler has get_session_data. */
function read_cart_session( $session ) {
    return array( 'cart' => $session->get( 'cart', array() ) );
}
