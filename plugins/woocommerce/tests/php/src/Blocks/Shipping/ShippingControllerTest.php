<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\Shipping;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Shipping\ShippingController;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;

/**
 * Unit tests for the ShippingController class.
 */
class ShippingControllerTest extends \WP_UnitTestCase {
	/**
	 * The shipping controller under test.
	 *
	 * @var ShippingController
	 */
	private ShippingController $shipping_controller;

	/**
	 * The old checkout page ID.
	 *
	 * @var int $original_checkout_page_id
	 */
	private $original_checkout_page_id;

	/**
	 * The new checkout page ID.
	 *
	 * @var int $block_checkout_page_id
	 */
	private $block_checkout_page_id;


	/**
	 * Mock logger instance.
	 *
	 * @var \WC_Logger_Interface $mock_logger
	 */
	private $mock_logger;

	/**
	 * Backup WC instance.
	 *
	 * @var \WC $backup_wc
	 */
	private $backup_wc;

	/**
	 * Initialize the registry instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Setup mock logger.
		$this->mock_logger = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();
		add_filter(
			'woocommerce_logging_class',
			array( $this, 'override_wc_logger' )
		);

		// Backup WC instance.
		$this->backup_wc = WC();

		// Local pickup only works with the checkout block.
		$this->original_checkout_page_id = get_option( 'woocommerce_checkout_page_id' );
		$this->block_checkout_page_id    = $this->factory->post->create(
			array(
				'post_type'    => 'page',
				'post_title'   => 'Checkout',
				'post_content' => '<!-- wp:woocommerce/checkout /-->',
				'post_status'  => 'publish',
			)
		);
		update_option( 'woocommerce_checkout_page_id', $this->block_checkout_page_id );

		$this->shipping_controller = new ShippingController(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class )
		);
		WC()->customer->set_shipping_postcode( '' );
		WC()->customer->set_shipping_city( '' );
		WC()->customer->set_shipping_state( '' );
		WC()->customer->set_shipping_country( '' );
	}

	/**
	 * Tear down the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		global $woocommerce;

		update_option( 'woocommerce_checkout_page_id', $this->original_checkout_page_id );
		wp_delete_post( $this->block_checkout_page_id );
		remove_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );
		$woocommerce = $this->backup_wc;
		parent::tearDown();
	}

	/**
	 * @testdox A shipping address counts as complete only with a country, city, postcode and any state the locale requires.
	 */
	public function test_has_full_shipping_address_requires_country_city_postcode_and_any_required_state() {
		// With GB, state is not required. Test it returns false with only a country, nothing else.
		WC()->customer->set_shipping_country( 'GB' );
		$this->assertFalse( WC()->customer->has_full_shipping_address() );

		WC()->customer->set_shipping_postcode( 'PR1 4SS' );
		$this->assertFalse( WC()->customer->has_full_shipping_address() );

		WC()->customer->set_shipping_city( 'Preston' );
		$this->assertTrue( WC()->customer->has_full_shipping_address() );

		// Now switch to US, ensure that it returns false because state is not input.
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_postcode( '90210' );
		WC()->customer->set_shipping_city( 'Beverly Hills' );
		$this->assertFalse( WC()->customer->has_full_shipping_address() );

		// Now add state, ensure that it returns true.
		WC()->customer->set_shipping_state( 'CA' );
		$this->assertTrue( WC()->customer->has_full_shipping_address() );

		// Now add a filter to set US state to optional, and UK state to required.
		add_filter(
			'woocommerce_get_country_locale',
			function ( $locale ) {
				$locale['US']['state']['required']      = false;
				$locale['GB']['state']['required']      = true;
				$locale['default']['state']['required'] = false;
				return $locale;
			}
		);

		// Unset the cached locale because this filter runs later. Typically, that sort of filter would be applied before
		// the locale is cached, but in unit tests the site is already set up before the test runs.
		unset( WC()->countries->locale );

		// Test that US state is now optional.
		WC()->customer->set_shipping_state( '' );
		$this->assertTrue( WC()->customer->has_full_shipping_address() );

		// Test that UK state is now required.
		WC()->customer->set_shipping_country( 'GB' );
		WC()->customer->set_shipping_postcode( 'PR1 4SS' );
		$this->assertFalse( WC()->customer->has_full_shipping_address() );

		// Finally test that it passes when an ordinarily optional prop filtered to be required is provided.
		WC()->customer->set_shipping_state( 'Lancashire' );
		$this->assertTrue( WC()->customer->has_full_shipping_address() );

		// Remove filter.
		remove_all_filters( 'woocommerce_get_country_locale' );
	}

	/**
	 * @testdox register_local_pickup survives missing shipping dependencies and logs when it cannot register the method.
	 */
	public function test_register_local_pickup_survives_missing_shipping_dependencies_and_logs_when_it_cannot_register() {
		// Test that the method does not throw exceptions without missing dependencies.
		$this->shipping_controller->register_local_pickup();
		$this->assertTrue( true, 'Method did not throw exceptions without missing dependencies' );

		// Test that the method does not throw exceptions with missing shipping.
		WC()->shipping = null;
		$this->shipping_controller->register_local_pickup();
		$this->assertTrue( true, 'Method did not throw exceptions with missing shipping' );

		// Test that the error is logged when WC()->shipping->register_shipping_method is not available.
		$this->mock_logger->expects( $this->once() )
					->method( 'error' )
					->with(
						'Error registering pickup location: WC()->shipping->register_shipping_method is not available',
						array( 'source' => 'shipping-controller' )
					);

		// Test that the method does not throw exceptions with missing WC object.
		global $woocommerce;
		$incomplete_wc = new \stdClass(); // Object without shipping property.
		$woocommerce   = $incomplete_wc;

		$this->shipping_controller->register_local_pickup();
		$this->assertTrue( true, 'Method did not throw exceptions with missing WC object' );
	}

	/**
	 * @testdox filter_order_tax_location returns the chosen pickup location address for local pickup orders.
	 */
	public function test_filter_order_tax_location_returns_pickup_location_address(): void {
		$pickup_address = array(
			'country'  => 'US',
			'state'    => 'CA',
			'postcode' => '90210',
			'city'     => 'Beverly Hills',
		);

		$order         = new \WC_Order();
		$shipping_item = new \WC_Order_Item_Shipping();
		// 'local_pickup' is always recognised as a local pickup method id; in production the block 'pickup_location'
		// method is what writes the _pickup_location_address meta onto the shipping line at purchase time.
		$shipping_item->set_method_id( 'local_pickup' );
		$shipping_item->set_method_title( 'Local pickup' );
		$shipping_item->add_meta_data( '_pickup_location_address', $pickup_address );
		$order->add_item( $shipping_item );
		$order->save();

		$order = wc_get_order( $order->get_id() );

		$default_location = array(
			'country'  => 'GB',
			'state'    => '',
			'postcode' => 'PR1 4SS',
			'city'     => 'Preston',
		);

		$location = $this->shipping_controller->filter_order_tax_location( $default_location, $order );

		$this->assertSame( $pickup_address['country'], $location['country'], 'Tax country should match the pickup location.' );
		$this->assertSame( $pickup_address['state'], $location['state'], 'Tax state should match the pickup location.' );
		$this->assertSame( $pickup_address['postcode'], $location['postcode'], 'Tax postcode should match the pickup location.' );
		$this->assertSame( $pickup_address['city'], $location['city'], 'Tax city should match the pickup location.' );
	}

	/**
	 * @testdox filter_order_tax_location resolves the pickup location for legacy_local_pickup orders even when the method is no longer registered.
	 */
	public function test_filter_order_tax_location_returns_pickup_location_for_legacy_method(): void {
		$pickup_address = array(
			'country'  => 'US',
			'state'    => 'CA',
			'postcode' => '90210',
			'city'     => 'Beverly Hills',
		);

		$order         = new \WC_Order();
		$shipping_item = new \WC_Order_Item_Shipping();
		// 'legacy_local_pickup' is in the canonical woocommerce_local_pickup_methods list but is not returned by
		// LocalPickupUtils::get_local_pickup_method_ids(), so it stands in for any method no longer registered.
		$shipping_item->set_method_id( 'legacy_local_pickup' );
		$shipping_item->set_method_title( 'Local pickup' );
		$shipping_item->add_meta_data( '_pickup_location_address', $pickup_address );
		$order->add_item( $shipping_item );
		$order->save();

		$order = wc_get_order( $order->get_id() );

		$default_location = array(
			'country'  => 'GB',
			'state'    => '',
			'postcode' => 'PR1 4SS',
			'city'     => 'Preston',
		);

		$location = $this->shipping_controller->filter_order_tax_location( $default_location, $order );

		$this->assertSame( $pickup_address['country'], $location['country'], 'Tax country should match the pickup location for legacy methods.' );
		$this->assertSame( $pickup_address['state'], $location['state'], 'Tax state should match the pickup location for legacy methods.' );
		$this->assertSame( $pickup_address['postcode'], $location['postcode'], 'Tax postcode should match the pickup location for legacy methods.' );
		$this->assertSame( $pickup_address['city'], $location['city'], 'Tax city should match the pickup location for legacy methods.' );
	}

	/**
	 * @testdox filter_order_tax_location leaves the resolved location untouched for non local pickup orders.
	 */
	public function test_filter_order_tax_location_ignores_non_local_pickup_orders(): void {
		$order         = new \WC_Order();
		$shipping_item = new \WC_Order_Item_Shipping();
		$shipping_item->set_method_id( 'flat_rate' );
		$shipping_item->set_method_title( 'Flat rate' );
		$order->add_item( $shipping_item );
		$order->save();

		$order = wc_get_order( $order->get_id() );

		$default_location = array(
			'country'  => 'GB',
			'state'    => '',
			'postcode' => 'PR1 4SS',
			'city'     => 'Preston',
		);

		$location = $this->shipping_controller->filter_order_tax_location( $default_location, $order );

		$this->assertSame( $default_location, $location, 'Non local pickup orders should keep the resolved tax location.' );
	}

	/**
	 * @testdox filter_order_tax_location falls back to the resolved location when no pickup address is captured.
	 */
	public function test_filter_order_tax_location_falls_back_without_pickup_address(): void {
		$order         = new \WC_Order();
		$shipping_item = new \WC_Order_Item_Shipping();
		$shipping_item->set_method_id( 'local_pickup' );
		$shipping_item->set_method_title( 'Local pickup' );
		$order->add_item( $shipping_item );
		$order->save();

		$order = wc_get_order( $order->get_id() );

		$default_location = array(
			'country'  => 'GB',
			'state'    => '',
			'postcode' => 'PR1 4SS',
			'city'     => 'Preston',
		);

		$location = $this->shipping_controller->filter_order_tax_location( $default_location, $order );

		$this->assertSame( $default_location, $location, 'Without a captured pickup address the location should be unchanged.' );
	}

	/**
	 * @testdox Delivery rates are hidden only when the address setting is on and the address is incomplete, for $scenario.
	 * @dataProvider hide_shipping_until_address_provider
	 *
	 * @param string   $scenario          Scenario name.
	 * @param string   $requires_address  Value of the woocommerce_shipping_cost_requires_address option.
	 * @param bool     $has_full_address  Whether the customer has a complete shipping address.
	 * @param string   $cart_context      Cart context the packages are filtered in.
	 * @param string[] $expected_rate_ids Rate ids the package should keep, in order.
	 */
	public function test_delivery_rates_are_hidden_only_when_the_address_setting_is_on_and_the_address_is_incomplete(
		string $scenario,
		string $requires_address,
		bool $has_full_address,
		string $cart_context,
		array $expected_rate_ids
	): void {
		$original_option       = get_option( 'woocommerce_shipping_cost_requires_address', null );
		$original_address      = $this->capture_shipping_address();
		$original_cart_context = WC()->cart->cart_context;

		try {
			update_option( 'woocommerce_shipping_cost_requires_address', $requires_address );
			WC()->cart->cart_context = $cart_context;

			if ( $has_full_address ) {
				WC()->customer->set_shipping_country( 'US' );
				WC()->customer->set_shipping_state( 'CA' );
				WC()->customer->set_shipping_city( 'Beverly Hills' );
				WC()->customer->set_shipping_postcode( '90210' );
			}

			$this->assertSame(
				$has_full_address,
				WC()->customer->has_full_shipping_address(),
				"The customer address should be set up as {$scenario} describes."
			);
			$this->assertContains(
				'pickup_location',
				LocalPickupUtils::get_local_pickup_method_ids(),
				'The pickup_location method should be recognised as local pickup.'
			);

			$destination = array(
				'address_1' => '60 29th Street',
				'address_2' => '',
				'city'      => 'San Francisco',
				'state'     => 'CA',
				'postcode'  => '94110',
				'country'   => 'US',
			);
			$packages    = array(
				array(
					'destination' => $destination,
					'rates'       => array(
						'flat_rate:1'       => new \WC_Shipping_Rate( 'flat_rate:1', 'Flat rate', 5, array(), 'flat_rate', 1 ),
						'pickup_location:2' => new \WC_Shipping_Rate( 'pickup_location:2', 'Local pickup', 0, array(), 'pickup_location', 2 ),
					),
				),
			);

			$filtered = $this->shipping_controller->remove_shipping_if_no_address( $packages );

			$this->assertCount( 1, $filtered, "The filter should return the same single package for {$scenario}." );
			$this->assertSame(
				$destination,
				$filtered[0]['destination'],
				"The filter should leave the package destination untouched for {$scenario}."
			);
			$this->assertSame(
				$expected_rate_ids,
				array_keys( $filtered[0]['rates'] ),
				"The package should keep exactly the expected rates for {$scenario}."
			);
		} finally {
			if ( null === $original_option ) {
				delete_option( 'woocommerce_shipping_cost_requires_address' );
			} else {
				update_option( 'woocommerce_shipping_cost_requires_address', $original_option );
			}
			$this->restore_shipping_address( $original_address );
			WC()->cart->cart_context = $original_cart_context;
		}
	}

	/**
	 * @testdox init registers remove_shipping_if_no_address on woocommerce_shipping_packages after the package filter.
	 */
	public function test_init_registers_remove_shipping_if_no_address_at_priority_eleven(): void {
		$this->shipping_controller->init();

		$this->assertSame(
			11,
			has_filter( 'woocommerce_shipping_packages', array( $this->shipping_controller, 'remove_shipping_if_no_address' ) ),
			'Delivery rates can only be hidden once the packages have been built, so the filter has to run after filter_shipping_packages at priority 10.'
		);
	}

	/**
	 * Scenarios for the hide-shipping-until-address setting.
	 *
	 * @return array<string, array{string, string, bool, string, string[]}>
	 */
	public function hide_shipping_until_address_provider(): array {
		return array(
			'setting off, no address'                => array( 'setting off, no address', 'no', false, 'store-api', array( 'flat_rate:1', 'pickup_location:2' ) ),
			'setting on, no address'                 => array( 'setting on, no address', 'yes', false, 'store-api', array( 'pickup_location:2' ) ),
			'setting on, full address'               => array( 'setting on, full address', 'yes', true, 'store-api', array( 'flat_rate:1', 'pickup_location:2' ) ),
			'setting on, no address, shortcode cart' => array( 'setting on, no address, shortcode cart', 'yes', false, 'shortcode', array( 'flat_rate:1', 'pickup_location:2' ) ),
		);
	}

	/**
	 * Capture the customer's shipping address fields the shipping filter reads.
	 *
	 * @return array<string, string>
	 */
	private function capture_shipping_address(): array {
		$address = array();
		foreach ( array( 'country', 'state', 'city', 'postcode' ) as $field ) {
			$getter            = 'get_shipping_' . $field;
			$address[ $field ] = WC()->customer->$getter();
		}

		return $address;
	}

	/**
	 * Restore the customer's shipping address fields.
	 *
	 * @param array<string, string> $address Shipping address fields.
	 */
	private function restore_shipping_address( array $address ): void {
		foreach ( $address as $field => $value ) {
			$setter = 'set_shipping_' . $field;
			WC()->customer->$setter( $value );
		}
	}

	/**
	 * Overrides the WC logger.
	 *
	 * @return mixed
	 */
	public function override_wc_logger() {
		return $this->mock_logger;
	}
}
