<?php
/* Scenario context: net is the final discounted net line price; tax_rate decimal fraction; is_vat_exempt boolean from verified customer setting. This helper supplies checkout charge totals with two-decimal currency. */
function calculate_line_total( $net, $tax_rate, $is_vat_exempt ) {
    $tax = $net * $tax_rate;
    return round( $net + $tax, 2 );
}
