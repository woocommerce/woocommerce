<?php
// coupon_usage has PRIMARY KEY(coupon_id); counts and limits are nonnegative integers.
function increase_usage_count( $db, $coupon_id, $lock ) {
    return 1 === $db->query( $db->prepare( 'UPDATE coupon_usage SET usage_count = usage_count + 1 WHERE coupon_id = %d AND usage_count < usage_limit', $coupon_id ) );
}
