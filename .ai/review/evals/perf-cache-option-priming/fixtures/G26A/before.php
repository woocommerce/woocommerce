<?php
/* Scenario context: Every product listing renders this helper using raw IDs from a fields=ids query. Caches are initially cold; no upstream or object-cache layer has resolved products. Listing size is extension-configurable and unbounded. Helper renders names only, no images. */
function wc_render_listing_names( $ids ) { _prime_post_caches( $ids ); $names = array(); foreach ( $ids as $id ) { $p = wc_get_product( $id ); if ( $p ) { $names[] = $p->get_name(); } } return $names; }
