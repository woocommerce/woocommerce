<?php
// Add this at the very top to check if file is included multiple times
static $include_count = 0;
$include_count++;
error_log("temp.php included, count: $include_count");

$orders = wc_get_orders(['limit'=>3,'type'=>'shop_order']);
error_log("Processing order " . $orders[0]->get_id());
$orders[0]->add_meta_data('_debug_log_source_pending_deletion', 'place-order-debug-1111');
error_log("About to save order " . $orders[0]->get_id());
$orders[0]->save();
error_log("Saved order " . $orders[0]->get_id());

error_log("Processing order " . $orders[1]->get_id());
$orders[1]->add_meta_data('_debug_log_source_pending_deletion', 'place-order-debug-2222');
error_log("About to save order " . $orders[1]->get_id());
$orders[1]->save();
error_log("Saved order " . $orders[1]->get_id());

error_log("Processing order " . $orders[2]->get_id());
$orders[2]->add_meta_data('_debug_log_source_pending_deletion', 'place-order-debug-3333');
error_log("About to save order " . $orders[2]->get_id());
$orders[2]->save();
error_log("Saved order " . $orders[2]->get_id());

$l = wc_get_logger();
$l->debug('Foo!', ['source'=>'place-order-debug-1111']);
$l->debug('Foo!', ['source'=>'place-order-debug-2222']);
$l->debug('Foo!', ['source'=>'place-order-debug-3333']);

error_log("Script completed");
