<?php
/* Scenario context: Performance refactor on cold raw child IDs. Existing selector returns first purchasable in-stock variation. No callback relies on observing database reads; all eligibility calls remain in order. */
function wc_first_buyable_child( $parent ) { $ids = $parent->get_children(); _prime_post_caches( $ids ); foreach ( $ids as $id ) { $p = wc_get_product( $id ); if ( $p && $p->is_purchasable() && $p->is_in_stock() ) { return $id; } } return 0; }
