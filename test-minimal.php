<?php
echo "Script executed at: " . microtime(true) . "\n";
$order = wc_get_order(wc_get_orders(['limit'=>1])[0]->get_id());
echo "Order ID: " . $order->get_id() . "\n";
$order->add_meta_data('_test_minimal', 'value-' . time());
$order->save();
global $wpdb;
$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_test_minimal'", $order->get_id()));
echo "Meta count: $count\n";
