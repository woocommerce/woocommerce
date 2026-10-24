<?php
/* Listing receives raw IDs from a fields=ids query with cold product caches. It renders names only. */
function wc_render_listing_names( $ids ) { $names = array(); foreach ( $ids as $id ) { $p = wc_get_product( $id ); if ( $p ) { $names[] = $p->get_name(); } } return $names; }
