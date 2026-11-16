<?php
/* Installation lock helper and its regression test, colocated for this eval. */
function wc_eval_create_lock() {
    global $wpdb;
    $created = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES ('wc_eval_lock', %d, 'no')", time() ) );
    if ( ! $created ) {
        $created = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %d WHERE option_name = 'wc_eval_lock' AND option_value < %d", time(), time() - 600 ) );
    }
    return (bool) $created;
}

class EvalLockContentionTest extends WC_Unit_Test_Case {
    public function test_held_lock_logs_no_database_error(): void {
        global $wpdb, $EZSQL_ERROR;
        $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES ('wc_eval_lock', %d, 'no')", time() ) );
        $errors_before = count( (array) $EZSQL_ERROR );
        $this->assertFalse( wc_eval_create_lock() );
        $this->assertCount( $errors_before, (array) $EZSQL_ERROR );
    }
}
