<?php
/* Scenario context: Synthetic production fixture. Order get_id returns positive integer. Queue called after AS init. Runner registration always loaded. Deleted orders are skipped. */
function wc_queue_order_export( $order ) {
    as_enqueue_async_action( 'woocommerce_export_order', array( (int) $order->get_id() ), 'wc-export' );
}
function wc_run_order_export( $id ) {
    $order = wc_get_order( $id );
    if ( ! $order ) { return; }
    wc_write_order_export( $order );
}
add_action( 'woocommerce_export_order', 'wc_run_order_export' );
