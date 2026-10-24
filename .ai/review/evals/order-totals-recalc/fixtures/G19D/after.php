<?php
/* Read-only order summary in a two-decimal currency. */
function formatted_order_total( $order ) {
    $display_total = round( $order->get_total(), 2 );
    return number_format( $display_total, 2, '.', '' );
}
