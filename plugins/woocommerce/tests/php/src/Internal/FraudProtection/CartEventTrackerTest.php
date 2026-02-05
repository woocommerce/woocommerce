<?php
/**
 * CartEventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\CartEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;

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
	 * Mock session data collector.
	 *
	 * @var SessionDataCollector|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_collector;

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

		// Create mock.
		$this->mock_collector = $this->createMock( SessionDataCollector::class );

		// Create system under test.
		$this->sut = new CartEventTracker();
		$this->sut->init( $this->mock_collector );

		// Create a test product.
		$this->test_product = \WC_Helper_Product::create_simple_product();

		// Empty cart before each test.
		WC()->cart->empty_cart();
	}

	/**
	 * Test cart page loaded collects data.
	 *
	 * @testdox track_cart_page_loaded() collects session data with empty event data.
	 */
	public function test_track_cart_page_loaded_collects_data(): void {
		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'cart_page_loaded' ),
				$this->equalTo( array() )
			);

		$this->sut->track_cart_page_loaded();
	}

	/**
	 * Test cart item added collects data.
	 *
	 * @testdox track_cart_item_added() collects session data with event details.
	 */
	public function test_track_cart_item_added_collects_data(): void {
		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'cart_item_added' ),
				$this->callback(
					function ( $event_data ) {
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

		$this->sut->track_cart_item_added(
			'test_cart_key',
			$this->test_product->get_id(),
			2,
			0
		);
	}

	/**
	 * Test cart item updated collects data.
	 *
	 * @testdox track_cart_item_updated() collects session data with quantity change.
	 */
	public function test_track_cart_item_updated_collects_data(): void {
		$cart_item_key = WC()->cart->add_to_cart( $this->test_product->get_id(), 1 );

		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'cart_item_updated' ),
				$this->callback(
					function ( $event_data ) {
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

		$this->sut->track_cart_item_updated(
			$cart_item_key,
			5,
			1,
			WC()->cart
		);
	}

	/**
	 * Test cart item removed collects data.
	 *
	 * @testdox track_cart_item_removed() collects session data.
	 */
	public function test_track_cart_item_removed_collects_data(): void {
		$cart_item_key = WC()->cart->add_to_cart( $this->test_product->get_id(), 1 );

		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'cart_item_removed' ),
				$this->callback(
					function ( $event_data ) {
						$this->assertArrayHasKey( 'action', $event_data );
						$this->assertEquals( 'item_removed', $event_data['action'] );
						return true;
					}
				)
			);

		WC()->cart->remove_cart_item( $cart_item_key );

		$this->sut->track_cart_item_removed( $cart_item_key, WC()->cart );
	}

	/**
	 * Test cart item restored collects data.
	 *
	 * @testdox track_cart_item_restored() collects session data.
	 */
	public function test_track_cart_item_restored_collects_data(): void {
		$cart_item_key = WC()->cart->add_to_cart( $this->test_product->get_id(), 1 );

		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'cart_item_restored' ),
				$this->callback(
					function ( $event_data ) {
						$this->assertArrayHasKey( 'action', $event_data );
						$this->assertEquals( 'item_restored', $event_data['action'] );
						return true;
					}
				)
			);

		$this->sut->track_cart_item_restored(
			$cart_item_key,
			WC()->cart
		);
	}

	/**
	 * Test cart events include variation_id.
	 *
	 * @testdox Cart events include variation_id when present.
	 */
	public function test_cart_events_include_variation_id(): void {
		$variable_product = \WC_Helper_Product::create_variation_product();
		$variations       = $variable_product->get_available_variations();
		$variation_id     = $variations[0]['variation_id'];

		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'cart_item_added' ),
				$this->callback(
					function ( $event_data ) use ( $variation_id ) {
						$this->assertArrayHasKey( 'action', $event_data );
						$this->assertEquals( 'item_added', $event_data['action'] );
						$this->assertArrayHasKey( 'variation_id', $event_data );
						$this->assertEquals( $variation_id, $event_data['variation_id'] );
						return true;
					}
				)
			);

		$this->sut->track_cart_item_added(
			'test_cart_key',
			$variable_product->get_id(),
			1,
			$variation_id
		);

		$variable_product->delete( true );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		if ( $this->test_product ) {
			$this->test_product->delete( true );
		}

		WC()->cart->empty_cart();
	}
}
