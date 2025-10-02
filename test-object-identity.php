<?php
echo "=== Testing Object Identity ===\n";

$orders = wc_get_orders(['limit'=>3,'type'=>'shop_order']);

echo "Order IDs: " . $orders[0]->get_id() . ", " . $orders[1]->get_id() . ", " . $orders[2]->get_id() . "\n";
echo "Object IDs: " . spl_object_id($orders[0]) . ", " . spl_object_id($orders[1]) . ", " . spl_object_id($orders[2]) . "\n";
echo "Are they the same object? " . (spl_object_id($orders[0]) === spl_object_id($orders[1]) ? "YES - PROBLEM!" : "NO - OK") . "\n\n";

// Clean slate - delete existing test meta
foreach ($orders as $order) {
    $order->delete_meta_data('_test_identity');
}
foreach ($orders as $order) {
    $order->save();
}

echo "=== Adding meta to ONLY first order ===\n";
$orders[0]->add_meta_data('_test_identity', 'ONLY-FIRST-ORDER');
echo "Added meta to order " . $orders[0]->get_id() . "\n";

echo "=== Saving ONLY first order ===\n";
$orders[0]->save();
echo "Saved order " . $orders[0]->get_id() . "\n\n";

echo "=== Checking database for ALL three orders ===\n";
global $wpdb;
foreach ($orders as $idx => $order) {
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_test_identity'",
        $order->get_id()
    ));
    echo "Order $idx (ID " . $order->get_id() . "): $count entries\n";
    
    if ($count > 0) {
        $values = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_test_identity'",
            $order->get_id()
        ));
        echo "  Values: " . implode(", ", $values) . "\n";
    }
}
