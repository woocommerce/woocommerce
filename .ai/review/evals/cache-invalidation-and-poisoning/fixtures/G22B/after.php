<?php
/* Persistent object cache. This isolated stock-label subsystem has one writer, shown below. */
function wc_stock_label( $id ) {
    $value = wp_cache_get( $id, 'stock_labels', false, $found );
    if ( $found ) { return $value; }
    $value = (int) get_post_meta( $id, '_stock', true );
    wp_cache_set( $id, $value, 'stock_labels' );
    return $value;
}
function wc_write_stock_label( $id, $value ) {
    update_post_meta( $id, '_stock', $value );
    wp_cache_delete( $id, 'stock_label' );
}
