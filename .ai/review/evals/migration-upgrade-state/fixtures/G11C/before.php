<?php
/* Scenario context: load_order returns false for deleted parents; valid orders expose get_meta/update_meta_data/save. This migration only writes _refund_migrated=yes and no observers rely on repeated saves. */
function wc_update_refund_parents( $refunds, $load_order, $mark_complete ) {
    foreach ( $refunds as $refund ) {
        $order = $load_order( $refund['parent_id'] );
        if ( ! $order ) { continue; }
        $order->update_meta_data( '_refund_migrated', 'yes' );
        $order->save();
    }
    $mark_complete();
}
