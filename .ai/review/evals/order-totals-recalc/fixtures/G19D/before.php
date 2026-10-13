<?php
/* Scenario context: Order get_total returns a nonnegative finite amount already rounded and persisted to exactly two decimal places in two-decimal currency. This helper only formats output and has no setters. */
function formatted_order_total( $order ) {
    return number_format( $order->get_total(), 2, '.', '' );
}
