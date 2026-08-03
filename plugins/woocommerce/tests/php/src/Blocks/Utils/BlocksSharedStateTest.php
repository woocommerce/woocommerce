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

		// Both the config and the state must be cleared: wp_interactivity_state()
		// merges recursively, so a previous test's cart would blend into the next
		// one's rather than replace it.
		$interactivity     = wp_interactivity();
		$interactivity_ref = new \ReflectionClass( $interactivity );

		foreach ( array( 'config_data', 'state_data' ) as $property_name ) {
			$property = $interactivity_ref->getProperty( $property_name );
			$property->setAccessible( true );
			$data = $property->getValue( $interactivity );
			unset( $data['woocommerce'] );
			$property->setValue( $interactivity, $data );
		}
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
	 * @testdox load_cart_state() rejects calls without the consent string, before any cart work happens.
	 */
	public function test_load_cart_state_throws_without_consent(): void {
		$fake = $this->create_counting_hydration( array( 'body' => array( 'items' => array( array( 'key' => 'abc' ) ) ) ) );
		$this->inject_hydration( $fake );

		$this->expectException( \InvalidArgumentException::class );

		try {
			BlocksSharedState::load_cart_state( 'nope' );
		} finally {
			$this->assertSame( 0, $fake->call_count, 'Hydration should not run before consent is checked.' );
		}
	}

	/**
	 * The hydrated cart response must reach the interactivity state untransformed,
	 * because that published value is what both the client and server-side
	 * consumers read. A transformation here would let the two describe
	 * different carts.
	 *
	 * @testdox load_cart_state() publishes the hydrated cart response verbatim as state.cart.
	 */
	public function test_load_cart_state_publishes_the_hydrated_response_verbatim(): void {
		$body = array(
			'items'       => array( array( 'key' => 'item-1' ), array( 'key' => 'item-2' ) ),
			'items_count' => 2,
		);
		$this->inject_hydration( $this->create_counting_hydration( array( 'body' => $body ) ) );

		BlocksSharedState::load_cart_state( $this->consent );

		$this->assertSame( $body, $this->published_cart() );
	}

	/**
	 * @testdox load_cart_state() hydrates at most once per request across repeated calls.
	 */
	public function test_load_cart_state_hydrates_at_most_once(): void {
		$items = array( array( 'key' => 'item-1' ) );
		$fake  = $this->create_counting_hydration( array( 'body' => array( 'items' => $items ) ) );
		$this->inject_hydration( $fake );

		BlocksSharedState::load_cart_state( $this->consent );
		BlocksSharedState::load_cart_state( $this->consent );

		$this->assertSame( $items, $this->published_cart()['items'] );
		$this->assertSame( 1, $fake->call_count, 'Hydration should run at most once across both calls.' );
	}

	/**
	 * @testdox load_cart_state() publishes an empty cart, and raises no error, when WC()->cart is unavailable.
	 */
	public function test_load_cart_state_publishes_empty_cart_when_cart_unavailable(): void {
		$original_cart = WC()->cart;
		WC()->cart     = null;

		try {
			BlocksSharedState::load_cart_state( $this->consent );
		} finally {
			WC()->cart = $original_cart;
		}

		$this->assertSame( array(), $this->published_cart() );
	}

	/**
	 * @testdox load_cart_state() publishes a cart with no items key when the response body has none, as happens when the cart route returns an error response.
	 */
	public function test_load_cart_state_publishes_body_without_items_key_as_is(): void {
		$this->inject_hydration( $this->create_counting_hydration( array( 'body' => array( 'errors' => array() ) ) ) );

		BlocksSharedState::load_cart_state( $this->consent );

		$this->assertArrayNotHasKey( 'items', $this->published_cart() );
	}

	/**
	 * @testdox load_cart_state() publishes an empty cart when hydration returns no body at all, as happens on the hydration-exception path.
	 */
	public function test_load_cart_state_publishes_empty_cart_when_hydration_returns_no_body(): void {
		$this->inject_hydration( $this->create_counting_hydration( array() ) );

		BlocksSharedState::load_cart_state( $this->consent );

		$this->assertSame( array(), $this->published_cart() );
	}

	/**
	 * Read the cart as published to the interactivity state — the same surface
	 * the client and server-side consumers read.
	 *
	 * @return array
	 */
	private function published_cart(): array {
		$state = wp_interactivity_state( 'woocommerce' );

		return $state['cart'] ?? array();
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
