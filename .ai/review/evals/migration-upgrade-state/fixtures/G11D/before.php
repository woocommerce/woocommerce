<?php
/* Scenario context: 11.0.0 shipped; 11.1.0 has not. Dispatcher records each completed key after all its callbacks succeed. backfill_zone_codes is an idempotent callback registered elsewhere in the fixture environment. */
function run_pending_migrations( $completed, $run ) {
    $db_updates = array( '11.0.0' => array( 'rebuild_shipping_index' ) );
    foreach ( $db_updates as $version => $callbacks ) {
        if ( in_array( $version, $completed, true ) ) { continue; }
        foreach ( $callbacks as $callback ) { $run( $callback ); }
    }
}
