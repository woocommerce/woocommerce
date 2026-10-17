<?php
// Runtime dependency: Action Scheduler 4.1.0 (WooCommerce composer.lock).
/* Queue registration is loaded in web and runner bootstrap after action_scheduler_init. IDs address immutable saved export snapshots; repeated requests for the same ID request the same output. */
function wc_queue_one_export( $id ) {
    return as_enqueue_async_action( 'woocommerce_export_one', array( $id ), 'wc-export', true );
}
function wc_run_one_export( $id ) { wc_write_export_file( $id ); }
add_action( 'woocommerce_export_one', 'wc_run_one_export' );
