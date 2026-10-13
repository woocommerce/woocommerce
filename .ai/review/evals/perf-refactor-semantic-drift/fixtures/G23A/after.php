<?php
/* Scenario context: Performance change for the default purchasable variation selector. Parent children are ordered IDs and may begin with disabled or out-of-stock variations. Product posts are already primed by the caller; public behavior returns first eligible ID or 0. */
function wc_first_buyable_child( $parent ) {
    foreach ( array_slice( $parent->get_children(), 0, 1 ) as $id ) {
        $child = wc_get_product( $id );
        if ( $child && $child->is_purchasable() && $child->is_in_stock() ) { return $id; }
    }
    return 0;
}
