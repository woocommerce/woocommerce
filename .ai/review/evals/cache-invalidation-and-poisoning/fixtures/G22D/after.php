<?php
/* Scenario context: Stock values are integer counts. wp_cache_get sets $found to a boolean, and this dedicated cache stores only integer counts. The shown writer remains the only writer. */
function wc_stock_label( $id ) {
    $value = wp_cache_get( $id, 'stock_labels', false, $found );
    if ( true === $found ) { return (int) $value; }
    $value = (int) get_post_meta( $id, '_stock', true );
    wp_cache_set( $id, $value, 'stock_labels' );
    return $value;
}
function wc_write_stock_label( $id, $value ) {
    update_post_meta( $id, '_stock', $value );
    wp_cache_delete( $id, 'stock_labels' );
}
