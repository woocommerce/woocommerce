<?php
/* Scenario context: Synthetic production fixture. Released hook passes id and status; caller provides integer and unprefixed string. $row is local and not exposed. */
function wc_export_payload( $id, $status ) {
    $row = (object) array( 'id' => $id, 'status' => $status );
    do_action( 'woocommerce_order_export_status', $row->id, $row->status );
}
