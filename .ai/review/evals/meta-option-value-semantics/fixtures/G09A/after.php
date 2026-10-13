<?php
/* Scenario context: Synthetic production fixture. Released stored contract: empty string means inherit, string0 means free. Writer accepts decimal numeric strings or empty string. Reader shown governs export configuration. */
function wc_store_shipping_cost( $id, $cost ) {
    update_post_meta( $id, '_export_shipping_cost', (string) (float) $cost );
}
function wc_shipping_cost_or_default( $id ) {
    $cost = get_post_meta( $id, '_export_shipping_cost', true );
    return '' === $cost ? 'inherit' : $cost;
}
