<?php
/* Coupon redemption adapter. lock serializes callbacks across processes per key and releases in finally. DB read/write operations do not lock. */
function increase_usage_count( $db, $coupon_id, $lock ) {
    $row = $db->read_coupon( $coupon_id );
    if ( $row['usage_count'] >= $row['usage_limit'] ) { return false; }
    $db->write_usage_count( $coupon_id, $row['usage_count'] + 1 );
    return true;
}
