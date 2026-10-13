<?php
/* Scenario context: Synthetic production fixture. Called after action_scheduler_init. Duplicate requests for same immutable export ID are coalescible. Handler does not enqueue itself. Registration file always loads in runner. */
function wc_queue_one_export( $id ) {
    return as_enqueue_async_action( 'woocommerce_export_one', array( $id ), 'wc-export', true );
}
function wc_run_one_export( $id ) { wc_write_export_file( $id ); }
add_action( 'woocommerce_export_one', 'wc_run_one_export' );
