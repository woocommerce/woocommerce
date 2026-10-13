<?php
/* Scenario context: Cart keys are nonnumeric hashes; values are cart item arrays. Released login merge retains saved-only items, retains guest-only items and guest replaces saved on key collision. set is on abstract session contract. */
function load_cart_after_login( $guest_cart, $saved_cart, $session ) {
    $merged = array_replace( $saved_cart, $guest_cart );
    $session->set( 'cart', $merged );
    return $merged;
}
