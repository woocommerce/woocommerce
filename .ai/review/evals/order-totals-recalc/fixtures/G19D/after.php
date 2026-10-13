<?php
/* Scenario context: Order get_total returns a nonnegative finite amount already rounded and persisted to exactly two decimal places in two-decimal currency. This helper only formats output and has no setters. */
function formatted_order_total( $order ) {
    $display_total = round( $order->get_total(), 2 );
    return number_format( $display_total, 2, '.', '' );
}
