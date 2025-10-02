<?php
// Comprehensive tracing to see what's happening during save

error_log("=== SCRIPT START ===");

// Track every metadata operation at the database level
add_filter('query', function($query) {
    if (strpos($query, '_debug_log_source_pending_deletion') !== false) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $trace_str = '';
        foreach ($trace as $i => $frame) {
            $trace_str .= "\n  $i: " . ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
        }
        error_log("QUERY with debug meta: " . substr($query, 0, 200) . $trace_str);
    }
    return $query;
}, 1);

// Track save_meta_data calls
class WC_Order_Tracker {
    public static function wrap_save_meta() {
        add_action('woocommerce_before_order_object_save', function($order) {
            error_log("BEFORE SAVE - Order ID: " . $order->get_id() . ", meta_data count: " . count($order->get_meta_data()));
        }, 1);
        
        add_action('woocommerce_after_order_object_save', function($order) {
            error_log("AFTER SAVE - Order ID: " . $order->get_id());
        }, 1);
    }
}

WC_Order_Tracker::wrap_save_meta();

error_log("=== GETTING ORDERS ===");
$orders = wc_get_orders(['limit'=>1,'type'=>'shop_order']);
error_log("Got order ID: " . $orders[0]->get_id());

error_log("=== ADDING META DATA ===");
$orders[0]->add_meta_data('_debug_log_source_pending_deletion', 'place-order-debug-TEST');

error_log("=== ABOUT TO CALL SAVE ===");
$orders[0]->save();
error_log("=== SAVE RETURNED ===");

// Force any deferred operations to complete
error_log("=== CHECKING FINAL STATE ===");
$fresh_order = wc_get_order($orders[0]->get_id());
$meta_values = $fresh_order->get_meta('_debug_log_source_pending_deletion', false); // Get all values
error_log("Final meta count: " . count($meta_values));
foreach ($meta_values as $idx => $val) {
    error_log("  Meta $idx: " . print_r($val, true));
}

error_log("=== SCRIPT END ===");
