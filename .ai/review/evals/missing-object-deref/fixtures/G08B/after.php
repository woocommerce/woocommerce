<?php
/* Scenario context: Synthetic production fixture. Admin catalog exporter processes orphan variations whose nonzero parent ID points at deleted parent. wc_get_product returns false for that ID. */
function wc_variation_parent_name( $variation ) {
    $parent = wc_get_product( $variation->get_parent_id() );
    if ( ! $variation->get_parent_id() ) { return ''; }
    return $parent->get_name();
}
