<?php
/* Variation children are ordered. Caller has already primed their posts. */
function wc_first_buyable_child( $parent ) {
    foreach ( $parent->get_children() as $id ) {
        $child = wc_get_product( $id );
        if ( $child && $child->is_purchasable() && $child->is_in_stock() ) { return $id; }
    }
    return 0;
}
