<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;

/**
 * Tests for the BlocksSharedState class.
 */
class BlocksSharedStateTest extends \WC_Unit_Test_Case {

	/**
	 * The consent statement required by the private API.
	 *
	 * @var string
	 */
	private string $consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->reset_shared_state();
	}

	/**
	 * Tear down each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_cart_contents_count' );
		$this->reset_shared_state();
		parent::tearDown();
	}

	/**
	 * Reset the static flags and interactivity config so load_store_config()
	 * and load_cart_state() can run again with a clean slate.
	 */
	private function reset_shared_state(): void {
		$reflection = new \ReflectionClass( BlocksSharedState::class );

		$prop = $reflection->getProperty( 'core_config_registered' );
		$prop->setAccessible( true );
		$prop->setValue( null, false );

		$cart_state = $reflection->getProperty( 'blocks_shared_cart_state' );
		$cart_state->setAccessible( true );
		$cart_state->setValue( null, null );

		$interactivity     = wp_interactivity();
		$interactivity_ref = new \ReflectionClass( $interactivity );
		$config_data       = $interactivity_ref->getProperty( 'config_data' );

		$config_data->setAccessible( true );
		$data = $config_data->getValue( $interactivity );
		unset( $data['woocommerce'] );
		$config_data->setValue( $interactivity, $data );
	}

	/**
	 * @testdox nonOptimisticProperties is empty when no filter is registered.
	 */
	public function test_no_filter_returns_empty_non_optimistic_properties(): void {
		BlocksSharedState::load_cart_state( $this->consent );

		$config = wp_interactivity_config( 'woocommerce' );

		$this->assertArrayHasKey( 'nonOptimisticProperties', $config );
		$this->assertSame( array(), $config['nonOptimisticProperties'] );
	}

	/**
	 * @testdox nonOptimisticProperties contains items_count when a third-party filter is registered.
	 */
	public function test_third_party_filter_detected(): void {
		add_filter( 'woocommerce_cart_contents_count', fn( $count ) => $count + 1 );

		BlocksSharedState::load_cart_state( $this->consent );

		$config = wp_interactivity_config( 'woocommerce' );

		$this->assertArrayHasKey( 'nonOptimisticProperties', $config );
		$this->assertContains( 'cart.items_count', $config['nonOptimisticProperties'] );
	}

	/**
	 * @testdox nonOptimisticProperties is empty when a filter is added and then removed.
	 */
	public function test_filter_added_then_removed_returns_empty(): void {
		$callback = fn( $count ) => $count + 1;

		add_filter( 'woocommerce_cart_contents_count', $callback );
		remove_filter( 'woocommerce_cart_contents_count', $callback );

		BlocksSharedState::load_cart_state( $this->consent );

		$config = wp_interactivity_config( 'woocommerce' );

		$this->assertArrayHasKey( 'nonOptimisticProperties', $config );
		$this->assertSame( array(), $config['nonOptimisticProperties'] );
	}
}
