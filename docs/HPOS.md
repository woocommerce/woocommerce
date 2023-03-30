# HPOS

WooCommerce has traditionally stored store orders and related order information (like refunds) as custom WordPress post types or post meta records. This comes with performance issues, and that's why HPOS (High Performance Order Storage) was developed. HPOS is the WooCommerce engine that stores orders in dedicated tables.

HPOS is also referred to as COT (Custom Order Tables) in some parts of the code, that's the early name of the engine.

There are a number of settings that control HPOS operation. Boolean settings are stored using the usual WooCommerce convention: `yes` for enabled ("set to true"), `no` for disabled ("set to false").

Most of the code related to HPOS is in [src/Internal/DataStores/Orders](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Internal/DataStores/Orders).


## Database tables

A number of database tables are used to store order data by HPOS. The `get_all_table_names` method in [the OrdersTableDataStore class](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/DataStores/Orders/OrdersTableDataStore.php) will return the names of all the tables.


## Enabling the feature

For HPOS to be usable, the HPOS feature must first be enabled. This should be done programmatically via [the features controller](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/Features/FeaturesController.php), or via admin UI (WooCommerce - Settings - Advanced - Features). The feature enable option name for HPOS is `woocommerce_feature_custom_order_tables_enabled`. The required database tables will be created automatically once the feature is enabled.


## Authoritative tables

At any given time while the HPOS feature is enabled there are two roles for the involved set database tables: _authoritative_ and _backup_. The authoritative tables are the working tables, where order data will be stored to and retrieved from during normal operation of the store. The _backup_ tables will receive a copy of the authoritative data whenever [synchronization](#synchronization) happens. 

If the `woocommerce_custom_orders_table_enabled` options is set to true, HPOS is active and [the new tables](#database-tables) are authoritative, while the posts and post meta tables act as the backup tables. If the option is set to false, it's the other way around. The option can be changed via admin UI (WooCommerce - Settings - Advanced - Custom data stores).

[The CustomOrdersTableController class](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/DataStores/Orders/CustomOrdersTableController.php) hooks on the `woocommerce_order_data_store` filter so that `WC_Data_Store::load( 'order' );` will return either an instance of [OrdersTableDataStore](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/DataStores/Orders/OrdersTableDataStore.php) or an instance of [WC_Order_Data_Store_CPT](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/data-stores/class-wc-order-data-store-cpt.php), depending on which are the authoritative tables.

In order to preserve data integrity switching the authoritative tables (from the new tables to the posts table or the other way around) isn't allowed while there are orders pending synchronization.


## Synchronization

_Synchronization_ is the process of applying all the pending changes in the authoritative tables to the backup tables. _Orders pending synchronization_ are orders that have been modified in the authoritative tables but the changes haven't been applied to the backup tables yet.

This can happen in a number of ways:


### Immediate synchronization

If the `woocommerce_custom_orders_table_data_sync_enabled` setting is set to true, synchronization happens automatically and immediately as soon as the orders are changed in the authoritative tables.


### Manual synchronization

When immediate synchronization is disabled, it can be triggered manually via command line as follows: `wp wc cot sync`. It can also be triggered programmatically as follows:

```php
$synchronizer = wc_get_container()->get(Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class);
$order_ids = $synchronizer->get_next_batch_to_process( $batch_size );
if ( count( $order_ids ) ) {
	$synchronizer->process_batch( $order_ids );
}
```

where `$batch_size` is the maximum count of orders to process.


### Scheduled synchronization

If immediate synchronization gets activated (`woocommerce_custom_orders_table_data_sync_enabled` is set to true) while there are orders pending synchronization, an instance of [DataSynchronizer](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/DataStores/Orders/DataSynchronizer.php) will be enqueued using [BatchProcessingController](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/BatchProcessing/BatchProcessingController.php) so that the synchronization of created/modified/deleted orders will happen in batches via scheduled actions. This scheduling happens inside [CustomOrdersTableController](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/DataStores/Orders/CustomOrdersTableController.php), by means of hooking into `woocommerce_update_options_advanced_custom_data_stores`.

If for some reason immediate synchronization is already active but synchronization is not scheduled, a trick to restart it is to go to the settings page (WooCommerce - Settings - Advanced - Custom data stores) and hit "Save" even without making any changes. As long as "Keep the posts table and the orders tables synchronized" is checked the synchronization scheduling will happen, even if it was checked before.

If the `woocommerce_auto_flip_authoritative_table_roles` option is set to true (there's a checkbox for it in the settings page), the authoritative tables will be switched automatically once all the orders have been synchronized. This is handled by [the CustomOrdersTableController class](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/DataStores/Orders/CustomOrdersTableController.php).


### Deletion synchronization

Synchronization of order deletions is tricky: if an order exists in one set of tables (new tables or posts) but not in the other, it's not clear if the missing orders need to be created or if the existing orders need to be deleted. Theorically, the orders missing from the backup tables imply the former and the orders missing from the authoritative tables imply the later; but that's dangerous as a bug in the involved code could easily lead to the deletion of legitimate orders.

To achieve a robust order deletion syncrhonization mechanism the following is done. Whenever an order is deleted and immediate synchronization is disabled, a record is created in the `wp_wc_orders_meta` table that has `deleted_from` as the key and the name of the authoritative table the order was deleted from (`wp_wc_orders` or the posts table). Then at synchronization time these records are processed (the corresponding orders are deleted from the corresponding tables) and deleted afterwards.

An exception to the above are the [placeholder records](#placeholder-records): these are deleted immediately when the corresponding order is deleted from `wp_wc_orders`, even if immediate synchronization is disabled.


## Placeholder records

Order ids must match in both the authoritative tables and the backup tables, otherwise synchronization wouldn't be possible. The order ids that are compared for order identification and synchronization purposes are the ones from the `id` field in both the `wp_wc_orders` table and the posts table.

If the posts table is authoritative achieving order id match is easy: the record in `wp_wc_orders` is created with the same id and that's it. However when the new orders tables are authoritative there's a problem: the posts table is used to store multiple types of data, not only orders; and by the time synchronization needs to happen, a non-order post could already exist having the same id as the order to synchronize.

To solve this _placeholder records_ are used. Whenever the new orders tables are authoritative and immediate synchronization is disabled, creating a new order will cause a record with post type `shop_order_placehold` and the same id as the order to be created in the posts table; this effectively "reserves" the order id in the posts table. Then at synchronization time the record is filled appropriately and its post type is changed to `shop_order`.


