<?php
/* Migration registry for release 11.1.0. Version 11.0.0 has shipped. */
function run_pending_migrations( $completed, $run ) {
    $db_updates = array( '11.0.0' => array( 'rebuild_shipping_index' ) );
    foreach ( $db_updates as $version => $callbacks ) {
        if ( in_array( $version, $completed, true ) ) { continue; }
        foreach ( $callbacks as $callback ) { $run( $callback ); }
    }
}

function upgrade_existing_store( $run ) {
    run_pending_migrations( array( '11.0.0' ), $run );
}
