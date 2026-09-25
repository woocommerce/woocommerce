<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Gateways;

use Automattic\WooCommerce\Blocks\Shipping\PickupLocation;
use WC_Cache_Helper;
use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;
use WC_Helper_Product;
use WC_Helper_Shipping;
use WC_Order;
use WC_Order_Item_Shipping;
use WC_Payment_Gateway;
use WC_Shipping_Zone;
use WC_Unit_Test_Case;

/**
 * Tests for the ShippingMethodRestrictionsTrait, through the offline gateways that use it.
 */
class ShippingMethodRestrictionsTraitTest extends WC_Unit_Test_Case {

	/**
	 * Shipping zone matching the test customer's address.
	 *
	 * @var WC_Shipping_Zone
	 */
	private $zone;

	/**
	 * Canonical rate ids ("method_id:instance_id") of the zone's methods, keyed by a symbolic name.
	 *
	 * @var array<string, string>
	 */
	private $rate_ids = array();

	/**
	 * Shipping enabled state before the test.
	 *
	 * @var bool
	 */
	private $shipping_was_enabled;

	/**
	 * Set up a zone with several shipping methods so the real cart can resolve chosen rates.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->shipping_was_enabled = WC()->shipping()->enabled;
		WC()->shipping()->enabled   = true;
		WC_Helper_Shipping::force_customer_us_address();

		$this->zone = new WC_Shipping_Zone();
		$this->zone->set_zone_name( 'Restrictions zone' );
		$this->zone->add_location( 'US', 'country' );
		$this->zone->save();

		$this->rate_ids = array(
			'flat_rate_a'   => 'flat_rate:' . $this->zone->add_shipping_method( 'flat_rate' ),
			'flat_rate_b'   => 'flat_rate:' . $this->zone->add_shipping_method( 'flat_rate' ),
			'free_shipping' => 'free_shipping:' . $this->zone->add_shipping_method( 'free_shipping' ),
		);

		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();
	}

	/**
	 * Restore the state the base test case does not reset; the zone itself is rolled back with the database transaction.
	 */
	public function tearDown(): void {
		try {
			$this->set_order_pay_query_var( null );
			WC()->session->set( 'chosen_shipping_methods', null );
			WC_Cache_Helper::get_transient_version( 'shipping', true );
			WC()->shipping()->enabled = $this->shipping_was_enabled;
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Data provider with the gateway classes using the trait.
	 *
	 * @return array
	 */
	public function gateway_classes(): array {
		return array(
			'cod'    => array( WC_Gateway_COD::class ),
			'bacs'   => array( WC_Gateway_BACS::class ),
			'cheque' => array( WC_Gateway_Cheque::class ),
		);
	}

	/**
	 * @testdox Should add the shipping method restriction fields to the gateway settings form.
	 * @dataProvider gateway_classes
	 *
	 * @param string $gateway_class Gateway class name.
	 */
	public function test_form_fields_include_shipping_method_restrictions( string $gateway_class ): void {
		$form_fields = ( new $gateway_class() )->get_form_fields();

		$this->assertSame( 'multiselect', $form_fields['enable_for_methods']['type'] ?? null, "$gateway_class should have the enable_for_methods multiselect" );
		$this->assertSame( 'checkbox', $form_fields['enable_for_virtual']['type'] ?? null, "$gateway_class should have the enable_for_virtual checkbox" );
		$this->assertSame( 'yes', $form_fields['enable_for_virtual']['default'], 'Virtual orders should be accepted by default' );
	}

	/**
	 * @testdox Should default to no restriction when the gateway settings were saved before the fields existed.
	 * @dataProvider gateway_classes
	 *
	 * @param string $gateway_class Gateway class name.
	 */
	public function test_defaults_to_no_restriction_for_previously_saved_settings( string $gateway_class ): void {
		$gateway = $this->create_gateway(
			$gateway_class,
			array(
				'enabled' => 'yes',
				'title'   => 'Offline payment',
			)
		);
		$this->fill_cart( 'free_shipping' );

		$this->assertSame( array(), $gateway->enable_for_methods, 'No shipping method restriction should be applied' );
		$this->assertTrue( $gateway->enable_for_virtual, 'Virtual orders should be accepted' );
		$this->assertTrue( $gateway->is_available(), 'The gateway should be available for any shipping method' );
	}

	/**
	 * @testdox Should only be available when a selected shipping method matches the restriction.
	 * @dataProvider availability_scenarios
	 *
	 * @param array  $enable_for_methods Restriction saved in the settings, as symbolic rate names or bare method ids.
	 * @param string $chosen_rate        Symbolic name of the shipping rate selected in the cart.
	 * @param bool   $expected           Expected availability.
	 */
	public function test_is_available_respects_shipping_method_restrictions( array $enable_for_methods, string $chosen_rate, bool $expected ): void {
		$gateway = $this->create_gateway(
			WC_Gateway_BACS::class,
			array(
				'enabled'            => 'yes',
				'enable_for_methods' => array_map( array( $this, 'resolve_rate_id' ), $enable_for_methods ),
				'enable_for_virtual' => 'yes',
			)
		);
		$this->fill_cart( $chosen_rate );

		$this->assertSame( $expected, $gateway->is_available() );
	}

	/**
	 * Data provider for the shipping method restriction scenarios.
	 *
	 * Rates are named symbolically because the zone's instance ids are only known once it is created in setUp().
	 *
	 * @return array
	 */
	public function availability_scenarios(): array {
		return array(
			'matching instance'          => array( array( 'flat_rate_a' ), 'flat_rate_a', true ),
			'other instance'             => array( array( 'flat_rate_a' ), 'flat_rate_b', false ),
			'other method'               => array( array( 'flat_rate_a' ), 'free_shipping', false ),
			'any instance of the method' => array( array( 'flat_rate' ), 'flat_rate_b', true ),
			'one of several'             => array( array( 'free_shipping', 'flat_rate_a' ), 'flat_rate_a', true ),
		);
	}

	/**
	 * @testdox Should honor the "Accept for virtual orders" setting when the cart needs no shipping.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $enable_for_virtual Saved setting value.
	 * @param bool   $expected           Expected availability.
	 */
	public function test_is_available_respects_enable_for_virtual( string $enable_for_virtual, bool $expected ): void {
		$gateway = $this->create_gateway(
			WC_Gateway_Cheque::class,
			array(
				'enabled'            => 'yes',
				'enable_for_methods' => array( $this->rate_ids['flat_rate_a'] ),
				'enable_for_virtual' => $enable_for_virtual,
			)
		);
		$this->fill_cart( null );

		$this->assertSame( $expected, $gateway->is_available() );
	}

	/**
	 * @testdox Should evaluate the cart's shipping method when the request is not paying for an order.
	 * @testWith ["0", "flat_rate_a", true]
	 *           ["0", "flat_rate_b", false]
	 *           ["999999999", "flat_rate_b", false]
	 *
	 * @param string $order_pay   Order-pay query var value sent with the request.
	 * @param string $chosen_rate Symbolic name of the shipping rate selected in the cart.
	 * @param bool   $expected    Expected availability.
	 */
	public function test_is_available_uses_the_cart_when_order_pay_does_not_resolve( string $order_pay, string $chosen_rate, bool $expected ): void {
		$gateway = $this->create_gateway(
			WC_Gateway_COD::class,
			array(
				'enabled'            => 'yes',
				'enable_for_methods' => array( $this->rate_ids['flat_rate_a'] ),
				'enable_for_virtual' => 'yes',
			)
		);
		$this->fill_cart( $chosen_rate );
		$this->set_order_pay_query_var( $order_pay );

		$this->assertSame( $expected, $gateway->is_available() );
	}

	/**
	 * @testdox Should treat a cart that needs shipping as physical when the request is not paying for an order.
	 */
	public function test_is_available_does_not_treat_the_cart_as_virtual_when_order_pay_does_not_resolve(): void {
		$gateway = $this->create_gateway(
			WC_Gateway_BACS::class,
			array(
				'enabled'            => 'yes',
				'enable_for_methods' => array( $this->rate_ids['flat_rate_a'] ),
				'enable_for_virtual' => 'no',
			)
		);
		$this->fill_cart( 'flat_rate_a' );
		$this->set_order_pay_query_var( '0' );

		$this->assertTrue( $gateway->is_available() );
	}

	/**
	 * @testdox Should evaluate the order's shipping methods instead of the cart's on the order-pay page.
	 * @testWith ["flat_rate_a", "yes", true]
	 *           ["flat_rate_b", "yes", false]
	 *           [null, "yes", true]
	 *           [null, "no", false]
	 *
	 * @param string|null $order_rate         Symbolic name of the order's shipping rate, or null for an order without shipping.
	 * @param string      $enable_for_virtual Saved setting value.
	 * @param bool        $expected           Expected availability.
	 */
	public function test_is_available_uses_order_shipping_methods_on_order_pay_page( ?string $order_rate, string $enable_for_virtual, bool $expected ): void {
		$gateway = $this->create_gateway(
			WC_Gateway_Cheque::class,
			array(
				'enabled'            => 'yes',
				'enable_for_methods' => array( $this->rate_ids['flat_rate_a'] ),
				'enable_for_virtual' => $enable_for_virtual,
			)
		);
		// The cart would allow the gateway on its own, so a pass here proves the order context was used.
		$this->fill_cart( 'flat_rate_a' );
		$this->set_order_pay_query_var( (string) $this->create_order_with_shipping( $order_rate )->get_id() );

		$this->assertSame( $expected, $gateway->is_available() );
	}

	/**
	 * @testdox Should not consult the cart when the gateway is disabled.
	 * @dataProvider gateway_classes
	 *
	 * @param string $gateway_class Gateway class name.
	 */
	public function test_is_available_returns_false_when_disabled( string $gateway_class ): void {
		$gateway = $this->create_gateway( $gateway_class, array( 'enabled' => 'no' ) );
		$this->fill_cart( 'flat_rate_a' );

		$needs_shipping_calls = 0;
		add_filter(
			'woocommerce_cart_needs_shipping',
			function ( $needs_shipping ) use ( &$needs_shipping_calls ) {
				++$needs_shipping_calls;
				return $needs_shipping;
			}
		);

		$this->assertFalse( $gateway->is_available() );
		$this->assertSame( 0, $needs_shipping_calls, 'The cart should not be queried for disabled gateways' );
	}

	/**
	 * @testdox Should load the shipping method options only when the gateway settings page is requested.
	 * @dataProvider gateway_classes
	 *
	 * @param string $gateway_class Gateway class name.
	 */
	public function test_shipping_method_options_load_only_on_settings_page( string $gateway_class ): void {
		$this->assertEmpty( ( new $gateway_class() )->get_form_fields()['enable_for_methods']['options'], 'Options should not be loaded outside the settings page' );

		set_current_screen( 'woocommerce_page_wc-settings' );
		$_REQUEST['page']    = 'wc-settings';
		$_REQUEST['tab']     = 'checkout';
		$_REQUEST['section'] = $gateway_class::ID;

		try {
			$options = ( new $gateway_class() )->get_form_fields()['enable_for_methods']['options'];
		} finally {
			$GLOBALS['current_screen'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			unset( $_REQUEST['page'], $_REQUEST['tab'], $_REQUEST['section'] );
		}

		$this->assertNotEmpty( $options, 'Options should be loaded on the gateway settings page' );
	}

	/**
	 * @testdox Should share the shipping method options between gateways for the rest of the request and refresh them when a zone changes.
	 */
	public function test_shipping_method_options_are_cached_per_request_and_invalidated_on_zone_change(): void {
		$cod_options = ( new WC_Gateway_COD() )->get_shipping_method_options();
		$this->assertArrayHasKey( $this->rate_ids['flat_rate_a'], $cod_options['Flat rate'] );

		$zone_query_count = 0;
		$count_queries    = function ( $query ) use ( &$zone_query_count ) {
			if ( false !== strpos( $query, 'woocommerce_shipping_zone_methods' ) ) {
				++$zone_query_count;
			}
			return $query;
		};
		add_filter( 'query', $count_queries );

		try {
			$bacs_options = ( new WC_Gateway_BACS() )->get_shipping_method_options();
		} finally {
			remove_filter( 'query', $count_queries );
		}

		$this->assertSame( $cod_options, $bacs_options );
		$this->assertSame( 0, $zone_query_count, 'Another gateway should reuse the options loaded earlier in the request' );

		$new_instance_id = $this->zone->add_shipping_method( 'local_pickup' );

		$this->assertArrayHasKey( 'local_pickup:' . $new_instance_id, ( new WC_Gateway_Cheque() )->get_shipping_method_options()['Local pickup'], 'Changing a zone should refresh the options' );
	}

	/**
	 * @testdox Should keep the classic and block local pickup methods under the same option group since they share a title.
	 */
	public function test_shipping_method_options_group_block_and_classic_local_pickup_together(): void {
		$local_pickup_instance_id = $this->zone->add_shipping_method( 'local_pickup' );

		// The block checkout registers its own "Local pickup" method after the classic one; keep the registration to this test.
		$register_pickup_location = function ( $methods ) {
			$methods['pickup_location'] = new PickupLocation();
			return $methods;
		};
		add_filter( 'woocommerce_shipping_methods', $register_pickup_location );

		try {
			WC()->shipping()->load_shipping_methods();
			$options = ( new WC_Gateway_COD() )->get_shipping_method_options();
		} finally {
			remove_filter( 'woocommerce_shipping_methods', $register_pickup_location );
			WC()->shipping()->load_shipping_methods();
		}

		$this->assertArrayHasKey( 'local_pickup', $options['Local pickup'], 'The classic "any" option should survive registering the block method' );
		$this->assertArrayHasKey( 'local_pickup:' . $local_pickup_instance_id, $options['Local pickup'], 'The classic instance should survive registering the block method' );
		$this->assertArrayHasKey( 'pickup_location', $options['Local pickup'], 'The block "any" option should be listed too' );
	}

	/**
	 * Save the given settings for a gateway and instantiate it.
	 *
	 * @param string $gateway_class Gateway class name.
	 * @param array  $settings      Settings to save.
	 * @return WC_Payment_Gateway
	 */
	private function create_gateway( string $gateway_class, array $settings ): WC_Payment_Gateway {
		update_option( 'woocommerce_' . $gateway_class::ID . '_settings', $settings );

		return new $gateway_class();
	}

	/**
	 * Translate a symbolic rate name from the data providers into the zone's real rate id.
	 *
	 * Bare method ids such as "flat_rate" (any instance) are passed through unchanged.
	 *
	 * @param string $name Symbolic rate name or method id.
	 * @return string
	 */
	private function resolve_rate_id( string $name ): string {
		return $this->rate_ids[ $name ] ?? $name;
	}

	/**
	 * Set the order-pay endpoint context, as WC_Query::parse_request() does when routing a request.
	 *
	 * @param string|null $value Query var value, or null to leave the endpoint context.
	 */
	private function set_order_pay_query_var( ?string $value ): void {
		global $wp;

		if ( null === $value ) {
			unset( $wp->query_vars['order-pay'] );
			unset( $GLOBALS['wp_query']->query_vars['order-pay'] );
			return;
		}

		$wp->query_vars['order-pay'] = $value;
		set_query_var( 'order-pay', $value );
	}

	/**
	 * Create an order with a shipping line for the given rate, or without shipping.
	 *
	 * @param string|null $rate Symbolic name of the rate, or null for no shipping line.
	 * @return WC_Order
	 */
	private function create_order_with_shipping( ?string $rate ): WC_Order {
		$order = new WC_Order();

		if ( null !== $rate ) {
			list( $method_id, $instance_id ) = explode( ':', $this->rate_ids[ $rate ] );

			$shipping_item = new WC_Order_Item_Shipping();
			$shipping_item->set_method_id( $method_id );
			$shipping_item->set_instance_id( $instance_id );
			$order->add_item( $shipping_item );
		}

		$order->save();

		return $order;
	}

	/**
	 * Put a product in the real cart and select a shipping rate for it.
	 *
	 * @param string|null $chosen_rate Symbolic name of the rate to select, or null for a virtual-only cart that needs no shipping.
	 */
	private function fill_cart( ?string $chosen_rate ): void {
		WC()->cart->empty_cart();

		$product = WC_Helper_Product::create_simple_product( true, array( 'virtual' => null === $chosen_rate ) );
		WC()->cart->add_to_cart( $product->get_id() );

		if ( null === $chosen_rate ) {
			WC()->cart->calculate_totals();
			$this->assertFalse( WC()->cart->needs_shipping(), 'Precondition: a virtual-only cart should not need shipping' );
			return;
		}

		WC()->session->set( 'chosen_shipping_methods', array( $this->rate_ids[ $chosen_rate ] ) );
		WC()->cart->calculate_totals();

		$this->assertSame(
			array( $this->rate_ids[ $chosen_rate ] ),
			array_map(
				function ( $rate ) {
					return $rate->get_id();
				},
				array_values( WC()->cart->get_shipping_methods() )
			),
			'Precondition: the real cart should resolve the chosen shipping rate'
		);
	}
}
