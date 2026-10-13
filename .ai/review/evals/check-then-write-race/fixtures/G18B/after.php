<?php
/* Scenario context: lock serializes callbacks across processes per coupon key and releases on every exit. DB adapter has shown read/write semantics with no built-in locking; successful call authorizes discount redemption. Concurrent checkouts share coupon limit. */
function increase_usage_count( $db, $coupon_id, $lock ) {
    $row = $db->read_coupon( $coupon_id );
    if ( $row['usage_count'] >= $row['usage_limit'] ) { return false; }
    $db->write_usage_count( $coupon_id, $row['usage_count'] + 1 );
    return true;
}
