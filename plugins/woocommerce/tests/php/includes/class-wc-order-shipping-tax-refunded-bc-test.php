<?php
/**
 * Regression test for backwards compatibility of WC_Order_Data_Store_Interface.
 *
 * Verifies that third-party order data stores which implement
 * WC_Order_Data_Store_Interface without the get_total_shipping_tax_refunded()
 * method continue to work without fatal errors. The interface intentionally
 * does NOT declare get_total_shipping_tax_refunded() to preserve BC; this
 * test guards against re-introducing that requirement.
 *
 * @package WooCommerce\Tests
 */

//phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Legacy class name.
/**
 * Class WC_Order_Shipping_Tax_Refunded_BC_Test.
 */
class WC_Order_Shipping_Tax_Refunded_BC_Test extends WC_Unit_Test_Case {

	/**
	 * Remove any data store overrides between tests so we don't leak state.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_order_data_store' );
		parent::tearDown();
	}

	/**
	 * @testdox WC_Order_Data_Store_Interface must not declare get_total_shipping_tax_refunded so third-party implementations remain BC-compatible.
	 */
	public function test_interface_does_not_require_get_total_shipping_tax_refunded(): void {
		$reflection = new ReflectionClass( 'WC_Order_Data_Store_Interface' );
		$this->assertFalse(
			$reflection->hasMethod( 'get_total_shipping_tax_refunded' ),
			'WC_Order_Data_Store_Interface must not declare get_total_shipping_tax_refunded(); adding it breaks third-party data stores. See https://github.com/woocommerce/woocommerce/issues/58369.'
		);
	}

	/**
	 * @testdox WC_Order::get_total_shipping_tax_refunded returns 0 without a fatal when the data store does not implement the method.
	 */
	public function test_returns_zero_when_data_store_missing_method(): void {
		// Ensure the legacy BC data store class exists for this test run.
		require_once __DIR__ . '/fixtures/class-wc-legacy-bc-order-data-store.php';

		// Override at priority 1000 so we win against CustomOrdersTableController's
		// priority-999 filter, regardless of HPOS state.
		add_filter(
			'woocommerce_order_data_store',
			static function () {
				return 'WC_Legacy_BC_Order_Data_Store';
			},
			1000
		);

		// Force a fresh data store lookup that picks up our filter.
		$order = new WC_Order();

		$data_store_class = $order->get_data_store()->get_current_class_name();
		$this->assertSame(
			'WC_Legacy_BC_Order_Data_Store',
			$data_store_class,
			'Test fixture data store should be in use for this order.'
		);

		$this->assertFalse(
			method_exists( 'WC_Legacy_BC_Order_Data_Store', 'get_total_shipping_tax_refunded' ),
			'Fixture must not declare get_total_shipping_tax_refunded() in order to exercise the BC code path.'
		);

		// The previous implementation used method_exists() against the WC_Data_Store
		// proxy, which never reports magic-call methods, so even data stores that
		// supported get_total_shipping_tax_refunded() silently returned 0. The
		// is_callable() check now correctly delegates to the wrapped instance.
		$this->assertSame(
			0,
			$order->get_total_shipping_tax_refunded(),
			'WC_Order::get_total_shipping_tax_refunded() must safely fall back to 0 when the data store does not implement the method.'
		);
	}

	/**
	 * @testdox WC_Order::get_total_shipping_tax_refunded delegates to the data store when the underlying instance implements the method via __call proxy.
	 */
	public function test_delegates_to_data_store_via_proxy(): void {
		// The default order data store (WC_Order_Data_Store_CPT or OrdersTableDataStore)
		// implements get_total_shipping_tax_refunded() via Abstract_WC_Order_Data_Store_CPT.
		// Reach it via the WC_Data_Store proxy to ensure is_callable() correctly
		// detects __call-routed methods.
		$order = new WC_Order();

		$this->assertTrue(
			is_callable( array( $order->get_data_store(), 'get_total_shipping_tax_refunded' ) ),
			'Standard order data store must expose get_total_shipping_tax_refunded() via the WC_Data_Store proxy.'
		);
	}
}
