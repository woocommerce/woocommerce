<?php
/* Scenario context: The dispatcher records completed version keys after success. Version 11.0.0 shipped; existing stores have that key recorded. backfill_zone_codes must execute once on every upgrading store. */
function run_pending_migrations( $completed, $run ) {
    $db_updates = array( '11.0.0' => array( 'rebuild_shipping_index' ) );
    foreach ( $db_updates as $version => $callbacks ) {
        if ( in_array( $version, $completed, true ) ) { continue; }
        foreach ( $callbacks as $callback ) { $run( $callback ); }
    }
}
