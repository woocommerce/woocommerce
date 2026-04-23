<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\SharedStores\ProductsStore as TestedProductsStore;
use WC_Helper_Product;
use WC_Product_Grouped;

/**
 * Tests for the ProductsStore shared store.
 */
class ProductsStore extends \WC_Unit_Test_Case {

	/**
	 * Consent string required by the ProductsStore API.
	 *
	 * @var string
	 */
	protected $consent = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * The Interactivity API store namespace under test.
	 *
	 * @var string
	 */
	protected $store_namespace = 'woocommerce/products';

	/**
	 * Captured original Hydration registry entry for restoration in tearDown.
	 *
	 * @var mixed
	 */
	protected $original_hydration_registry_entry = null;

	/**
	 * Reset static state on the ProductsStore and the global
	 * WP_Interactivity_API instance between tests so state does not bleed.
	 */
	public function tearDown(): void {
		$this->reset_products_store_static_state();
		$this->reset_interactivity_state();
		$this->restore_hydration_container_entry();
		parent::tearDown();
	}

	/**
	 * @testdox load_product() rejects calls without the consent string.
	 */
	public function test_load_product_throws_without_consent(): void {
		$this->expectException( \InvalidArgumentException::class );

		TestedProductsStore::load_product( 'nope', 123 );
	}

	/**
	 * @testdox load_variations() rejects calls without the consent string.
	 */
	public function test_load_variations_throws_without_consent(): void {
		$this->expectException( \InvalidArgumentException::class );

		TestedProductsStore::load_variations( 'nope', 123 );
	}

	/**
	 * @testdox load_purchasable_child_products() rejects calls without the consent string.
	 */
	public function test_load_purchasable_child_products_throws_without_consent(): void {
		$this->expectException( \InvalidArgumentException::class );

		TestedProductsStore::load_purchasable_child_products( 'nope', 123 );
	}

	/**
	 * @testdox load_product() hydrates interactivity state with the product payload.
	 */
	public function test_load_product_populates_state(): void {
		$product = WC_Helper_Product::create_simple_product();

		$result = TestedProductsStore::load_product( $this->consent, $product->get_id() );

		$state = wp_interactivity_state( $this->store_namespace );

		$this->assertArrayHasKey( 'products', $state );
		$this->assertArrayHasKey( $product->get_id(), $state['products'] );
		$this->assertSame( $product->get_name(), $state['products'][ $product->get_id() ]['name'] );
		$this->assertSame( $product->get_name(), $result['name'], 'Return value should contain the product data.' );

		$product->delete( true );
	}

	/**
	 * @testdox load_product() fetches each product ID from REST only once.
	 */
	public function test_load_product_is_memoized_per_id(): void {
		$product = WC_Helper_Product::create_simple_product();

		$fake_hydration = $this->create_counting_hydration(
			array(
				'body' => array(
					'id'   => $product->get_id(),
					'name' => 'Fake Product',
				),
			)
		);
		$this->inject_hydration( $fake_hydration );

		TestedProductsStore::load_product( $this->consent, $product->get_id() );
		TestedProductsStore::load_product( $this->consent, $product->get_id() );

		$this->assertSame( 1, $fake_hydration->call_count, 'Should fetch only once per product ID.' );

		$product->delete( true );
	}

	/**
	 * @testdox load_variations() hydrates interactivity state with every child variation.
	 */
	public function test_load_variations_populates_state(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$variation_ids = $product->get_children();

		$result = TestedProductsStore::load_variations( $this->consent, $product->get_id() );

		$state = wp_interactivity_state( $this->store_namespace );

		$this->assertArrayHasKey( 'productVariations', $state );
		$this->assertNotEmpty( $result, 'Should return loaded variations.' );

		foreach ( $variation_ids as $variation_id ) {
			$this->assertArrayHasKey(
				$variation_id,
				$state['productVariations'],
				"Variation {$variation_id} should be in state."
			);
		}

		$product->delete( true );
	}

	/**
	 * @testdox load_variations() fetches each parent product from REST only once.
	 */
	public function test_load_variations_is_memoized_per_parent(): void {
		$product = WC_Helper_Product::create_variation_product();

		$fake_hydration = $this->create_counting_hydration(
			array(
				'body' => array(
					array(
						'id'     => 999,
						'parent' => $product->get_id(),
						'name'   => 'Fake Variation',
					),
				),
			)
		);
		$this->inject_hydration( $fake_hydration );

		TestedProductsStore::load_variations( $this->consent, $product->get_id() );
		$second = TestedProductsStore::load_variations( $this->consent, $product->get_id() );

		$this->assertSame( 1, $fake_hydration->call_count, 'Should fetch only once per parent.' );
		$this->assertArrayHasKey( 999, $second, 'Second call should return the cached variations for the parent.' );

		$reflection = new \ReflectionClass( TestedProductsStore::class );
		$latch      = $reflection->getProperty( 'loaded_variation_parents' );
		$latch->setAccessible( true );
		$loaded = $latch->getValue();
		$this->assertArrayHasKey(
			$product->get_id(),
			$loaded,
			'loaded_variation_parents should record the parent ID.'
		);

		$product->delete( true );
	}

	/**
	 * @testdox load_variations() returns only the variations belonging to the requested parent.
	 */
	public function test_load_variations_second_call_filters_by_parent(): void {
		$reflection = new \ReflectionClass( TestedProductsStore::class );
		$variations = $reflection->getProperty( 'product_variations' );
		$variations->setAccessible( true );
		$loaded = $reflection->getProperty( 'loaded_variation_parents' );
		$loaded->setAccessible( true );

		$variations->setValue(
			null,
			array(
				10 => array(
					'id'     => 10,
					'parent' => 1,
				),
				20 => array(
					'id'     => 20,
					'parent' => 2,
				),
				30 => array(
					'id'     => 30,
					'parent' => 1,
				),
			)
		);
		$loaded->setValue( null, array( 1 => true ) );

		$result = TestedProductsStore::load_variations( $this->consent, 1 );

		$this->assertCount( 2, $result );
		$this->assertArrayHasKey( 10, $result );
		$this->assertArrayHasKey( 30, $result );
		$this->assertArrayNotHasKey( 20, $result );
	}

	/**
	 * @testdox load_purchasable_child_products() returns an empty array for an unknown parent ID.
	 */
	public function test_load_purchasable_child_products_returns_empty_for_bogus_id(): void {
		$result = TestedProductsStore::load_purchasable_child_products( $this->consent, 999999999 );

		$this->assertSame( array(), $result );
	}

	/**
	 * @testdox load_purchasable_child_products() returns an empty array for a grouped product with no children.
	 */
	public function test_load_purchasable_child_products_returns_empty_for_childless_parent(): void {
		$grouped = new WC_Product_Grouped();
		$grouped->set_name( 'Empty Grouped' );
		$grouped->save();

		$result = TestedProductsStore::load_purchasable_child_products( $this->consent, $grouped->get_id() );

		$this->assertSame( array(), $result );

		$grouped->delete( true );
	}

	/**
	 * @testdox load_purchasable_child_products() excludes non-purchasable children.
	 */
	public function test_load_purchasable_child_products_filters_non_purchasable(): void {
		$purchasable     = WC_Helper_Product::create_simple_product();
		$non_purchasable = WC_Helper_Product::create_simple_product();

		$grouped = new WC_Product_Grouped();
		$grouped->set_name( 'Grouped With Mixed Children' );
		$grouped->set_children( array( $purchasable->get_id(), $non_purchasable->get_id() ) );
		$grouped->save();

		$fake_hydration = $this->create_counting_hydration(
			array(
				'body' => array(
					array(
						'id'             => $purchasable->get_id(),
						'name'           => 'Purchasable',
						'is_purchasable' => true,
					),
					array(
						'id'             => $non_purchasable->get_id(),
						'name'           => 'Not Purchasable',
						'is_purchasable' => false,
					),
				),
			)
		);
		$this->inject_hydration( $fake_hydration );

		$result = TestedProductsStore::load_purchasable_child_products( $this->consent, $grouped->get_id() );

		$this->assertCount( 1, $result, 'Only purchasable children should be returned.' );
		$this->assertArrayHasKey( $purchasable->get_id(), $result );
		$this->assertArrayNotHasKey( $non_purchasable->get_id(), $result );

		$grouped->delete( true );
		$purchasable->delete( true );
		$non_purchasable->delete( true );
	}

	/**
	 * @testdox register_getters() registers the derived state closures exactly once.
	 */
	public function test_register_getters_is_idempotent(): void {
		$product = WC_Helper_Product::create_simple_product();

		$reflection = new \ReflectionClass( TestedProductsStore::class );
		$flag       = $reflection->getProperty( 'getters_registered' );
		$flag->setAccessible( true );

		$this->assertFalse( $flag->getValue() );

		TestedProductsStore::load_product( $this->consent, $product->get_id() );
		$this->assertTrue( $flag->getValue() );

		TestedProductsStore::load_product( $this->consent, $product->get_id() );
		TestedProductsStore::load_variations( $this->consent, $product->get_id() );

		$this->assertTrue( $flag->getValue(), 'getters_registered should remain true.' );

		$state = wp_interactivity_state( $this->store_namespace );
		$this->assertArrayHasKey( 'mainProductInContext', $state );
		$this->assertArrayHasKey( 'productVariationInContext', $state );
		$this->assertArrayHasKey( 'productInContext', $state );
		$this->assertInstanceOf( \Closure::class, $state['mainProductInContext'] );
		$this->assertInstanceOf( \Closure::class, $state['productVariationInContext'] );
		$this->assertInstanceOf( \Closure::class, $state['productInContext'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.mainProductInContext resolves to the hydrated product matching state.productId.
	 */
	public function test_product_getter_reads_from_state(): void {
		$this->setExpectedIncorrectUsage( 'WP_Interactivity_API::get_context' );

		$product = WC_Helper_Product::create_simple_product();

		TestedProductsStore::load_product( $this->consent, $product->get_id() );

		wp_interactivity_state(
			$this->store_namespace,
			array( 'productId' => $product->get_id() )
		);

		$state   = wp_interactivity_state( $this->store_namespace );
		$closure = $state['mainProductInContext'];
		$this->assertInstanceOf( \Closure::class, $closure );

		$resolved = $closure();

		$this->assertIsArray( $resolved );
		$this->assertSame( $product->get_name(), $resolved['name'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.productVariationInContext resolves to the hydrated variation matching state.variationId.
	 */
	public function test_selected_variation_getter_reads_from_state(): void {
		$this->setExpectedIncorrectUsage( 'WP_Interactivity_API::get_context' );

		$product       = WC_Helper_Product::create_variation_product();
		$variation_ids = $product->get_children();
		$variation_id  = (int) $variation_ids[0];

		TestedProductsStore::load_variations( $this->consent, $product->get_id() );

		wp_interactivity_state(
			$this->store_namespace,
			array( 'variationId' => $variation_id )
		);

		$state   = wp_interactivity_state( $this->store_namespace );
		$closure = $state['productVariationInContext'];
		$this->assertInstanceOf( \Closure::class, $closure );

		$resolved = $closure();

		$this->assertIsArray( $resolved );
		$this->assertSame( $variation_id, $resolved['id'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.productInContext unwraps closure getters and falls back to the product when no variation is selected.
	 */
	public function test_product_in_context_unwraps_closure_selected_variation(): void {
		$this->setExpectedIncorrectUsage( 'WP_Interactivity_API::get_context' );

		$product = WC_Helper_Product::create_simple_product();

		TestedProductsStore::load_product( $this->consent, $product->get_id() );

		wp_interactivity_state(
			$this->store_namespace,
			array( 'productId' => $product->get_id() )
		);

		$state = wp_interactivity_state( $this->store_namespace );

		$this->assertInstanceOf(
			\Closure::class,
			$state['productVariationInContext'],
			'productVariationInContext should still be a Closure at the point productInContext unwraps it.'
		);

		$resolved = $state['productInContext']();

		$this->assertIsArray(
			$resolved,
			'productInContext should unwrap the closures and fall through to the product branch when no variation is selected.'
		);
		$this->assertSame( $product->get_name(), $resolved['name'] );

		$product->delete( true );
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

	/**
	 * Reset the four private static properties on ProductsStore.
	 */
	private function reset_products_store_static_state(): void {
		$reflection = new \ReflectionClass( TestedProductsStore::class );

		foreach ( array( 'products', 'product_variations', 'loaded_variation_parents' ) as $name ) {
			$property = $reflection->getProperty( $name );
			$property->setAccessible( true );
			$property->setValue( null, array() );
		}

		$flag = $reflection->getProperty( 'getters_registered' );
		$flag->setAccessible( true );
		$flag->setValue( null, false );
	}

	/**
	 * Clear the global WP_Interactivity_API state store so tests do not bleed
	 * state into each other. WordPress core does not expose a public reset
	 * helper, so we reach in via reflection.
	 */
	private function reset_interactivity_state(): void {
		if ( ! function_exists( 'wp_interactivity' ) ) {
			return;
		}

		$api = wp_interactivity();
		if ( ! is_object( $api ) ) {
			return;
		}

		$reflection = new \ReflectionClass( $api );
		foreach ( array( 'state_data', 'config_data', 'derived_state_closures' ) as $name ) {
			if ( ! $reflection->hasProperty( $name ) ) {
				continue;
			}
			$property = $reflection->getProperty( $name );
			$property->setAccessible( true );
			$property->setValue( $api, array() );
		}
	}
}
