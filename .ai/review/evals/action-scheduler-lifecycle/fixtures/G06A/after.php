<?php
/* Queue registration is loaded in web and runner bootstrap after action_scheduler_init. */
function wc_export_queue_tick() {
    wc_process_export_batch();
    as_schedule_single_action( time() + 60, 'woocommerce_export_queue_tick', array(), 'wc-export', true );
}
add_action( 'woocommerce_export_queue_tick', 'wc_export_queue_tick' );
