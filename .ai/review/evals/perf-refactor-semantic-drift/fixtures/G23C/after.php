<?php
function wc_listing_names( $ids ) {
    _prime_post_caches( $ids );
    $names = array();
    foreach ( $ids as $id ) {
        $product = wc_get_product( $id );
        if ( $product ) { $names[$id] = $product->get_name(); }
    }
    return $names;
}
