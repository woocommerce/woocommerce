<?php
/**
 * CheckoutEventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;

/**
 * Tests for CheckoutEventTracker.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\CheckoutEventTracker
 */
class CheckoutEventTrackerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var CheckoutEventTracker
	 */
	private $sut;

	/**
	 * Mock session data collector.
	 *
	 * @var SessionDataCollector|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_collector;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure WooCommerce cart and session are available.
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		// Create mock.
		$this->mock_collector = $this->createMock( SessionDataCollector::class );

		// Create system under test.
		$this->sut = new CheckoutEventTracker();
		$this->sut->init( $this->mock_collector );
	}

	// ========================================
	// Checkout Page Load Tests
	// ========================================

	/**
	 * Test checkout page loaded collects data.
	 *
	 * @testdox track_checkout_page_loaded() collects session data with empty event data.
	 */
	public function test_track_checkout_page_loaded_collects_data(): void {
		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'checkout_page_loaded' ),
				$this->equalTo( array() )
			);

		$this->sut->track_checkout_page_loaded();
	}

	// ========================================
	// Blocks Checkout Tests
	// ========================================

	/**
	 * Test blocks checkout update collects data.
	 *
	 * @testdox track_blocks_checkout_update() collects session data with empty event data.
	 */
	public function test_track_blocks_checkout_update_collects_data(): void {
		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->equalTo( array() )
			);

		$this->sut->track_blocks_checkout_update();
	}

	// ========================================
	// Shortcode Checkout Tests
	// ========================================

	/**
	 * Test shortcode checkout field update collects data on billing country change.
	 *
	 * @testdox track_shortcode_checkout_field_update() collects data when billing country changes.
	 */
	public function test_track_shortcode_checkout_field_update_collects_data_on_billing_country_change(): void {
		$this->mock_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'CA' );

		$this->mock_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->callback(
					function ( $event_data ) {
						return isset( $event_data['action'] )
							&& 'field_update' === $event_data['action']
							&& isset( $event_data['billing_email'] )
							&& 'test@example.com' === $event_data['billing_email'];
					}
				)
			);

		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_last_name=Doe&billing_country=US';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test shortcode checkout field update extracts billing fields.
	 *
	 * @testdox track_shortcode_checkout_field_update() extracts billing fields correctly.
	 */
	public function test_track_shortcode_checkout_field_update_extracts_billing_fields(): void {
		$this->mock_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'CA' );

		$this->mock_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		$captured_event_data = null;
		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturnCallback(
				function ( $event_type, $event_data ) use ( &$captured_event_data ) {
					$captured_event_data = $event_data;
					return array();
				}
			);

		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_last_name=Doe&billing_country=US&billing_city=New+York';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );

		$this->assertNotNull( $captured_event_data );
		$this->assertEquals( 'field_update', $captured_event_data['action'] );
		$this->assertEquals( 'test@example.com', $captured_event_data['billing_email'] );
		$this->assertEquals( 'John', $captured_event_data['billing_first_name'] );
		$this->assertEquals( 'Doe', $captured_event_data['billing_last_name'] );
		$this->assertEquals( 'US', $captured_event_data['billing_country'] );
		$this->assertEquals( 'New York', $captured_event_data['billing_city'] );
	}

	/**
	 * Test shortcode checkout field update extracts shipping fields.
	 *
	 * @testdox track_shortcode_checkout_field_update() extracts shipping fields when ship_to_different_address is set.
	 */
	public function test_track_shortcode_checkout_field_update_extracts_shipping_fields(): void {
		$this->mock_collector
			->method( 'get_current_billing_country' )
			->willReturn( null );

		$this->mock_collector
			->method( 'get_current_shipping_country' )
			->willReturn( 'CA' );

		$captured_event_data = null;
		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturnCallback(
				function ( $event_type, $event_data ) use ( &$captured_event_data ) {
					$captured_event_data = $event_data;
					return array();
				}
			);

		$posted_data = 'billing_email=test@example.com&ship_to_different_address=1&shipping_first_name=Jane&shipping_last_name=Smith&shipping_city=Los+Angeles&shipping_country=US';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );

		$this->assertNotNull( $captured_event_data );
		$this->assertEquals( 'Jane', $captured_event_data['shipping_first_name'] );
		$this->assertEquals( 'Smith', $captured_event_data['shipping_last_name'] );
		$this->assertEquals( 'Los Angeles', $captured_event_data['shipping_city'] );
	}

	/**
	 * Test shortcode checkout field update skips shipping fields when not different address.
	 *
	 * @testdox track_shortcode_checkout_field_update() skips shipping fields when not shipping to different address.
	 */
	public function test_track_shortcode_checkout_field_update_skips_shipping_fields_when_not_different_address(): void {
		$this->mock_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'CA' );

		$this->mock_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		$captured_event_data = null;
		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturnCallback(
				function ( $event_type, $event_data ) use ( &$captured_event_data ) {
					$captured_event_data = $event_data;
					return array();
				}
			);

		$posted_data = 'billing_email=test@example.com&billing_country=US&shipping_first_name=Jane&shipping_last_name=Smith';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );

		$this->assertNotNull( $captured_event_data );
		$this->assertArrayNotHasKey( 'shipping_first_name', $captured_event_data );
		$this->assertArrayNotHasKey( 'shipping_last_name', $captured_event_data );
	}

	// ========================================
	// Country Change Detection Tests
	// ========================================

	/**
	 * Test no collection when no country changes.
	 *
	 * @testdox Event is NOT collected when neither country changes.
	 */
	public function test_no_collection_when_no_country_changes(): void {
		$this->mock_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'US' );

		$this->mock_collector
			->method( 'get_current_shipping_country' )
			->willReturn( 'US' );

		$this->mock_collector
			->expects( $this->never() )
			->method( 'collect' );

		$posted_data = 'billing_email=test@example.com&billing_country=US&shipping_country=US';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test no collection when only non-country fields change.
	 *
	 * @testdox Event is NOT collected when only non-country fields change.
	 */
	public function test_no_collection_when_only_non_country_fields_change(): void {
		$this->mock_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'US' );

		$this->mock_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		$this->mock_collector
			->expects( $this->never() )
			->method( 'collect' );

		$posted_data = 'billing_email=test@example.com&billing_first_name=John&billing_phone=1234567890';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test collection when billing country changes from null.
	 *
	 * @testdox Event is collected when billing country changes from null.
	 */
	public function test_collection_when_billing_country_changes_from_null(): void {
		$this->mock_collector
			->method( 'get_current_billing_country' )
			->willReturn( null );

		$this->mock_collector
			->method( 'get_current_shipping_country' )
			->willReturn( null );

		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' );

		$posted_data = 'billing_email=test@example.com&billing_country=US';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	/**
	 * Test collection when ship_to_different_address unchecked with different countries.
	 *
	 * @testdox Event is collected when ship_to_different_address unchecked with different countries.
	 */
	public function test_collection_when_ship_to_different_address_unchecked_with_different_countries(): void {
		$this->mock_collector
			->method( 'get_current_billing_country' )
			->willReturn( 'US' );

		$this->mock_collector
			->method( 'get_current_shipping_country' )
			->willReturn( 'CA' );

		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'checkout_update' ),
				$this->anything()
			);

		$posted_data = 'billing_country=US&billing_email=test@example.com';
		$this->sut->track_shortcode_checkout_field_update( $posted_data );
	}

	// ========================================
	// Order Placed Tests
	// ========================================

	/**
	 * Test track order placed collects data.
	 *
	 * @testdox track_order_placed() collects session data with order details.
	 */
	public function test_track_order_placed_collects_data(): void {
		$order = \WC_Helper_Order::create_order();

		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'order_placed' ),
				$this->callback(
					function ( $event_data ) use ( $order ) {
						$this->assertArrayHasKey( 'order_id', $event_data );
						$this->assertEquals( $order->get_id(), $event_data['order_id'] );
						$this->assertArrayHasKey( 'payment_method', $event_data );
						$this->assertArrayHasKey( 'total', $event_data );
						$this->assertArrayHasKey( 'currency', $event_data );
						$this->assertArrayHasKey( 'customer_id', $event_data );
						$this->assertArrayHasKey( 'status', $event_data );
						return true;
					}
				)
			);

		$this->sut->track_order_placed( $order->get_id(), $order );

		$order->delete( true );
	}
}
