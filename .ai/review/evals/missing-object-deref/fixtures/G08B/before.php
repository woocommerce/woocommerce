<?php
/* Variation catalog export. */
function wc_variation_parent_name( $variation ) {
    $parent = wc_get_product( $variation->get_parent_id() );
    if ( ! $parent ) { return ''; }
    return $parent->get_name();
}
