<?php // phpcs:ignore

/**
 * Orders helper.
 *
 * @package Automattic\WooCommerce\RestApi\UnitTests\Helpers
 */

namespace Automattic\WooCommerce\RestApi\UnitTests\Helpers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Data_Store;
use WC_Mock_Payment_Gateway;
use WC_Order;
use WC_Product;
use WC_Tax;
use WC_Shipping_Rate;
use WC_Order_Item_Shipping;
use WC_Order_Item_Product;

/**
 * Class OrderHelper.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class OrderHelper {

	/**
	 * Delete a product.
	 *
	 * @param int $order_id ID of the order to delete.
	 */
	public static function delete_order( $order_id ) {

		$order = wc_get_order( $order_id );

		// Delete all products in the order.
		foreach ( $order->get_items() as $item ) {
			\Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::delete_product( $item['product_id'] );
		}

		ShippingHelper::delete_simple_flat_rate();

		// Delete the order post.
		$order->delete( true );
	}

	/**
	 * Create a order.
	 *
	 * @param int        $customer_id Customer ID.
	 * @param WC_Product $product Product object.
	 *
	 * @return WC_Order WC_Order object.
	 * @version 3.0 New parameter $product.
	 *
	 * @since   2.4
	 */
	public static function create_order( $customer_id = 1, $product = null ) {

		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		}

		ShippingHelper::create_simple_flat_rate();

		$order_data = array(
			'status'        => 'pending',
			'customer_id'   => $customer_id,
			'customer_note' => '',
			'total'         => '',
		);

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1'; // Required, else wc_create_order throws an exception.
		$order                  = wc_create_order( $order_data );

		// Add order products.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 4,
				'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
				'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
			)
		);
		$item->save();
		$order->add_item( $item );

		// Set billing address.
		$order->set_billing_first_name( 'Jeroen' );
		$order->set_billing_last_name( 'Sormani' );
		$order->set_billing_company( 'WooCompany' );
		$order->set_billing_address_1( 'WooAddress' );
		$order->set_billing_address_2( '' );
		$order->set_billing_city( 'WooCity' );
		$order->set_billing_state( 'NY' );
		$order->set_billing_postcode( '123456' );
		$order->set_billing_country( 'US' );
		$order->set_billing_email( 'admin@example.org' );
		$order->set_billing_phone( '555-32123' );

		// Add shipping costs.
		$shipping_taxes = WC_Tax::calc_shipping_tax( '10', WC_Tax::get_shipping_tax_rates() );
		$rate           = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', $shipping_taxes, 'flat_rate' );
		$item           = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$order->add_item( $item );

		// Set payment gateway.
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$order->set_payment_method( $payment_gateways['bacs'] );

		// Set totals.
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 0 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 0 );
		$order->set_shipping_tax( 0 );
		$order->set_total( 50 ); // 4 x $10 simple helper product.
		$order->save();

		return $order;
	}

	/**
	 * Helper method to drop custom tables if present.
	 */
	public static function delete_order_custom_tables() {
		$features_controller = wc_get_container()->get( Featurescontroller::class );
		$features_controller->change_feature_enable( 'custom_order_tables', true );
		$synchronizer = wc_get_container()
			->get( DataSynchronizer::class );
		if ( $synchronizer->check_orders_table_exists() ) {
			$synchronizer->delete_database_tables();
		}
	}

	/**
	 * Enables or disables the custom orders table across WP temporarily.
	 *
	 * @param boolean $enabled TRUE to enable COT or FALSE to disable.
	 * @return void
	 */
	public static function toggle_cot( bool $enabled ) {
		$features_controller = wc_get_container()->get( Featurescontroller::class );
		$features_controller->change_feature_enable( 'custom_order_tables', $enabled );

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, wc_bool_to_string( $enabled ) );

		// Confirm things are really correct.
		$wc_data_store = WC_Data_Store::load( 'order' );
		assert( is_a( $wc_data_store->get_current_class_name(), OrdersTableDataStore::class, true ) === $enabled );
	}

	/**
	 * Helper method to create custom tables if not present.
	 */
	public static function create_order_custom_table_if_not_exist() {
		$features_controller = wc_get_container()->get( Featurescontroller::class );
		$features_controller->change_feature_enable( 'custom_order_tables', true );

		$synchronizer = wc_get_container()->get( DataSynchronizer::class );
		if ( ! $synchronizer->check_orders_table_exists() ) {
			$synchronizer->create_database_tables();
		}
	}

	/**
	 * Helper method to create complex wp_post based order.
	 *
	 * @return int Order ID
	 */
	public static function create_complex_wp_post_order() {
		$current_cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
		self::toggle_cot( false );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$uniq_cust_id = wp_generate_password( 10, false );
		$customer     = CustomerHelper::create_customer( "user$uniq_cust_id", $uniq_cust_id, "user$uniq_cust_id@example.com" );
		$tax_rate     = array(
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

		$order = self::create_order();
		// Make sure this is a wp_post order.
		$post = get_post( $order->get_id() );
		assert( isset( $post ) );
		assert( 'shop_order' === $post->post_type );

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
		$order->set_customer_note( 'Please be careful' );

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
		$order->get_data_store()->set_recorded_sales( $order, true );
		$order->set_cart_hash( '1234' );
		$order->get_data_store()->set_email_sent( $order, true );
		$order->get_data_store()->stock_reduced( $order, true );
		$order->set_date_paid( time() );
		$order->set_date_completed( time() );
		$order->calculate_shipping();

		$order->add_meta_data( 'unique_key_1', 'unique_value_1', true );
		$order->add_meta_data( 'non_unique_key_1', 'non_unique_value_1', false );
		$order->add_meta_data( 'non_unique_key_1', 'non_unique_value_2', false );
		$order->save();
		$order->save_meta_data();

		self::toggle_cot( $current_cot_state );

		return $order->get_id();
	}

	/**
	 * Helper method to allow switching data stores.
	 *
	 * @param WC_Order       $order Order object.
	 * @param \WC_Data_Store $data_store Data store object to switch order to.
	 */
	public static function switch_data_store( $order, $data_store ) {
		$update_data_store_func = function ( $data_store ) {
			// Each order object contains a reference to its data store, but this reference is itself
			// held inside of an instance of WC_Data_Store, so we create that first.
			$data_store_wrapper = WC_Data_Store::load( 'order' );

			// Bind $data_store to our WC_Data_Store.
			( function ( $data_store ) {
				$this->current_class_name = get_class( $data_store );
				$this->instance           = $data_store;
			} )->call( $data_store_wrapper, $data_store );

			// Finally, update the $order object with our WC_Data_Store( $data_store ) instance.
			$this->data_store = $data_store_wrapper;
		};

		$update_data_store_func->call( $order, $data_store );
	}

	/**
	 * Creates a complex order with address info, line items, etc.
	 *
	 * @param \WC_Data_Store $data_store Data store object to use in creating order.
	 *
	 * @return \WC_Order Order object.
	 */
	public static function create_complex_data_store_order( $data_store = null ) {
		$order = new WC_Order();
		if ( ! $data_store ) {
			$data_store = wc_get_container()->get( OrdersTableDataStore::class );
		}
		self::switch_data_store( $order, $data_store );

		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		$order->set_status( 'pending' );
		$order->set_created_via( 'unit-tests' );
		$order->set_currency( 'COP' );
		$order->set_customer_ip_address( '127.0.0.1' );

		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => 2 ) ),
				'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => 2 ) ),
			)
		);

		$order->add_item( $item );

		$order->set_billing_first_name( 'Jeroen' );
		$order->set_billing_last_name( 'Sormani' );
		$order->set_billing_company( 'WooCompany' );
		$order->set_billing_address_1( 'WooAddress' );
		$order->set_billing_address_2( '' );
		$order->set_billing_city( 'WooCity' );
		$order->set_billing_state( 'NY' );
		$order->set_billing_postcode( '123456' );
		$order->set_billing_country( 'US' );
		$order->set_billing_email( 'admin@example.org' );
		$order->set_billing_phone( '555-32123' );

		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$order->set_payment_method( $payment_gateways['bacs'] );

		$order->set_shipping_total( 5.0 );
		$order->set_discount_total( 0.0 );
		$order->set_discount_tax( 0.0 );
		$order->set_cart_tax( 0.0 );
		$order->set_shipping_tax( 0.0 );
		$order->set_total( 25.0 );
		$order->save();

		$order->get_data_store()->set_stock_reduced( $order, true, false );

		$order->update_meta_data( 'my_meta', rand( 0, 255 ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand

		$order->save();

		return $order;
	}
}
