<?php
// coupon_usage has PRIMARY KEY(coupon_id); counts and limits are nonnegative integers.
function increase_usage_count( $db, $coupon_id, $lock ) {
    return $lock( 'coupon:' . $coupon_id, static function () use ( $db, $coupon_id ) {
        $row = $db->get_row( $db->prepare( 'SELECT usage_count, usage_limit FROM coupon_usage WHERE coupon_id = %d', $coupon_id ), ARRAY_A );
        if ( ! $row || $row['usage_count'] >= $row['usage_limit'] ) { return false; }
        return 1 === $db->query( $db->prepare( 'UPDATE coupon_usage SET usage_count = usage_count + 1 WHERE coupon_id = %d', $coupon_id ) );
    } );
}
