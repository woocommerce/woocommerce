<?php
/* Read-only order summary in a two-decimal currency. */
function formatted_order_total( $order ) {
    return number_format( $order->get_total(), 2, '.', '' );
}
