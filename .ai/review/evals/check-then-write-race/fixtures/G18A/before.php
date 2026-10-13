<?php
/* Stock table has PRIMARY KEY(product_id) and integer quantity. Called by checkout requests. */
function reserve_stock_unit( $db, $product_id ) {
    return 1 === $db->query( $db->prepare( 'UPDATE stock SET quantity = quantity - 1 WHERE product_id = %d AND quantity > 0', $product_id ) );
}
