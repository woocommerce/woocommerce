<?php
// Track hook calls to see if save is being triggered multiple times
$save_hook_count = 0;

add_action('woocommerce_before_order_object_save', function($order) use (&$save_hook_count) {
    $save_hook_count++;
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
    $caller = isset($trace[2]) ? $trace[2]['function'] : 'unknown';
    error_log("woocommerce_before_order_object_save called (count: $save_hook_count) for order " . $order->get_id() . " from $caller");
}, 1);

add_action('woocommerce_after_order_object_save', function($order) {
    error_log("woocommerce_after_order_object_save called for order " . $order->get_id());
}, 1);

add_action('added_order_meta', function($meta_id, $object_id, $meta_key, $meta_value) {
    static $added_count = 0;
    $added_count++;
    error_log("added_order_meta called (count: $added_count): meta_id=$meta_id, order_id=$object_id, key=$meta_key, value=$meta_value");
}, 10, 4);

$orders = wc_get_orders(['limit'=>3,'type'=>'shop_order']);
$orders[0]->add_meta_data('_debug_log_source_pending_deletion', 'place-order-debug-1111');
$orders[0]->save();
$orders[1]->add_meta_data('_debug_log_source_pending_deletion', 'place-order-debug-2222');
$orders[1]->save();
$orders[2]->add_meta_data('_debug_log_source_pending_deletion', 'place-order-debug-3333');
$orders[2]->save();

error_log("Total save hooks called: $save_hook_count");

$l = wc_get_logger();
$l->debug('Foo!', ['source'=>'place-order-debug-1111']);
$l->debug('Foo!', ['source'=>'place-order-debug-2222']);
$l->debug('Foo!', ['source'=>'place-order-debug-3333']);
