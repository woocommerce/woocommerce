<?php
/* Cart keys are product/variation hashes. Guest items take precedence on a matching key. */
function load_cart_after_login( $guest_cart, $saved_cart, $session ) {
    $merged = $saved_cart;
    foreach ( $guest_cart as $key => $item ) { $merged[ $key ] = $item; }
    $session->set( 'cart', $merged );
    return $merged;
}
