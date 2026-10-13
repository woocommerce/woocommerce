<?php
/* Cart keys are product/variation hashes. Guest items take precedence on a matching key. */
function load_cart_after_login( $guest_cart, $saved_cart, $session ) {
    $merged = array_replace( $saved_cart, $guest_cart );
    $session->set( 'cart', $merged );
    return $merged;
}
