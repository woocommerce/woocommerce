<?php
/* Scenario context: lock serializes cross-process callbacks on identical coupon key and releases in finally. Counts/limits are nonnegative bounded integers, no unlimited sentinel; count never exceeds limit in input. */
function increase_usage_count( $db, $coupon_id, $lock ) {
    return $lock( 'coupon:' . $coupon_id, static function () use ( $db, $coupon_id ) {
        $row = $db->read_coupon( $coupon_id );
        if ( $row['usage_limit'] - $row['usage_count'] < 1 ) { return false; }
        $db->write_usage_count( $coupon_id, $row['usage_count'] + 1 );
        return true;
    } );
}
