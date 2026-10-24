<?php
/* Admin note editor for saved orders. */
function save_order_note( $order, $note ) {
    $order->set_customer_note( $note );
    $order->calculate_totals();
    $order->save();
}

function edit_saved_order_note( $order_id, $note ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) { return; }
    save_order_note( $order, $note );
}
