<?php
/* Scenario context: Concurrent checkout requests invoke this function; stock has unique product_id, integer quantity. DB updates are atomic with affected-row return; no enclosing transaction or application lock exists. Success grants one item to the caller. */
function reserve_stock_unit( $db, $product_id ) {
    $quantity = (int) $db->get_var( $db->prepare( 'SELECT quantity FROM stock WHERE product_id = %d', $product_id ) );
    if ( $quantity < 1 ) { return false; }
    return 1 === $db->query( $db->prepare( 'UPDATE stock SET quantity = quantity - 1 WHERE product_id = %d', $product_id ) );
}
