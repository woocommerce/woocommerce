<?php
/**
 * CartEventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\CartEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionDispatcher;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;

/**
 * Tests for CartEventTracker.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\CartEventTracker
 */
class CartEventTrackerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var CartEventTracker
	 */
	private $sut;

	/**
	 * Mock event dispatcher.
	 *
	 * @var FraudProtectionDispatcher|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_dispatcher;

	/**
	 * Mock fraud protection controller.
	 *
	 * @var FraudProtectionController|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_controller;

	/**
	 * Test product.
	 *
	 * @var \WC_Product
	 */
	private $test_product;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure WooCommerce cart and session are available.
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		// Create mocks.
		$this->mock_dispatcher = $this->createMock( FraudProtectionDispatcher::class );
		$this->mock_controller = $this->createMock( FraudProtectionController::class );

		// Create system under test.
		$this->sut = new CartEventTracker();
		$this->sut->init( $this->mock_dispatcher );

		// Create a test product.
		$this->test_product = \WC_Helper_Product::create_simple_product();

		// Empty cart before each test.
		WC()->cart->empty_cart();
	}

	/**
	 * Test track_cart_page_loaded dispatches event.
	 * The CartEventTracker::track_cart_page_loaded does not add any event data.
	 * The data collection is handled by the SessionDataCollector.
	 * So we only need to test if the dispatcher is called with no event data.
	 */
	public function test_track_cart_page_loaded_dispatches_event(): void {
		// Mock dispatcher to verify event is dispatched with empty event data.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_page_loaded' ),
				$this->equalTo( array() )
			);

		// Call the method.
		$this->sut->track_cart_page_loaded();
	}

	/**
	 * Test track_cart_item_added tracks event.
	 */
	public function test_track_cart_item_added_tracks_event(): void {
		// Mock the dispatcher to verify dispatch_event is called with event data.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_item_added' ),
				$this->callback(
					function ( $event_data ) {
						// Verify the event data structure.
						$this->assertArrayHasKey( 'action', $event_data );
						$this->assertEquals( 'item_added', $event_data['action'] );
						$this->assertArrayHasKey( 'product_id', $event_data );
						$this->assertEquals( $this->test_product->get_id(), $event_data['product_id'] );
						$this->assertArrayHasKey( 'quantity', $event_data );
						$this->assertEquals( 2, $event_data['quantity'] );
						return true;
					}
				)
			);

		// Call the method.
		$this->sut->track_cart_item_added(
			'test_cart_key',
			$this->test_product->get_id(),
			2,
			0
		);
	}

	/**
	 * Test track_cart_item_updated tracks event.
	 */
	public function test_track_cart_item_updated_tracks_event(): void {
		// Add item to cart first.
		$cart_item_key = WC()->cart->add_to_cart( $this->test_product->get_id(), 1 );

		// Mock the dispatcher.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_item_updated' ),
				$this->callback(
					function ( $event_data ) {
						// Verify the event data structure.
						$this->assertArrayHasKey( 'action', $event_data );
						$this->assertEquals( 'item_updated', $event_data['action'] );
						$this->assertArrayHasKey( 'quantity', $event_data );
						$this->assertEquals( 5, $event_data['quantity'] );
						$this->assertArrayHasKey( 'old_quantity', $event_data );
						$this->assertEquals( 1, $event_data['old_quantity'] );
						return true;
					}
				)
			);

		// Call the method.
		$this->sut->track_cart_item_updated(
			$cart_item_key,
			5,
			1,
			WC()->cart
		);
	}

	/**
	 * Test track_cart_item_removed tracks event.
	 */
	public function test_track_cart_item_removed_tracks_event(): void {
		// Add item to cart.
		$cart_item_key = WC()->cart->add_to_cart( $this->test_product->get_id(), 1 );

		// Mock the dispatcher.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_item_removed' ),
				$this->callback(
					function ( $event_data ) {
						// Verify the event data structure.
						$this->assertArrayHasKey( 'action', $event_data );
						$this->assertEquals( 'item_removed', $event_data['action'] );
						return true;
					}
				)
			);

		// Remove the item from cart.
		WC()->cart->remove_cart_item( $cart_item_key );

		// Call the method directly.
		$this->sut->track_cart_item_removed( $cart_item_key, WC()->cart );
	}

	/**
	 * Test track_cart_item_restored tracks event.
	 */
	public function test_track_cart_item_restored_tracks_event(): void {
		// Add item to cart.
		$cart_item_key = WC()->cart->add_to_cart( $this->test_product->get_id(), 1 );

		// Mock the dispatcher.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_item_restored' ),
				$this->callback(
					function ( $event_data ) {
						// Verify the event data structure.
						$this->assertArrayHasKey( 'action', $event_data );
						$this->assertEquals( 'item_restored', $event_data['action'] );
						return true;
					}
				)
			);

		// Call the method directly (simulating restore action).
		$this->sut->track_cart_item_restored(
			$cart_item_key,
			WC()->cart
		);
	}

	/**
	 * Test that cart events include variation ID when present.
	 */
	public function test_cart_events_include_variation_id(): void {
		// Create a variable product with variation.
		$variable_product = \WC_Helper_Product::create_variation_product();
		$variations       = $variable_product->get_available_variations();
		$variation_id     = $variations[0]['variation_id'];

		// Mock the dispatcher to capture event data.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_item_added' ),
				$this->callback(
					function ( $event_data ) use ( $variation_id ) {
						// Verify the event data structure includes variation_id.
						$this->assertArrayHasKey( 'action', $event_data );
						$this->assertEquals( 'item_added', $event_data['action'] );
						$this->assertArrayHasKey( 'variation_id', $event_data );
						$this->assertEquals( $variation_id, $event_data['variation_id'] );
						return true;
					}
				)
			);

		// Call the method with variation ID.
		$this->sut->track_cart_item_added(
			'test_cart_key',
			$variable_product->get_id(),
			1,
			$variation_id
		);

		// Clean up.
		$variable_product->delete( true );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up test product.
		if ( $this->test_product ) {
			$this->test_product->delete( true );
		}

		// Empty cart.
		WC()->cart->empty_cart();
	}
}
