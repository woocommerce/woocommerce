<?php
/* Upgrade worker for legacy refund records. */
function wc_update_refund_parents( $refunds, $load_order, $mark_complete ) {
    foreach ( $refunds as $refund ) {
        $order = $load_order( $refund['parent_id'] );
        $order->update_meta_data( '_refund_migrated', 'yes' );
        $order->save();
    }
    $mark_complete();
}
