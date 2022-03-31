<?php
/**
 * Tests for WPPostToCOTMigrator class.
 */

use Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable\WPPostToCOTMigrator;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ShippingHelper;

/**
 * Class WPPostToCOTMigratorTest.
 */
class WPPostToCOTMigratorTest extends WC_Unit_Test_Case {

	/**
	 * @var DataSynchronizer
	 */
	private $synchronizer;

	/**
	 * @var WPPostToCOTMigrator
	 */
	private $sut;

	/**
	 * @var OrdersTableDataStore;
	 */
	private $data_store;

	public function setUp(): void {
		parent::setUp();
		$this->create_order_custom_table_if_not_exist();
		$this->data_store = wc_get_container()->get( OrdersTableDataStore::class );
		$this->sut        = wc_get_container()->get( WPPostToCOTMigrator::class );
	}

	public function test_process_next_migration_batch_normal_order() {
		$order = wc_get_order( $this->create_complex_wp_post_order() );
		$this->clear_all_orders_and_reset_checkpoint();
		$this->sut->process_next_migration_batch( 100 );

		$this->assert_core_data_is_migrated( $order );
		$this->assert_order_addresses_are_migrated( $order );
		$this->assert_order_op_data_is_migrated( $order );
	}

	public function test_process_next_migration_batch_already_migrated_order() {
		global $wpdb;
		$order = wc_get_order( $this->create_complex_wp_post_order() );
		$this->clear_all_orders_and_reset_checkpoint();

		// Run the migration once.
		$this->sut->process_next_migration_batch( 100 );

		// Delete checkpoint and run migration again, assert there are still no duplicates.
		$this->sut->update_checkpoint( 0 );
		$this->sut->process_next_migration_batch( 100 );

		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_orders_table_name()}
WHERE post_id = {$order->get_id()}"
			),
			'Order record is duplicated.'
		);
		$order_id = $wpdb->get_var( "SELECT id FROM {$this->data_store::get_orders_table_name()} WHERE post_id = {$order->get_id()}" );
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
	}

	public function test_process_next_migration_batch_interrupted_migrating_order() {
		$this->markTestSkipped();
	}

	public function test_process_next_migration_batch_invalid_order_data() {
		$this->markTestSkipped();
	}

	public function test_process_next_migration_batch_invalid_valid_order_combo() {
		$this->markTestSkipped();
	}

	public function test_process_next_migration_batch_stale_order() {
		$this->markTestSkipped();
	}

	private function get_order_from_cot( $post_order ) {
		global $wpdb;
		$order_table = $this->data_store::get_orders_table_name();
		$query       = "SELECT * FROM $order_table WHERE post_id = {$post_order->get_id()};";

		return $wpdb->get_row( $query );
	}

	private function get_address_details_from_cot( $order_id, $address_type ) {
		global $wpdb;
		$address_table = $this->data_store::get_addresses_table_name();

		return $wpdb->get_row( "SELECT * FROM $address_table WHERE order_id = $order_id AND address_type = '$address_type';" );
	}

	private function get_order_operational_data_from_cot( $order_id ) {
		global $wpdb;
		$operational_data_table = $this->data_store::get_operational_data_table_name();

		return $wpdb->get_row( "SELECT * FROM $operational_data_table WHERE order_id = $order_id;" );
	}

	private function create_complex_wp_post_order() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$uniq_cust_id = wp_generate_password( 10, false );
		$customer = CustomerHelper::create_customer( "user$uniq_cust_id", $uniq_cust_id, "user$uniq_cust_id@example.com" );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '15.0000',
			'tax_rate_name'     => 'tax',
			'tax_rate_priority' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_shipping' => '1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		ShippingHelper::create_simple_flat_rate();

		$order = OrderHelper::create_order();
		// Make sure this is a wp_post order.
		$post = get_post( $order->get_id() );
		$this->assertNotNull( $post, 'Order is not created in wp_post table.' );
		$this->assertEquals( 'shop_order', $post->post_type, 'Order is not created in wp_post table.' );

		$order->save();

		$order->set_status( 'completed' );
		$order->set_currency( 'INR' );
		$order->set_customer_id( $customer->get_id() );
		$order->set_billing_email( $customer->get_billing_email() );

		$payment_gateway = new WC_Mock_Payment_Gateway();
		$order->set_payment_method( 'mock' );
		$order->set_transaction_id( 'mock1' );

		$order->set_customer_ip_address( '1.1.1.1' );
		$order->set_customer_user_agent( 'wc_unit_tests' );

		$order->save();

		$order->set_shipping_first_name( 'Albert' );
		$order->set_shipping_last_name( 'Einstein' );
		$order->set_shipping_company( 'The Olympia Academy' );
		$order->set_shipping_address_1( '112 Mercer Street' );
		$order->set_shipping_address_2( 'Princeton' );
		$order->set_shipping_city( 'New Jersey' );
		$order->set_shipping_postcode( '08544' );
		$order->set_shipping_phone( '299792458' );
		$order->set_shipping_country( 'US' );

		$order->set_created_via( 'unit_tests' );
		$order->set_version( '0.0.2' );
		$order->set_prices_include_tax( true );
		wc_update_coupon_usage_counts( $order->get_id() );
		$order->get_data_store()->set_download_permissions_granted( $order, true );
		$order->set_cart_hash( '1234' );
		$order->update_meta_data( '_new_order_email_sent', 'true' );
		$order->update_meta_data( '_order_stock_reduced', 'true' );
		$order->set_date_paid( time() );
		$order->set_date_completed( time() );
		$order->calculate_shipping();

		$order->save();
		$order->save_meta_data();

		return $order->get_id();
	}

	private function assert_core_data_is_migrated( $order ) {
		$db_order = $this->get_order_from_cot( $order );

		// Verify core data.
		$this->assertEquals( $order->get_id(), $db_order->post_id );
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
		$this->assertEquals( $order->get_shipping_tax(), $db_order_op_data->shipping_tax_amount );
		$this->assertEquals( $order->get_shipping_total(), $db_order_op_data->shipping_total_amount );
		$this->assertEquals( $order->get_discount_tax(), $db_order_op_data->discount_tax_amount );
		$this->assertEquals( $order->get_discount_total(), $db_order_op_data->discount_total_amount );
	}

	private function clear_all_orders_and_reset_checkpoint() {
		global $wpdb;
		$order_tables = $this->data_store->get_all_table_names();
		foreach ( $order_tables as $table ) {
			$wpdb->query( "TRUNCATE table $table;" );
		}
		$this->sut->delete_checkpoint();
	}

	private function create_order_custom_table_if_not_exist() {
		$order_table_controller = wc_get_container()
			->get( 'Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' );
		$order_table_controller->show_feature();
		$this->synchronizer = wc_get_container()
			->get( DataSynchronizer::class );
		if ( ! $this->synchronizer->check_orders_table_exists() ) {
			$this->synchronizer->create_database_tables();
		}
	}
}
