<?php
/* Scenario context: Legacy refund rows may reference deleted orders. load_order returns false for missing parents. Remaining valid orders must be migrated; this runs only in the upgrade worker. */
function wc_update_refund_parents( $refunds, $load_order, $mark_complete ) {
    foreach ( $refunds as $refund ) {
        $order = $load_order( $refund['parent_id'] );
        if ( ! $order ) { continue; }
        $order->update_meta_data( '_refund_migrated', 'yes' );
        $order->save();
    }
    $mark_complete();
}
