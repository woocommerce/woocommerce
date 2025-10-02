<?php
// Simplest possible test - just ONE order, ONE save

error_log("=== SINGLE ORDER TEST START ===");

// Get just ONE order
$orders = wc_get_orders(['limit'=>1,'type'=>'shop_order']);
$order = $orders[0];
$order_id = $order->get_id();

error_log("Testing with order ID: $order_id");

// Delete any existing test meta first
error_log("Cleaning existing meta...");
$order->delete_meta_data('_debug_log_source_pending_deletion');
$order->save();

// Verify it's clean
global $wpdb;
$before_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_debug_log_source_pending_deletion'",
    $order_id
));
error_log("Meta count before adding: $before_count");

// Now add ONE entry
error_log("Adding meta data...");
$order->add_meta_data('_debug_log_source_pending_deletion', 'single-test-value');

// Check count BEFORE save
$meta_in_object = $order->get_meta('_debug_log_source_pending_deletion', false);
error_log("Meta in object before save: " . count($meta_in_object));

// Save
error_log("Calling save...");
$order->save();
error_log("Save completed");

// Check count AFTER save
$after_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_debug_log_source_pending_deletion'",
    $order_id
));
error_log("Meta count after save: $after_count");

// Get actual values
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_debug_log_source_pending_deletion'",
    $order_id
));
error_log("Actual entries:");
foreach ($results as $row) {
    error_log("  meta_id={$row->meta_id}, value={$row->meta_value}");
}

error_log("=== SINGLE ORDER TEST END ===");
