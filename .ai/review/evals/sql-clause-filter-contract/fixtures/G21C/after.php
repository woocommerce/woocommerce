<?php
/* Scenario context: Private SQL builder used by an internal maintenance report. The SQL string is never exposed to filters; all references are in the shown function. No public consumer uses the alias. */
function wc_internal_stock_sql() { global $wpdb; return "SELECT lookup.stock_quantity FROM {$wpdb->prefix}wc_product_meta_lookup lookup ORDER BY lookup.product_id"; }
