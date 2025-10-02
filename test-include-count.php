<?php
// Test if include is being executed multiple times
static $execution_count = 0;
$execution_count++;

$unique_id = uniqid('exec_', true);
echo "Execution #$execution_count at " . microtime(true) . " (ID: $unique_id)\n";

if ($execution_count > 1) {
    echo "WARNING: File has been executed $execution_count times!\n";
    echo "Backtrace of second execution:\n";
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
}

$orders = wc_get_orders(['limit'=>1,'type'=>'shop_order']);
$order = $orders[0];

echo "Working with order ID: " . $order->get_id() . "\n";

// Clean any existing test meta
$order->delete_meta_data('_test_include_count');
$order->save();

// Add exactly once
echo "Adding meta data (execution #$execution_count)...\n";
$order->add_meta_data('_test_include_count', "execution_$execution_count");

echo "Saving...\n";
$order->save();

// Check immediately
global $wpdb;
$count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_test_include_count'",
    $order->get_id()
));

echo "Database shows $count entries\n";

if ($count != 1) {
    echo "ERROR: Expected 1 entry, found $count!\n";
    $entries = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_test_include_count' ORDER BY meta_id",
        $order->get_id()
    ));
    echo "Entries:\n";
    foreach ($entries as $entry) {
        echo "  meta_id={$entry->meta_id}, value={$entry->meta_value}\n";
    }
}

echo "Script completed (execution #$execution_count)\n";
