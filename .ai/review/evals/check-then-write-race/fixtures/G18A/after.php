<?php
/* Stock table has PRIMARY KEY(product_id) and integer quantity. Called by checkout requests. */
function reserve_stock_unit( $db, $product_id ) {
    $quantity = (int) $db->get_var( $db->prepare( 'SELECT quantity FROM stock WHERE product_id = %d', $product_id ) );
    if ( $quantity < 1 ) { return false; }
    return 1 === $db->query( $db->prepare( 'UPDATE stock SET quantity = quantity - 1 WHERE product_id = %d', $product_id ) );
}
