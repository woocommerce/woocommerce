<?php
/* Internal maintenance report; this function is the complete SQL builder. */
function wc_internal_stock_sql() { global $wpdb; return "SELECT lookup.stock_quantity FROM {$wpdb->prefix}wc_product_meta_lookup lookup ORDER BY lookup.product_id"; }
