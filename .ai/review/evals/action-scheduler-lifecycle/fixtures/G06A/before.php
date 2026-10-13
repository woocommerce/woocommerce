<?php
/* Scenario context: Synthetic production fixture. File loaded in all AS runner requests after action_scheduler_init. Current running action has same hook,args,group. AS unique lookup includes running actions. wc_process_export_batch completes one bounded batch. */
function wc_export_queue_tick() {
    wc_process_export_batch();
    as_schedule_single_action( time() + 60, 'woocommerce_export_queue_tick', array(), 'wc-export', false );
}
add_action( 'woocommerce_export_queue_tick', 'wc_export_queue_tick' );
