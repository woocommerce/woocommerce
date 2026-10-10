<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\Shipping;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Shipping\ShippingController;

/**
 * Unit tests for the PatternRegistry class.
 */
class ShippingControllerTest extends \WC_Unit_Test_Case {
	/**
	 * The registry instance.
	 *
	 * @var ShippingController $controller
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
	public function setUp(): void {
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
	public function tearDown(): void {
		global $woocommerce;

		update_option( 'woocommerce_checkout_page_id', $this->original_checkout_page_id );
		wp_delete_post( $this->block_checkout_page_id );
		remove_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );
		$woocommerce = $this->backup_wc;
		parent::tearDown();
	}

	/**
	 * Test that the has_full_shipping_address method returns correctly.
	 */
	public function test_has_full_shipping_address_returns_correctly() {
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
	 * Test that register_local_pickup handles missing WC()->shipping and other dependencies gracefully.
	 */
	public function test_register_local_pickup_also_handles_missing_dependencies() {
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
	 * Overrides the WC logger.
	 *
	 * @return mixed
	 */
	public function override_wc_logger() {
		return $this->mock_logger;
	}

	/**
	 * Build a shipping package the way WC_Shipping hands one to the filters.
	 *
	 * @param array $rate_keys Rate ids, e.g. array( 'flat_rate:1', 'pickup_location:0' ).
	 * @return array
	 */
	private function package_offering( array $rate_keys ): array {
		$rates = array();

		foreach ( $rate_keys as $rate_key ) {
			$method_id          = current( explode( ':', $rate_key ) );
			$rates[ $rate_key ] = new \WC_Shipping_Rate( $rate_key, ucfirst( $method_id ), '10', array(), $method_id );
		}

		return array( 'rates' => $rates );
	}

	/**
	 * Put the shopper on the block checkout. Only remove_shipping_if_no_address() asks which cart
	 * it is; its sibling applies everywhere, so tests of that one say which cart they are on only
	 * because the pair below varies exactly that.
	 */
	private function shopper_is_on_the_block_checkout(): void {
		WC()->cart->cart_context = 'store-api';
	}

	/**
	 * Give the customer an address complete enough to ship to.
	 */
	private function customer_enters_a_full_address(): void {
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'CA' );
		WC()->customer->set_shipping_city( 'Beverly Hills' );
		WC()->customer->set_shipping_postcode( '90210' );

		$this->assertTrue(
			WC()->customer->has_full_shipping_address(),
			'The fixture address should count as complete, or a test expecting delivery to survive proves nothing.'
		);
	}

	/**
	 * The setting is off by default, and the screen only promises to hide costs once it is switched on.
	 *
	 * @testdox With the setting off, a shopper with no address still sees every option.
	 *
	 * @testWith [null]
	 *           ["no"]
	 *
	 * @param string|null $stored What the option holds, null meaning it was never saved.
	 */
	public function test_nothing_is_hidden_while_the_setting_is_off( ?string $stored ): void {
		$this->shopper_is_on_the_block_checkout();

		if ( null === $stored ) {
			delete_option( 'woocommerce_shipping_cost_requires_address' );
		} else {
			update_option( 'woocommerce_shipping_cost_requires_address', $stored );
		}

		$packages = $this->shipping_controller->remove_shipping_if_no_address(
			array( $this->package_offering( array( 'flat_rate:1', 'pickup_location:0' ) ) )
		);

		$this->assertSame(
			array( 'flat_rate:1', 'pickup_location:0' ),
			array_keys( $packages[0]['rates'] ),
			'Nothing should be hidden while the merchant has not asked for it.'
		);
	}

	/**
	 * "Hide shipping costs until an address is entered." Collection needs no address, so it stays.
	 *
	 * @testdox With the setting on and no address, delivery is hidden and collection stays.
	 */
	public function test_delivery_is_hidden_until_an_address_is_entered(): void {
		$this->shopper_is_on_the_block_checkout();
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );

		$packages = $this->shipping_controller->remove_shipping_if_no_address(
			array( $this->package_offering( array( 'flat_rate:1', 'free_shipping:2', 'local_pickup:3', 'pickup_location:0' ) ) )
		);

		$this->assertSame(
			array( 'local_pickup:3', 'pickup_location:0' ),
			array_keys( $packages[0]['rates'] ),
			'Only what the shopper can collect without giving an address should survive.'
		);
	}

	/**
	 * @testdox Completing the address brings the delivery options back.
	 */
	public function test_completing_the_address_brings_delivery_back(): void {
		$this->shopper_is_on_the_block_checkout();
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->customer_enters_a_full_address();

		$packages = $this->shipping_controller->remove_shipping_if_no_address(
			array( $this->package_offering( array( 'flat_rate:1', 'pickup_location:0' ) ) )
		);

		$this->assertSame(
			array( 'flat_rate:1', 'pickup_location:0' ),
			array_keys( $packages[0]['rates'] ),
			'An address has been entered, so there is nothing left to wait for.'
		);
	}

	/**
	 * A half-entered address is not an address. Which fields count is the country's business, and
	 * `has_full_shipping_address()` is what decides it.
	 *
	 * @testdox Delivery stays hidden while the address is only half entered.
	 *
	 * @dataProvider provider_incomplete_addresses
	 *
	 * @param array  $address What the shopper has typed so far.
	 * @param string $why     What makes this address incomplete.
	 */
	public function test_delivery_stays_hidden_while_the_address_is_incomplete( array $address, string $why ): void {
		$this->shopper_is_on_the_block_checkout();
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );

		WC()->customer->set_shipping_country( $address['country'] );
		WC()->customer->set_shipping_state( $address['state'] );
		WC()->customer->set_shipping_city( $address['city'] );
		WC()->customer->set_shipping_postcode( $address['postcode'] );

		$this->assertFalse(
			WC()->customer->has_full_shipping_address(),
			'The fixture address should be incomplete, or this test proves nothing: ' . $why
		);

		$packages = $this->shipping_controller->remove_shipping_if_no_address(
			array( $this->package_offering( array( 'flat_rate:1', 'pickup_location:0' ) ) )
		);

		$this->assertSame(
			array( 'pickup_location:0' ),
			array_keys( $packages[0]['rates'] ),
			'Delivery should stay hidden until the address is finished: ' . $why
		);
	}

	/**
	 * Addresses a shopper can be part way through typing.
	 *
	 * @return array
	 */
	public function provider_incomplete_addresses(): array {
		return array(
			'a country and nothing else'  => array(
				array(
					'country'  => 'US',
					'state'    => '',
					'city'     => '',
					'postcode' => '',
				),
				'only the country has been chosen',
			),
			'everything but the postcode' => array(
				array(
					'country'  => 'US',
					'state'    => 'CA',
					'city'     => 'Beverly Hills',
					'postcode' => '',
				),
				'the US asks for a postcode and none has been typed',
			),
			'everything but the state'    => array(
				array(
					'country'  => 'US',
					'state'    => '',
					'city'     => 'Beverly Hills',
					'postcode' => '90210',
				),
				'the US asks for a state and none has been chosen',
			),
		);
	}

	/**
	 * A shopper can clear a field they already filled in, and what they had typed before is not a
	 * reason to keep showing delivery.
	 *
	 * @testdox Clearing part of the address hides delivery again.
	 */
	public function test_clearing_part_of_the_address_hides_delivery_again(): void {
		$this->shopper_is_on_the_block_checkout();
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->customer_enters_a_full_address();

		$packages = $this->shipping_controller->remove_shipping_if_no_address(
			array( $this->package_offering( array( 'flat_rate:1', 'pickup_location:0' ) ) )
		);
		$this->assertSame(
			array( 'flat_rate:1', 'pickup_location:0' ),
			array_keys( $packages[0]['rates'] ),
			'Delivery should be on offer while the address is complete.'
		);

		WC()->customer->set_shipping_postcode( '' );

		$packages = $this->shipping_controller->remove_shipping_if_no_address(
			array( $this->package_offering( array( 'flat_rate:1', 'pickup_location:0' ) ) )
		);
		$this->assertSame(
			array( 'pickup_location:0' ),
			array_keys( $packages[0]['rates'] ),
			'Once the postcode is taken back out, delivery should be hidden again.'
		);
	}

	/**
	 * What makes an address complete is the country's business. In a country that asks for neither a
	 * postcode nor a state, the shopper has finished as soon as the city is in.
	 *
	 * @testdox In a country that asks for no postcode, a city is enough to bring delivery back.
	 */
	public function test_a_country_that_asks_for_no_postcode_needs_no_postcode(): void {
		$this->shopper_is_on_the_block_checkout();
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );

		WC()->customer->set_shipping_country( 'AE' );
		WC()->customer->set_shipping_city( 'Dubai' );

		$this->assertTrue(
			WC()->customer->has_full_shipping_address(),
			'The UAE asks for neither a postcode nor a state, so this address should count as complete.'
		);

		$packages = $this->shipping_controller->remove_shipping_if_no_address(
			array( $this->package_offering( array( 'flat_rate:1', 'pickup_location:0' ) ) )
		);

		$this->assertSame(
			array( 'flat_rate:1', 'pickup_location:0' ),
			array_keys( $packages[0]['rates'] ),
			'Nothing should be held back for fields this country never asks for.'
		);
	}

	/**
	 * Stores change what their countries ask for through `woocommerce_get_country_locale`, and that
	 * has to move what counts as a finished address here too.
	 *
	 * @testdox A field an extension made optional no longer holds delivery back.
	 */
	public function test_a_field_an_extension_made_optional_no_longer_holds_delivery_back(): void {
		$this->shopper_is_on_the_block_checkout();
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );

		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_city( 'Beverly Hills' );
		WC()->customer->set_shipping_state( 'CA' );

		$this->assertFalse(
			WC()->customer->has_full_shipping_address(),
			'Ordinarily the US asks for a postcode, or this test would prove nothing.'
		);

		add_filter(
			'woocommerce_get_country_locale',
			static function ( $locale ) {
				$locale['US']['postcode']['required'] = false;
				return $locale;
			}
		);
		// The locale is cached on first read, and the filter is attached after that read here.
		WC()->countries->locale = array();

		$packages = $this->shipping_controller->remove_shipping_if_no_address(
			array( $this->package_offering( array( 'flat_rate:1', 'pickup_location:0' ) ) )
		);

		$this->assertSame(
			array( 'flat_rate:1', 'pickup_location:0' ),
			array_keys( $packages[0]['rates'] ),
			'With the postcode made optional, the address is finished and delivery should show.'
		);
	}

	/**
	 * A locale can hide a field outright. A shopper is never shown it, so it cannot be something
	 * they have left unfinished, whatever the same locale says about it being required.
	 *
	 * @testdox A field the locale hides does not hold delivery back, even when marked required.
	 *
	 * @dataProvider provider_locales_that_hide_the_postcode
	 *
	 * @param callable $hide_the_postcode Attaches the filter that hides it.
	 * @param string   $why               Which locale entry is doing the hiding.
	 */
	public function test_a_field_the_locale_hides_does_not_hold_delivery_back( callable $hide_the_postcode, string $why ): void {
		$this->shopper_is_on_the_block_checkout();
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );

		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_city( 'Beverly Hills' );
		WC()->customer->set_shipping_state( 'CA' );

		$this->assertFalse(
			WC()->customer->has_full_shipping_address(),
			'Ordinarily the US asks for a postcode, or this test would prove nothing.'
		);

		$hide_the_postcode();
		// The locale is cached on first read, and the filter is attached after that read here.
		WC()->countries->locale = array();

		$packages = $this->shipping_controller->remove_shipping_if_no_address(
			array( $this->package_offering( array( 'flat_rate:1', 'pickup_location:0' ) ) )
		);

		$this->assertSame(
			array( 'flat_rate:1', 'pickup_location:0' ),
			array_keys( $packages[0]['rates'] ),
			'A field the shopper is never shown cannot be one they have left empty: ' . $why
		);
	}

	/**
	 * The two places a hidden flag can come from. They have separate filters, because
	 * `WC_Countries::get_country_locale()` rebuilds its default entry after the country one.
	 *
	 * @return array
	 */
	public function provider_locales_that_hide_the_postcode(): array {
		return array(
			'hidden for this country'  => array(
				static function () {
					add_filter(
						'woocommerce_get_country_locale',
						static function ( $locale ) {
							$locale['US']['postcode']['hidden']   = true;
							$locale['US']['postcode']['required'] = true;
							return $locale;
						}
					);
				},
				'the US locale hides it',
			),
			'hidden for every country' => array(
				static function () {
					add_filter(
						'woocommerce_get_country_locale_default',
						static function ( $fields ) {
							$fields['postcode']['hidden']   = true;
							$fields['postcode']['required'] = true;
							return $fields;
						}
					);
				},
				'the default locale hides it',
			),
		);
	}
}
