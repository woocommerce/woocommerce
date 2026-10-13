<?php
/* Scenario context: Stock quantity is a nonnegative integer with unique product_id; DB atomic conditional update returns affected rows. Concurrent requests may race for last item. */
function reserve_stock_unit( $db, $product_id ) {
    $sql = $db->prepare( 'UPDATE stock SET quantity = quantity - 1 WHERE product_id = %d AND quantity >= 1', $product_id );
    return 1 === $db->query( $sql );
}
