<?php
/* Scenario context: Synthetic production fixture. Released action passes integer order ID and unprefixed status string. Export extensions compare the second argument with pending. Caller passes pending. */
function wc_notify_status( $id, $status ) {
    do_action( 'woocommerce_order_export_status', $id, $status );
}
