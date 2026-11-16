<?php
/* Product stock report receives IDs from a post_status=any query. */
function wc_published_stock( $ids ) { global $wpdb; $ids = array_map( 'absint', $ids ); if ( ! $ids ) { return array(); } return $wpdb->get_col( "SELECT l.stock_quantity FROM {$wpdb->prefix}wc_product_meta_lookup l INNER JOIN {$wpdb->posts} p ON p.ID=l.product_id WHERE p.post_type='product' AND p.post_status='publish' AND l.product_id IN (" . implode( ',', $ids ) . ')' ); }
