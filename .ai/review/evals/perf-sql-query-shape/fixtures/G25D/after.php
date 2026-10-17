<?php
/* Product stock report receives IDs from a post_status=any query. */
function wc_published_stock( $ids ) { global $wpdb; $ids = array_map( 'absint', $ids ); if ( ! $ids ) { return array(); } return $wpdb->get_col( "SELECT l.stock_quantity FROM {$wpdb->prefix}wc_product_meta_lookup l WHERE EXISTS (SELECT 1 FROM {$wpdb->posts} p WHERE p.ID=l.product_id AND p.post_type='product' AND p.post_status='publish') AND l.product_id IN (" . implode( ',', $ids ) . ')' ); }
