<?php
// Test without the logger to see if that's the cause
error_log("Starting script without logger");

$orders = wc_get_orders(['limit'=>3,'type'=>'shop_order']);
error_log("Got " . count($orders) . " orders");

$orders[0]->add_meta_data('_debug_log_source_pending_deletion', 'place-order-debug-1111');
$orders[0]->save();
error_log("Saved order 0: " . $orders[0]->get_id());

$orders[1]->add_meta_data('_debug_log_source_pending_deletion', 'place-order-debug-2222');
$orders[1]->save();
error_log("Saved order 1: " . $orders[1]->get_id());

$orders[2]->add_meta_data('_debug_log_source_pending_deletion', 'place-order-debug-3333');
$orders[2]->save();
error_log("Saved order 2: " . $orders[2]->get_id());

// NO LOGGER CALLS HERE - just end the script

error_log("Script completed without logger calls");
