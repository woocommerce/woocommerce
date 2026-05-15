<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Meta_Box_Order_Data class.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

/**
 * Class WC_Meta_Box_Order_Data_Test
 *
 * Covers regression guards added for RSMAPGJ-337 / woo#41777: ensuring the
 * order's saved billing/shipping country is always present in the order
 * edit screen country dropdown, even when the store has restricted its
 * sell/ship-to countries or a filter removed the option.
 */
class WC_Meta_Box_Order_Data_Test extends WC_Unit_Test_Case {

	/**
	 * Helper for invoking a protected static method on WC_Meta_Box_Order_Data.
	 *
	 * @param string $method_name Method name.
	 * @param array  $args        Positional arguments.
	 * @return mixed
	 */
	private function call_protected_static( string $method_name, array $args ) {
		$reflection = new ReflectionMethod( 'WC_Meta_Box_Order_Data', $method_name );
		$reflection->setAccessible( true );
		return $reflection->invokeArgs( null, $args );
	}

	/**
	 * Tear down filters that tests may register.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_admin_billing_fields' );
		remove_all_filters( 'woocommerce_admin_shipping_fields' );
		parent::tearDown();
	}

	/**
	 * Build an order with a billing/shipping country set.
	 *
	 * @param string $billing_country  Billing country code.
	 * @param string $shipping_country Shipping country code.
	 * @return WC_Order
	 */
	private function create_order_with_country( string $billing_country, string $shipping_country ): WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_billing_country( $billing_country );
		$order->set_shipping_country( $shipping_country );
		$order->save();
		return $order;
	}

	/**
	 * The order's billing country must be present in the dropdown even when
	 * a filter restricts the country options.
	 */
	public function test_billing_country_is_included_when_filter_restricts_options(): void {
		$order = $this->create_order_with_country( 'ST', 'ST' );

		add_filter(
			'woocommerce_admin_billing_fields',
			function ( $fields ) {
				$fields['country']['options'] = array(
					''   => 'Select a country',
					'PT' => 'Portugal',
					'ES' => 'Spain',
				);
				return $fields;
			}
		);

		$fields = $this->call_protected_static( 'get_billing_fields', array( $order, 'edit' ) );

		$this->assertArrayHasKey( 'country', $fields );
		$this->assertArrayHasKey(
			'ST',
			$fields['country']['options'],
			"The order's saved billing country (ST) must remain selectable on the order edit screen."
		);
		// Existing options are preserved.
		$this->assertArrayHasKey( 'PT', $fields['country']['options'] );
		$this->assertArrayHasKey( 'ES', $fields['country']['options'] );
	}

	/**
	 * Same regression guard for the shipping country dropdown.
	 */
	public function test_shipping_country_is_included_when_filter_restricts_options(): void {
		$order = $this->create_order_with_country( 'PT', 'ST' );

		add_filter(
			'woocommerce_admin_shipping_fields',
			function ( $fields ) {
				$fields['country']['options'] = array(
					''   => 'Select a country',
					'PT' => 'Portugal',
					'ES' => 'Spain',
				);
				return $fields;
			}
		);

		$fields = $this->call_protected_static( 'get_shipping_fields', array( $order, 'edit' ) );

		$this->assertArrayHasKey( 'country', $fields );
		$this->assertArrayHasKey(
			'ST',
			$fields['country']['options'],
			"The order's saved shipping country (ST) must remain selectable on the order edit screen."
		);
	}

	/**
	 * The injected country must use the human-readable label from WC()->countries.
	 */
	public function test_injected_country_uses_label_from_countries_class(): void {
		$order = $this->create_order_with_country( 'ST', 'ST' );

		add_filter(
			'woocommerce_admin_billing_fields',
			function ( $fields ) {
				$fields['country']['options'] = array( '' => 'Select a country', 'PT' => 'Portugal' );
				return $fields;
			}
		);

		$fields    = $this->call_protected_static( 'get_billing_fields', array( $order, 'edit' ) );
		$countries = WC()->countries->get_countries();

		$this->assertSame(
			$countries['ST'],
			$fields['country']['options']['ST'],
			'The injected country label should come from the canonical country list.'
		);
	}

	/**
	 * If the order's country is already present, the options must not change.
	 */
	public function test_country_already_present_is_not_duplicated(): void {
		$order = $this->create_order_with_country( 'PT', 'PT' );

		add_filter(
			'woocommerce_admin_billing_fields',
			function ( $fields ) {
				$fields['country']['options'] = array(
					''   => 'Select a country',
					'PT' => 'Portugal',
					'ES' => 'Spain',
				);
				return $fields;
			}
		);

		$fields = $this->call_protected_static( 'get_billing_fields', array( $order, 'edit' ) );

		$this->assertSame(
			array( '', 'PT', 'ES' ),
			array_keys( $fields['country']['options'] ),
			'Options should remain unchanged when the order country is already present.'
		);
	}

	/**
	 * An empty saved country must not inject an empty key beyond the placeholder.
	 */
	public function test_empty_order_country_does_not_inject_option(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_billing_country( '' );
		$order->save();

		add_filter(
			'woocommerce_admin_billing_fields',
			function ( $fields ) {
				$fields['country']['options'] = array( '' => 'Select', 'PT' => 'Portugal' );
				return $fields;
			}
		);

		$fields = $this->call_protected_static( 'get_billing_fields', array( $order, 'edit' ) );

		$this->assertSame(
			array( '', 'PT' ),
			array_keys( $fields['country']['options'] ),
			'No extra option should be injected when the order has no billing country.'
		);
	}

	/**
	 * When no order is supplied (early calls), the helper must be a no-op.
	 */
	public function test_no_order_context_returns_fields_unchanged(): void {
		add_filter(
			'woocommerce_admin_billing_fields',
			function ( $fields ) {
				$fields['country']['options'] = array( '' => 'Select', 'PT' => 'Portugal' );
				return $fields;
			}
		);

		$fields = $this->call_protected_static( 'get_billing_fields', array( false, 'edit' ) );

		$this->assertSame(
			array( '', 'PT' ),
			array_keys( $fields['country']['options'] ),
			'Fields must be returned unchanged when no order context is available.'
		);
	}
}
