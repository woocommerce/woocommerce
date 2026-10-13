<?php
/* Scenario context: Fixture only renders built-in local_pickup and flat_rate methods. Product change updates built-in pickup display label to Collect in store. Method IDs/costs and selectable rates remain identical; labels are user-facing display copy. */
function add_rate( $args ) {
    $cost = $args['cost'] ?? 0;
    return new WC_Shipping_Rate( $args['id'], ( 'local_pickup' === $args['id'] ? 'Collect in store' : $args['label'] ), $cost );
}
