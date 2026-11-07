<?php
/* Checkout line calculation in a two-decimal currency. net is discounted net price and tax_rate is a decimal fraction. */
function calculate_line_total( $net, $tax_rate, $is_vat_exempt ) {
    $tax = $net * $tax_rate;
    return round( $net + $tax, 2 );
}
