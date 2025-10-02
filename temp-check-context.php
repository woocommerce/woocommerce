<?php
// Check if there's something special about the execution context

error_log("=== EXECUTION CONTEXT ===");
error_log("In shutdown: " . (did_action('shutdown') ? 'yes' : 'no'));
error_log("In admin: " . (is_admin() ? 'yes' : 'no'));
error_log("CLI: " . (defined('WP_CLI') && WP_CLI ? 'yes' : 'no'));
error_log("Doing AJAX: " . (wp_doing_ajax() ? 'yes' : 'no'));
error_log("Doing CRON: " . (wp_doing_cron() ? 'yes' : 'no'));
error_log("Current filter: " . current_filter());

// Check how many times save_meta_data is called
$save_meta_call_count = 0;

// Intercept at a lower level - the actual data store
add_filter('woocommerce_order_data_store_cpt_get_orders_query', function($query, $query_vars) {
    error_log("Get orders query called");
    return $query;
}, 10, 2);

// Most importantly: trace the actual add_metadata WordPress function
add_action('added_post_meta', function($meta_id, $object_id, $meta_key, $meta_value) {
    if ($meta_key === '_debug_log_source_pending_deletion') {
        static $add_count = 0;
        $add_count++;
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        $trace_str = "ADDED POST META #$add_count (meta_id=$meta_id, object_id=$object_id)\nBacktrace:\n";
        foreach (array_slice($bt, 0, 10) as $i => $frame) {
            $trace_str .= "  $i: " . ($frame['file'] ?? 'unknown') . ':' . ($frame['line'] ?? '?') . ' ';
            $trace_str .= ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '') . "\n";
        }
        error_log($trace_str);
    }
}, 10, 4);

error_log("=== RUNNING TEST ===");
$orders = wc_get_orders(['limit'=>1,'type'=>'shop_order']);
error_log("Order ID: " . $orders[0]->get_id());

$orders[0]->add_meta_data('_debug_log_source_pending_deletion', 'test-value-' . time());
error_log("Before save");
$orders[0]->save();
error_log("After save");

// Check how many were actually added
global $wpdb;
$count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_debug_log_source_pending_deletion'",
    $orders[0]->get_id()
));
error_log("Total meta entries in DB: $count");
