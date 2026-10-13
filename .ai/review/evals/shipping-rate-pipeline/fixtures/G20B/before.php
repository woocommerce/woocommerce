<?php
/* Scenario context: Released add_rate accepts omitted cost and constructs a free rate with integer zero. Fixture WC_Shipping_Rate retains provided cost unchanged and get_cost exposes it; extensions read that public value. args id/label always present. */
function add_rate( $args ) {
    $cost = $args['cost'] ?? 0;
    return new WC_Shipping_Rate( $args['id'], $args['label'], $cost );
}
