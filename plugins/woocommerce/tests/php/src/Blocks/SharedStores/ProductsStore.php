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
	protected $store_namespace = 'woocommerce';

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
	 * @testdox load_product() hydrates interactivity state with the product payload under the woocommerce namespace.
	 */
	public function test_load_product_populates_state(): void {
		$product = WC_Helper_Product::create_simple_product();

		$result = TestedProductsStore::load_product( $this->consent, $product->get_id() );

		$state = wp_interactivity_state( $this->store_namespace );

		$this->assertArrayHasKey( 'products', $state );
		$this->assertArrayHasKey( $product->get_id(), $state['products'] );
		$this->assertSame( $product->get_name(), $state['products'][ $product->get_id() ]['name'] );
		$this->assertSame( $product->get_name(), $result['name'], 'Return value should contain the product data.' );

		$legacy_state = wp_interactivity_state( 'woocommerce/products' );
		$this->assertArrayNotHasKey( 'products', $legacy_state, 'Nothing should be seeded into the retired woocommerce/products namespace.' );

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
	 * @testdox load_variations() hydrates interactivity state with every child variation under the woocommerce namespace.
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

		$legacy_state = wp_interactivity_state( 'woocommerce/products' );
		$this->assertArrayNotHasKey( 'productVariations', $legacy_state, 'Nothing should be seeded into the retired woocommerce/products namespace.' );

		$product->delete( true );
	}

	/**
	 * @testdox load_variations() does not hydrate descriptions protected by the parent product password.
	 */
	public function test_load_variations_omits_parent_password_protected_descriptions(): void {
		$product      = WC_Helper_Product::create_variation_product();
		$variation_id = $product->get_children()[0];
		$variation    = wc_get_product( $variation_id );
		$variation->set_description( 'Protected variation description' );
		$variation->save();
		$product->set_post_password( 'secret' );
		$product->save();

		$result = TestedProductsStore::load_variations( $this->consent, $product->get_id() );

		$this->assertSame(
			'',
			$result[ $variation_id ]['description'],
			'Variation descriptions should be omitted until the parent product password is entered.'
		);

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
	 * @testdox register_getters() registers the productScope closure exactly once.
	 */
	public function test_register_getters_registers_product_scope_once(): void {
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
		$this->assertArrayHasKey( 'productScope', $state );
		$this->assertInstanceOf( \Closure::class, $state['productScope'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope.scopeName defaults to _default when the context declares none.
	 */
	public function test_scope_name_defaults_to_default(): void {
		$this->push_woocommerce_context( array() );

		$envelope = $this->get_product_scope();

		$this->assertSame( '_default', $envelope['scopeName']() );
	}

	/**
	 * @testdox state.productScope.product resolves to the matching variation, and .baseProduct to the parent, when the context declares a matching variation.
	 */
	public function test_product_scope_resolves_matching_variation(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$variation_ids = $product->get_children();
		$huge_red_zero = wc_get_product( $variation_ids[2] );
		$selected      = array(
			array(
				'attribute' => 'size',
				'value'     => 'huge',
			),
			array(
				'attribute' => 'colour',
				'value'     => 'red',
			),
			array(
				'attribute' => 'number',
				'value'     => '0',
			),
		);

		TestedProductsStore::load_product( $this->consent, $product->get_id() );
		TestedProductsStore::load_variations( $this->consent, $product->get_id() );

		$this->push_woocommerce_context(
			array(
				'productId' => $product->get_id(),
				'variation' => $selected,
			)
		);

		$envelope = $this->get_product_scope();

		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( $huge_red_zero->get_id(), $resolved_variation['id'] );

		$resolved_product = $envelope['product']();
		$this->assertSame( $huge_red_zero->get_id(), $resolved_product['id'] );

		$base_product = $envelope['baseProduct']();
		$this->assertSame( $product->get_id(), $base_product['id'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope.product resolves to the product itself, and .productVariation is null, when the context declares no variation.
	 */
	public function test_product_scope_resolves_base_product_without_a_variation(): void {
		$product = WC_Helper_Product::create_variation_product();

		TestedProductsStore::load_product( $this->consent, $product->get_id() );
		TestedProductsStore::load_variations( $this->consent, $product->get_id() );

		$this->push_woocommerce_context( array( 'productId' => $product->get_id() ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation'](), 'No variation should resolve without a selection.' );

		$resolved_product = $envelope['product']();
		$this->assertSame( $product->get_id(), $resolved_product['id'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope members resolve from state.template when the context declares no productId.
	 */
	public function test_product_scope_resolves_from_template_without_a_declared_product_id(): void {
		$product = WC_Helper_Product::create_simple_product();

		TestedProductsStore::load_product( $this->consent, $product->get_id() );
		wp_interactivity_state(
			$this->store_namespace,
			array( 'template' => array( 'productId' => $product->get_id() ) )
		);

		$this->push_woocommerce_context( array() );

		$envelope = $this->get_product_scope();

		$this->assertSame( $product->get_id(), $envelope['productId']() );

		$resolved_product = $envelope['product']();
		$this->assertSame( $product->get_id(), $resolved_product['id'] );

		$product->delete( true );
	}

	/**
	 * @testdox a state.productScopes record wins over the declared context for the same scopeName.
	 */
	public function test_product_scope_record_wins_over_declared_context(): void {
		$context_product = WC_Helper_Product::create_simple_product();
		$record_product  = WC_Helper_Product::create_simple_product();

		TestedProductsStore::load_product( $this->consent, $context_product->get_id() );
		TestedProductsStore::load_product( $this->consent, $record_product->get_id() );

		wp_interactivity_state(
			$this->store_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array( 'productId' => $record_product->get_id() ),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'scopeName' => 'my-scope',
				'productId' => $context_product->get_id(),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertSame( $record_product->get_id(), $envelope['productId']() );

		$context_product->delete( true );
		$record_product->delete( true );
	}

	/**
	 * @testdox state.productScope.draftCartItem.quantity reads 1 for a scope with no record.
	 */
	public function test_draft_cart_item_defaults_to_quantity_one_without_a_record(): void {
		$this->push_woocommerce_context(
			array(
				'productId' => 42,
				'variation' => array(),
			)
		);

		$envelope = $this->get_product_scope();
		$draft    = $envelope['draftCartItem']();

		$this->assertSame( 42, $draft['id'] );
		$this->assertSame( array(), $draft['variation'] );
		$this->assertSame( 1, $draft['quantity'] );
	}

	/**
	 * @testdox state.productScope.draftCartItem.quantity reads the seeded record's value.
	 */
	public function test_draft_cart_item_reads_the_seeded_records_quantity(): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array(
						'draftCartItem' => array(
							'id'        => 42,
							'variation' => array(),
							'quantity'  => 3,
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'scopeName' => 'my-scope' ) );

		$envelope = $this->get_product_scope();
		$draft    = $envelope['draftCartItem']();

		$this->assertSame( 3, $draft['quantity'] );
	}

	/**
	 * @testdox state.productScope.cartItem matches the line named by the context's cartItemKey, regardless of id.
	 */
	public function test_cart_item_matches_by_cart_item_key(): void {
		$product = WC_Helper_Product::create_simple_product();

		wp_interactivity_state(
			$this->store_namespace,
			array(
				'cart' => array(
					'items' => array(
						array(
							'key'  => 'line-1',
							'id'   => $product->get_id(),
							'type' => 'simple',
						),
						array(
							'key'  => 'line-2',
							'id'   => 999999,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId'   => $product->get_id(),
				'cartItemKey' => 'line-2',
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-2', $cart_item['key'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope.cartItem matches a non-variable line by product id when no cartItemKey is declared.
	 */
	public function test_cart_item_matches_simple_product_by_id(): void {
		$product = WC_Helper_Product::create_simple_product();

		TestedProductsStore::load_product( $this->consent, $product->get_id() );

		wp_interactivity_state(
			$this->store_namespace,
			array(
				'cart' => array(
					'items' => array(
						array(
							'key'  => 'line-1',
							'id'   => $product->get_id(),
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => $product->get_id() ) );

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-1', $cart_item['key'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope.cartItem matches a variation line whose label-valued attributes resolve to the selection.
	 */
	public function test_cart_item_matches_variation_by_attributes(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$variation_ids = $product->get_children();
		$huge_red_zero = wc_get_product( $variation_ids[2] );
		$selected      = array(
			array(
				'attribute' => 'size',
				'value'     => 'huge',
			),
			array(
				'attribute' => 'colour',
				'value'     => 'red',
			),
			array(
				'attribute' => 'number',
				'value'     => '0',
			),
		);

		TestedProductsStore::load_product( $this->consent, $product->get_id() );
		TestedProductsStore::load_variations( $this->consent, $product->get_id() );

		wp_interactivity_state(
			$this->store_namespace,
			array(
				'cart' => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => $huge_red_zero->get_id(),
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => 'size',
									'value'     => 'huge',
								),
								array(
									'attribute' => 'colour',
									'value'     => 'red',
								),
								array(
									'attribute' => 'number',
									'value'     => '0',
								),
							),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => $product->get_id(),
				'variation' => $selected,
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-1', $cart_item['key'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope.cartItem is null when no cart line matches.
	 */
	public function test_cart_item_is_null_when_nothing_matches(): void {
		$product = WC_Helper_Product::create_simple_product();

		TestedProductsStore::load_product( $this->consent, $product->get_id() );

		wp_interactivity_state( $this->store_namespace, array( 'cart' => array( 'items' => array() ) ) );

		$this->push_woocommerce_context( array( 'productId' => $product->get_id() ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );

		$product->delete( true );
	}

	/**
	 * Build the `productScope` envelope from the current interactivity
	 * state, registering the closure first when no loader call has done so.
	 *
	 * @return array<string, \Closure> The envelope, keyed by member name.
	 */
	private function get_product_scope(): array {
		$reflection = new \ReflectionClass( TestedProductsStore::class );
		$method     = $reflection->getMethod( 'register_getters' );
		$method->setAccessible( true );
		$method->invoke( null );

		$state = wp_interactivity_state( $this->store_namespace );

		return $state['productScope']();
	}

	/**
	 * Push a `woocommerce` context frame onto the global interactivity API
	 * instance, simulating an element rendering with the given declared
	 * context. tearDown() resets the stack back to its idle (null) state.
	 *
	 * @param array $context The `woocommerce` context to simulate.
	 */
	private function push_woocommerce_context( array $context ): void {
		$api        = wp_interactivity();
		$reflection = new \ReflectionClass( $api );
		$property   = $reflection->getProperty( 'context_stack' );
		$property->setAccessible( true );
		$property->setValue( $api, array( array( $this->store_namespace => $context ) ) );
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
	 * Clear the global WP_Interactivity_API state and context stores so
	 * tests do not bleed state into each other. WordPress core does not
	 * expose a public reset helper, so we reach in via reflection.
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

		if ( $reflection->hasProperty( 'context_stack' ) ) {
			$property = $reflection->getProperty( 'context_stack' );
			$property->setAccessible( true );
			$property->setValue( $api, null );
		}
	}
}
