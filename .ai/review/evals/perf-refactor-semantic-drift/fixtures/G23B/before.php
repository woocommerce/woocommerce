<?php
/* Catalog visibility extension callbacks are registered before rendering. */
function wc_first_visible_id( $ids ) {
    $visible = apply_filters( 'woocommerce_visible_candidate_ids', $ids );
    return array_slice( $visible, 0, 1 );
}

add_filter( 'woocommerce_visible_candidate_ids', static function( $ids ) { return array_values( array_diff( $ids, array( 10 ) ) ); } );
