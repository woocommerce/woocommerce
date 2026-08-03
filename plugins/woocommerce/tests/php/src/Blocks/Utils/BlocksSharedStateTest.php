<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Blocks\Package;
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
	 * Captured original Hydration registry entry for restoration in tearDown.
	 *
	 * @var mixed
	 */
	private $original_hydration_registry_entry = null;

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
		$this->restore_hydration_container_entry();
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

	/**
	 * @testdox get_cart_items() rejects calls without the consent string, before any cart work happens.
	 */
	public function test_get_cart_items_throws_without_consent(): void {
		$fake = $this->create_counting_hydration( array( 'body' => array( 'items' => array( array( 'key' => 'abc' ) ) ) ) );
		$this->inject_hydration( $fake );

		$this->expectException( \InvalidArgumentException::class );

		try {
			BlocksSharedState::get_cart_items( 'nope' );
		} finally {
			$this->assertSame( 0, $fake->call_count, 'Hydration should not run before consent is checked.' );
		}
	}

	/**
	 * @testdox get_cart_items() self-heals by loading cart state when it has not been loaded yet.
	 */
	public function test_get_cart_items_self_heals_without_prior_load_cart_state(): void {
		$items = array( array( 'key' => 'item-1' ) );
		$fake  = $this->create_counting_hydration( array( 'body' => array( 'items' => $items ) ) );
		$this->inject_hydration( $fake );

		$result = BlocksSharedState::get_cart_items( $this->consent );

		$this->assertSame( $items, $result );
		$this->assertSame( 1, $fake->call_count, 'get_cart_items() should self-heal by running hydration exactly once.' );
	}

	/**
	 * @testdox get_cart_items() returns the same items loaded by a prior load_cart_state() call, without hydrating again.
	 */
	public function test_get_cart_items_after_load_cart_state_reuses_memoized_state(): void {
		$items = array( array( 'key' => 'item-1' ) );
		$fake  = $this->create_counting_hydration( array( 'body' => array( 'items' => $items ) ) );
		$this->inject_hydration( $fake );

		BlocksSharedState::load_cart_state( $this->consent );
		$result = BlocksSharedState::get_cart_items( $this->consent );

		$this->assertSame( $items, $result );
		$this->assertSame( 1, $fake->call_count, 'Hydration should run at most once across both calls.' );
	}

	/**
	 * @testdox get_cart_items() returns an empty array and raises no error or notice when WC()->cart is unavailable.
	 */
	public function test_get_cart_items_returns_empty_array_when_cart_unavailable(): void {
		$original_cart = WC()->cart;
		WC()->cart     = null;

		try {
			$result = BlocksSharedState::get_cart_items( $this->consent );
		} finally {
			WC()->cart = $original_cart;
		}

		$this->assertSame( array(), $result );
	}

	/**
	 * @testdox get_cart_items() returns an empty array when the stored response body has no items key.
	 */
	public function test_get_cart_items_returns_empty_array_when_body_has_no_items_key(): void {
		$fake = $this->create_counting_hydration( array( 'body' => array( 'errors' => array() ) ) );
		$this->inject_hydration( $fake );

		$result = BlocksSharedState::get_cart_items( $this->consent );

		$this->assertSame( array(), $result );
	}

	/**
	 * @testdox get_cart_items() returns an empty array when hydration returns no body at all, as happens on the hydration-exception path.
	 */
	public function test_get_cart_items_returns_empty_array_when_hydration_returns_no_body(): void {
		$fake = $this->create_counting_hydration( array() );
		$this->inject_hydration( $fake );

		$result = BlocksSharedState::get_cart_items( $this->consent );

		$this->assertSame( array(), $result );
	}

	/**
	 * Create an anonymous Hydration stand-in that counts how many times
	 * get_rest_api_response_data was called and returns a canned response.
	 *
	 * @param array $response The response to return from get_rest_api_response_data.
	 * @return object A fake Hydration with public `$call_count`.
	 */
	private function create_counting_hydration( array $response ): object {
		return new class( $response ) {
			/**
			 * The canned response.
			 *
			 * @var array
			 */
			private array $response;

			/**
			 * How many times get_rest_api_response_data was called.
			 *
			 * @var int
			 */
			public int $call_count = 0;

			/**
			 * Constructor.
			 *
			 * @param array $response The canned response.
			 */
			public function __construct( array $response ) {
				$this->response = $response;
			}

			/**
			 * Mimic Hydration::get_rest_api_response_data.
			 *
			 * @param string $path The REST path (ignored).
			 * @return array The canned response.
			 */
			public function get_rest_api_response_data( string $path ): array {
				// Avoid parameter not used PHPCS errors.
				unset( $path );
				++$this->call_count;
				return $this->response;
			}
		};
	}

	/**
	 * Swap the Hydration entry in the Blocks DI container with a fake. Also
	 * captures the original entry so tearDown() can restore it.
	 *
	 * @param object $fake The fake Hydration instance.
	 */
	private function inject_hydration( object $fake ): void {
		$container            = Package::container();
		$container_reflection = new \ReflectionClass( $container );
		$registry_property    = $container_reflection->getProperty( 'registry' );
		$registry_property->setAccessible( true );
		$registry = $registry_property->getValue( $container );

		if ( null === $this->original_hydration_registry_entry ) {
			$this->original_hydration_registry_entry = $registry[ Hydration::class ] ?? false;
		}

		$shared_type_class            = 'Automattic\\WooCommerce\\Blocks\\Registry\\SharedType';
		$registry[ Hydration::class ] = new $shared_type_class(
			function () use ( $fake ) {
				return $fake;
			}
		);

		$registry_property->setValue( $container, $registry );
	}

	/**
	 * Restore the original Hydration entry in the container registry, if we
	 * swapped it during a test.
	 */
	private function restore_hydration_container_entry(): void {
		if ( null === $this->original_hydration_registry_entry ) {
			return;
		}

		$container            = Package::container();
		$container_reflection = new \ReflectionClass( $container );
		$registry_property    = $container_reflection->getProperty( 'registry' );
		$registry_property->setAccessible( true );
		$registry = $registry_property->getValue( $container );

		if ( false === $this->original_hydration_registry_entry ) {
			unset( $registry[ Hydration::class ] );
		} else {
			$registry[ Hydration::class ] = $this->original_hydration_registry_entry;
		}

		$registry_property->setValue( $container, $registry );
		$this->original_hydration_registry_entry = null;
	}
}
