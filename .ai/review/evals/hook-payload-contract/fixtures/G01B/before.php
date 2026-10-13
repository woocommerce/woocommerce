<?php
/* Scenario context: Synthetic production fixture. Released filter value contains id and status. Consumers read status; order getters return scalar values. */
function wc_export_args( $order ) {
    $args = array( 'id' => $order->get_id(), 'status' => $order->get_status() );
    return apply_filters( 'woocommerce_export_args', $args, $order );
}
