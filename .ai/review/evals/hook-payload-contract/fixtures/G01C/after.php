<?php
/* Scenario context: Synthetic production fixture. Released first two arguments are args array and order. Existing callbacks register accepted_args=2. */
function wc_export_args( $order ) {
    $args = array( 'id' => $order->get_id(), 'status' => $order->get_status() );
    return apply_filters( 'woocommerce_export_args', $args, $order, 'admin' );
}
