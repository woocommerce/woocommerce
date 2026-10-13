<?php
/* Scenario context: Hot listing function returns stock map for unique integer product IDs. Missing IDs are omitted and map iteration order is not part of the API. This patch batches lookups and guards empty input. */
function wc_listing_totals( $ids ) { global $wpdb; $out = array(); foreach ( array_map( 'absint', $ids ) as $id ) { $row = $wpdb->get_row( $wpdb->prepare( "SELECT product_id, stock_quantity FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $id ), ARRAY_A ); if ( $row ) { $out[$row['product_id']] = $row['stock_quantity']; } } return $out; }
