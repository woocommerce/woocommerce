<?php
/* Scenario context: Synthetic production fixture. All functions are loaded for web and runner requests. Cleanup callback deletes expired temporary exports idempotently. Registrar runs once per request; existing recurring action remains queued. */
function wc_register_export_cleanup() {
    if ( ! as_has_scheduled_action( 'woocommerce_export_cleanup', array(), 'wc-export' ) ) {
        as_schedule_recurring_action( time() + 60, 3600, 'woocommerce_export_cleanup', array(), 'wc-export' );
    }
}
add_action( 'action_scheduler_init', 'wc_register_export_cleanup' );
add_action( 'woocommerce_export_cleanup', 'wc_cleanup_export_files' );
