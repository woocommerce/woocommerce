<?php
// Test file for wp eval-file
$orders = wc_get_orders(['limit'=>1,'type'=>'shop_order']);
$orders[0]->delete_meta_data('_debug_log_source_pending_deletion');
$orders[0]->save();

$orders[0]->add_meta_data('_debug_log_source_pending_deletion', 'eval-file-test');
$orders[0]->save();

global $wpdb;
$count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_debug_log_source_pending_deletion'",
    $orders[0]->get_id()
));

echo "Order ID: " . $orders[0]->get_id() . "\n";
echo "Meta entries in database: $count\n";

if ($count == 1) {
    echo "✓ SUCCESS - Only 1 entry!\n";
} else {
    echo "✗ FAIL - Found $count entries (expected 1)\n";
}
