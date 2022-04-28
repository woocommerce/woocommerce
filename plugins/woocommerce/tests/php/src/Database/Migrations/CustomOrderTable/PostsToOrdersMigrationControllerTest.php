<?php
/**
 * Tests for PostsToOrdersMigrationController class.
 */

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ShippingHelper;

/**
 * Class PostsToOrdersMigrationControllerTest.
 */
class PostsToOrdersMigrationControllerTest extends WC_Unit_Test_Case {

	/**
	 * @var DataSynchronizer
	 */
	private $synchronizer;

	/**
	 * @var PostsToOrdersMigrationController
	 */
	private $sut;

	/**
	 * @var OrdersTableDataStore;
	 */
	private $data_store;

	/**
	 * Setup data_store and sut.
	 */
	public function setUp(): void {
		parent::setUp();
		OrderHelper::create_order_custom_table_if_not_exist();
		$this->data_store = wc_get_container()->get( OrdersTableDataStore::class );
		$this->sut        = wc_get_container()->get( PostsToOrdersMigrationController::class );
	}

	/**
	 * Test that migration for a normal order happens as expected.
	 */
	public function test_migration_for_normal_order() {
		$order = wc_get_order( OrderHelper::create_complex_wp_post_order() );
		$this->clear_all_orders();
		$this->sut->migrate_order( $order->get_id() );

		$this->assert_core_data_is_migrated( $order );
		$this->assert_order_addresses_are_migrated( $order );
		$this->assert_order_op_data_is_migrated( $order );
		$this->assert_metadata_is_migrated( $order );
	}

	/**
	 * Test that already migrated order isn't migrated twice.
	 */
	public function test_migration_for_already_migrated_order() {
		global $wpdb;
		$order = wc_get_order( OrderHelper::create_complex_wp_post_order() );
		$this->clear_all_orders();

		// Run the migration once.
		$this->sut->migrate_order( $order->get_id() );

		// Run the migration again, assert there are still no duplicates.
		$this->sut->migrate_order( $order->get_id() );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_orders_table_name()}
WHERE id = {$order->get_id()}"
			),
			'Order record is duplicated.'
		);
		$order_id = $wpdb->get_var( "SELECT id FROM {$this->data_store::get_orders_table_name()} WHERE id = {$order->get_id()}" );
		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_addresses_table_name()}
WHERE order_id = {$order_id} AND address_type = 'billing'
"
			)
		);
		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_addresses_table_name()}
WHERE order_id = {$order_id} AND address_type = 'shipping'
"
			)
		);
		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_operational_data_table_name()}
WHERE order_id = {$order_id}
"
			)
		);

		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_meta_table_name()}
WHERE order_id = {$order_id} AND meta_key = 'unique_key_1' AND meta_value = 'unique_value_1'
"
			)
		);
		$this->assertEquals(
			2,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_meta_table_name()}
WHERE order_id = {$order_id} AND meta_key = 'non_unique_key_1' AND meta_value in ( 'non_unique_value_1', 'non_unique_value_2' )
"
			)
		);
		// phpcs:enable
	}

	/**
	 * Test that when an order is partially migrated, it can still be resumed as expected.
	 */
	public function test_interrupted_migration() {
		$this->markTestSkipped();
	}

	/**
	 * Test that invalid order data is not migrated but logged.
	 */
	public function test_migrating_invalid_order_data() {
		$this->markTestSkipped();
	}

	/**
	 * Test when one order is invalid but other one is valid in a migration batch.
	 */
	public function test_migrating_invalid_valid_order_combo() {
		$this->markTestSkipped();
	}

	/**
	 * Helper method to get order object from COT.
	 *
	 * @param WP_Post $post_order Post object for order.
	 *
	 * @return array|object|void|null DB object from COT.
	 */
	private function get_order_from_cot( $post_order ) {
		global $wpdb;
		$order_table = $this->data_store::get_orders_table_name();
		$query       = "SELECT * FROM $order_table WHERE id = {$post_order->get_id()};";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_row( $query );
	}

	/**
	 * Helper method to get address details from DB.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $address_type Address Type.
	 *
	 * @return array|object|void|null DB object.
	 */
	private function get_address_details_from_cot( $order_id, $address_type ) {
		global $wpdb;
		$address_table = $this->data_store::get_addresses_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( "SELECT * FROM $address_table WHERE order_id = $order_id AND address_type = '$address_type';" );
	}

	/**
	 * Helper method to get operational details from COT.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array|object|void|null DB Object.
	 */
	private function get_order_operational_data_from_cot( $order_id ) {
		global $wpdb;
		$operational_data_table = $this->data_store::get_operational_data_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( "SELECT * FROM $operational_data_table WHERE order_id = $order_id;" );
	}

	/**
	 * Helper method to get meta data from custom order tables for given order id.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Meta data for an order ID.
	 */
	private function get_meta_data_from_cot( $order_id ) {
		global $wpdb;
		$metadata_table = $this->data_store::get_meta_table_name();

		// phpcs:ignore
		return $wpdb->get_results( "SELECT * FROM $metadata_table WHERE order_id = $order_id;" );
	}

	/**
	 * Helper method to assert core data is migrated.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function assert_core_data_is_migrated( $order ) {
		$db_order = $this->get_order_from_cot( $order );

		// Verify core data.
		$this->assertEquals( $order->get_id(), $db_order->id );
		$this->assertEquals( 'wc-' . $order->get_status(), $db_order->status );
		$this->assertEquals( 'INR', $db_order->currency );
		$this->assertEquals( $order->get_customer_id(), $db_order->customer_id );
		$this->assertEquals( $order->get_billing_email(), $db_order->billing_email );
		$this->assertEquals( $order->get_payment_method(), $db_order->payment_method );
		$this->assertEquals(
			$order->get_date_created()->date( DATE_ISO8601 ),
			( new WC_DateTime( $db_order->date_created_gmt ) )->date( DATE_ISO8601 )
		);
		$this->assertEquals( $order->get_date_modified()->date( DATE_ISO8601 ), ( new WC_DateTime( $db_order->date_updated_gmt ) )->date( DATE_ISO8601 ) );
		$this->assertEquals( $order->get_parent_id(), $db_order->parent_order_id );
		$this->assertEquals( $order->get_payment_method_title(), $db_order->payment_method_title );
		$this->assertEquals( $order->get_transaction_id(), $db_order->transaction_id );
		$this->assertEquals( $order->get_customer_ip_address(), $db_order->ip_address );
		$this->assertEquals( $order->get_customer_user_agent(), $db_order->user_agent );
	}

	/**
	 * Helper method to assert addresses are migrated.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function assert_order_addresses_are_migrated( $order ) {
		$db_order = $this->get_order_from_cot( $order );

		// Verify order billing address.
		$db_order_address = $this->get_address_details_from_cot( $db_order->id, 'billing' );
		$this->assertEquals( $order->get_billing_first_name(), $db_order_address->first_name );
		$this->assertEquals( $order->get_billing_last_name(), $db_order_address->last_name );
		$this->assertEquals( $order->get_billing_company(), $db_order_address->company );
		$this->assertEquals( $order->get_billing_address_1(), $db_order_address->address_1 );
		$this->assertEquals( $order->get_billing_address_2(), $db_order_address->address_2 );
		$this->assertEquals( $order->get_billing_city(), $db_order_address->city );
		$this->assertEquals( $order->get_billing_postcode(), $db_order_address->postcode );
		$this->assertEquals( $order->get_billing_country(), $db_order_address->country );
		$this->assertEquals( $order->get_billing_email(), $db_order_address->email );
		$this->assertEquals( $order->get_billing_phone(), $db_order_address->phone );

		// Verify order shipping address.
		$db_order_address = $this->get_address_details_from_cot( $db_order->id, 'shipping' );
		$this->assertEquals( $order->get_shipping_first_name(), $db_order_address->first_name );
		$this->assertEquals( $order->get_shipping_last_name(), $db_order_address->last_name );
		$this->assertEquals( $order->get_shipping_company(), $db_order_address->company );
		$this->assertEquals( $order->get_shipping_address_1(), $db_order_address->address_1 );
		$this->assertEquals( $order->get_shipping_address_2(), $db_order_address->address_2 );
		$this->assertEquals( $order->get_shipping_city(), $db_order_address->city );
		$this->assertEquals( $order->get_shipping_postcode(), $db_order_address->postcode );
		$this->assertEquals( $order->get_shipping_country(), $db_order_address->country );
		$this->assertEquals( $order->get_shipping_phone(), $db_order_address->phone );
	}

	/**
	 * Helper method to assert operational data is migrated.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function assert_order_op_data_is_migrated( $order ) {
		$db_order = $this->get_order_from_cot( $order );
		// Verify order operational data.
		$db_order_op_data = $this->get_order_operational_data_from_cot( $db_order->id );
		$this->assertEquals( $order->get_created_via(), $db_order_op_data->created_via );
		$this->assertEquals( $order->get_version(), $db_order_op_data->woocommerce_version );
		$this->assertEquals( $order->get_prices_include_tax(), $db_order_op_data->prices_include_tax );
		$this->assertEquals(
			wc_string_to_bool( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) ),
			$db_order_op_data->coupon_usages_are_counted
		);
		$this->assertEquals(
			wc_string_to_bool( $order->get_data_store()->get_download_permissions_granted( $order ) ),
			$db_order_op_data->download_permission_granted
		);
		$this->assertEquals( $order->get_cart_hash(), $db_order_op_data->cart_hash );
		$this->assertEquals(
			wc_string_to_bool( $order->get_meta( '_new_order_email_sent' ) ),
			$db_order_op_data->new_order_email_sent
		);
		$this->assertEquals( $order->get_order_key(), $db_order_op_data->order_key );
		$this->assertEquals( $order->get_data_store()->get_stock_reduced( $order ), $db_order_op_data->order_stock_reduced );
		$this->assertEquals(
			$order->get_date_paid()->date( DATE_ISO8601 ),
			( new WC_DateTime( $db_order_op_data->date_paid_gmt ) )->date( DATE_ISO8601 )
		);
		$this->assertEquals(
			$order->get_date_completed()->date( DATE_ISO8601 ),
			( new WC_DateTime( $db_order_op_data->date_completed_gmt ) )->date( DATE_ISO8601 )
		);
		$this->assertEquals( (float) $order->get_shipping_tax(), (float) $db_order_op_data->shipping_tax_amount );
		$this->assertEquals( (float) $order->get_shipping_total(), (float) $db_order_op_data->shipping_total_amount );
		$this->assertEquals( (float) $order->get_discount_tax(), (float) $db_order_op_data->discount_tax_amount );
		$this->assertEquals( (float) $order->get_discount_total(), (float) $db_order_op_data->discount_total_amount );
	}

	/**
	 * Helper method to assert that metadata is migrated for an order.
	 *
	 * @param WP_Post $order WP_Post order object.
	 */
	private function assert_metadata_is_migrated( $order ) {
		$db_order  = $this->get_order_from_cot( $order );
		$meta_data = $this->get_meta_data_from_cot( $db_order->id );

		$unique_row = array_filter(
			$meta_data,
			function ( $meta_row ) {
				return 'unique_key_1' === $meta_row->meta_key;
			}
		);

		$this->assertEquals( 1, count( $unique_row ) );
		$this->assertEquals( 'unique_value_1', array_values( $unique_row )[0]->meta_value );

		$non_unique_rows = array_filter(
			$meta_data,
			function ( $meta_row ) {
				return 'non_unique_key_1' === $meta_row->meta_key;
			}
		);
		$this->assertEquals( 2, count( $non_unique_rows ) );
		$this->assertEquals(
			array(
				'non_unique_value_1',
				'non_unique_value_2',
			),
			array_column( $non_unique_rows, 'meta_value' )
		);
	}

	/**
	 * Helper method to clear checkout and truncate order tables.
	 */
	private function clear_all_orders() {
		global $wpdb;
		$order_tables = $this->data_store->get_all_table_names();
		foreach ( $order_tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "TRUNCATE table $table;" );
		}
	}
}
