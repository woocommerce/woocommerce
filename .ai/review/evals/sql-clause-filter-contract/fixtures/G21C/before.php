<?php
/* Internal maintenance report; this function is the complete SQL builder. */
function wc_internal_stock_sql() { global $wpdb; return "SELECT inventory.stock_quantity FROM {$wpdb->prefix}wc_product_meta_lookup inventory ORDER BY inventory.product_id"; }
