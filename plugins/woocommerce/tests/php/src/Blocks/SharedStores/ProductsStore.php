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

		$products_namespace_state = wp_interactivity_state( 'woocommerce/products' );
		$this->assertArrayNotHasKey( 'products', $products_namespace_state, 'Nothing should be seeded into the woocommerce/products namespace.' );

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

		$products_namespace_state = wp_interactivity_state( 'woocommerce/products' );
		$this->assertArrayNotHasKey( 'productVariations', $products_namespace_state, 'Nothing should be seeded into the woocommerce/products namespace.' );

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
	 * @testdox a state.productScopes record's draftCartItem.id wins over the declared context for the same scopeName.
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
					'my-scope' => array( 'draftCartItem' => array( 'id' => $record_product->get_id() ) ),
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
	 * @testdox a state.productScopes record's draftCartItem.variation selects the matching variation on the server.
	 */
	public function test_product_scope_record_variation_selects_matching_variation(): void {
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
				'productScopes' => array(
					'my-scope' => array(
						'draftCartItem' => array(
							'id'        => $product->get_id(),
							'variation' => $selected,
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'scopeName' => 'my-scope' ) );

		$envelope = $this->get_product_scope();

		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( $huge_red_zero->get_id(), $resolved_variation['id'] );

		$resolved_product = $envelope['product']();
		$this->assertSame( $huge_red_zero->get_id(), $resolved_product['id'] );

		$product->delete( true );
	}

	/**
	 * @testdox a record carrying only draftCartItem.quantity leaves identity resolving from the declared context.
	 */
	public function test_product_scope_record_with_only_quantity_leaves_identity_to_context(): void {
		$product  = WC_Helper_Product::create_simple_product();
		$selected = array(
			array(
				'attribute' => 'colour',
				'value'     => 'red',
			),
		);

		wp_interactivity_state(
			$this->store_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array( 'draftCartItem' => array( 'quantity' => 4 ) ),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'scopeName' => 'my-scope',
				'productId' => $product->get_id(),
				'variation' => $selected,
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertSame( $product->get_id(), $envelope['productId']() );
		$this->assertSame( $selected, $envelope['variation']() );

		$draft = $envelope['draftCartItem']();
		$this->assertSame( 4, $draft['quantity'] );
		$this->assertSame( $product->get_id(), $draft['id'] );
		$this->assertSame( $selected, $draft['variation'] );

		$product->delete( true );
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
	 * @testdox state.productScope.draftCartItem carries a record's extension props unchanged.
	 */
	public function test_draft_cart_item_passes_through_extension_props(): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array(
						'draftCartItem' => array( 'giftWrap' => true ),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'scopeName' => 'my-scope',
				'productId' => 42,
			)
		);

		$envelope = $this->get_product_scope();
		$draft    = $envelope['draftCartItem']();

		$this->assertSame( 42, $draft['id'] );
		$this->assertTrue( $draft['giftWrap'] );
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
	 * @testdox state.productScope.productId falls back to the declared context, without a fatal, when the seeded productScopes value is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_product_scope_resolves_without_fatal_when_product_scopes_state_is_not_an_array( $malformed ): void {
		$product = WC_Helper_Product::create_simple_product();

		wp_interactivity_state( $this->store_namespace, array( 'productScopes' => $malformed ) );

		$this->push_woocommerce_context(
			array(
				'scopeName' => 'my-scope',
				'productId' => $product->get_id(),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertSame( $product->get_id(), $envelope['productId']() );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope resolves the _default record, without a fatal, when the context's declared scopeName is not usable as an array key.
	 */
	public function test_product_scope_falls_back_to_default_scope_when_scope_name_is_not_an_array_key(): void {
		$record_product  = WC_Helper_Product::create_simple_product();
		$context_product = WC_Helper_Product::create_simple_product();

		wp_interactivity_state(
			$this->store_namespace,
			array(
				'productScopes' => array(
					'_default' => array( 'draftCartItem' => array( 'id' => $record_product->get_id() ) ),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'scopeName' => array( 'not', 'a', 'key' ),
				'productId' => $context_product->get_id(),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertSame( $record_product->get_id(), $envelope['productId']() );
		$this->assertSame( '_default', $envelope['scopeName'](), 'scopeName should report the scope the record was actually resolved from.' );

		$record_product->delete( true );
		$context_product->delete( true );
	}

	/**
	 * @testdox state.productScope members resolve from the declared context, without a fatal, when the seeded template value is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_product_scope_resolves_without_fatal_when_template_state_is_not_an_array( $malformed ): void {
		wp_interactivity_state( $this->store_namespace, array( 'template' => $malformed ) );

		$this->push_woocommerce_context( array() );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productId']() );
		$this->assertSame( array(), $envelope['variation']() );
	}

	/**
	 * @testdox state.productScope resolves as though the record carried no draftCartItem, without a fatal, when the seeded draftCartItem value is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_product_scope_resolves_without_fatal_when_draft_cart_item_record_is_not_an_array( $malformed ): void {
		$product = WC_Helper_Product::create_simple_product();

		wp_interactivity_state(
			$this->store_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array( 'draftCartItem' => $malformed ),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'scopeName' => 'my-scope',
				'productId' => $product->get_id(),
				'variation' => array(),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertSame( $product->get_id(), $envelope['productId']() );
		$this->assertSame( array(), $envelope['variation']() );

		$draft = $envelope['draftCartItem']();
		$this->assertSame( $product->get_id(), $draft['id'] );
		$this->assertSame( array(), $draft['variation'] );
		$this->assertSame( 1, $draft['quantity'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope.productId and .baseProduct fall back to the declared context, without a fatal, when the seeded record's draftCartItem.id is not usable as an array key.
	 * @dataProvider provider_shapes_not_usable_as_an_array_key
	 * @param mixed $malformed A shape not usable as an array key.
	 */
	public function test_product_id_resolves_without_fatal_when_record_draft_id_is_not_an_array_key( $malformed ): void {
		$product = WC_Helper_Product::create_simple_product();

		TestedProductsStore::load_product( $this->consent, $product->get_id() );

		wp_interactivity_state(
			$this->store_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array( 'draftCartItem' => array( 'id' => $malformed ) ),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'scopeName' => 'my-scope',
				'productId' => $product->get_id(),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertSame( $product->get_id(), $envelope['productId']() );

		$base_product = $envelope['baseProduct']();
		$this->assertSame( $product->get_id(), $base_product['id'] );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope.variation falls back to the declared context, without a fatal, when the seeded record's draftCartItem.variation is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_variation_falls_back_to_context_when_record_draft_variation_is_not_an_array( $malformed ): void {
		$selected = array(
			array(
				'attribute' => 'colour',
				'value'     => 'red',
			),
		);

		wp_interactivity_state(
			$this->store_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array(
						'draftCartItem' => array(
							'id'        => 42,
							'variation' => $malformed,
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'scopeName' => 'my-scope',
				'variation' => $selected,
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertSame( $selected, $envelope['variation']() );
	}

	/**
	 * @testdox state.productScope.variation falls back to the seeded template, without a fatal, when the context's declared variation is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_variation_falls_back_to_template_when_context_variation_is_not_an_array( $malformed ): void {
		$templated = array(
			array(
				'attribute' => 'colour',
				'value'     => 'blue',
			),
		);

		wp_interactivity_state(
			$this->store_namespace,
			array( 'template' => array( 'variation' => $templated ) )
		);

		$this->push_woocommerce_context( array( 'variation' => $malformed ) );

		$envelope = $this->get_product_scope();

		$this->assertSame( $templated, $envelope['variation']() );
	}

	/**
	 * @testdox state.productScope.variation resolves to an empty selection, without a fatal, when the seeded template's variation is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_variation_falls_back_to_default_when_template_variation_is_not_an_array( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array( 'template' => array( 'variation' => $malformed ) )
		);

		$this->push_woocommerce_context( array() );

		$envelope = $this->get_product_scope();

		$this->assertSame( array(), $envelope['variation']() );
	}

	/**
	 * @testdox state.productScope.baseProduct resolves to null, without a fatal, when the seeded products value is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_base_product_resolves_without_fatal_when_products_state_is_not_an_array( $malformed ): void {
		wp_interactivity_state( $this->store_namespace, array( 'products' => $malformed ) );

		$this->push_woocommerce_context( array( 'productId' => 42 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['baseProduct']() );
	}

	/**
	 * @testdox state.productScope.productVariation resolves to null, without a fatal, when the seeded productVariations value is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_product_variation_resolves_without_fatal_when_product_variations_state_is_not_an_array( $malformed ): void {
		$product = WC_Helper_Product::create_variation_product();

		TestedProductsStore::load_product( $this->consent, $product->get_id() );

		wp_interactivity_state( $this->store_namespace, array( 'productVariations' => $malformed ) );

		$this->push_woocommerce_context(
			array(
				'productId' => $product->get_id(),
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
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation']() );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope.cartItem resolves to null, without a fatal, when the seeded cart items value is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_cart_item_resolves_without_fatal_when_cart_items_state_is_not_an_array( $malformed ): void {
		$product = WC_Helper_Product::create_simple_product();

		TestedProductsStore::load_product( $this->consent, $product->get_id() );

		wp_interactivity_state( $this->store_namespace, array( 'cart' => array( 'items' => $malformed ) ) );

		$this->push_woocommerce_context( array( 'productId' => $product->get_id() ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );

		$product->delete( true );
	}

	/**
	 * Values the envelope's private helpers read below its own top-level
	 * state and context reads.
	 *
	 * Every case below drives the envelope through a hand-built,
	 * synthetic `products`/`productVariations`/`cart` state rather than a
	 * real loaded product, so the malformed value under test can be placed
	 * at one exact path while everything else stays well formed. Product
	 * id 5001 and variation ids 6001/6002 are reused throughout and name no
	 * real database record.
	 */

	/**
	 * @testdox state.productScope.scopeName reports the scope the record was actually resolved from, not the raw declared value, when the context's scopeName is not usable as an array key.
	 * @dataProvider provider_shapes_not_usable_as_an_array_key
	 * @param mixed $malformed A shape not usable as an array key.
	 */
	public function test_scope_name_reports_the_resolved_scope_key( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'productScopes' => array(
					'_default' => array( 'draftCartItem' => array( 'id' => 5001 ) ),
				),
			)
		);

		$this->push_woocommerce_context( array( 'scopeName' => $malformed ) );

		$envelope = $this->get_product_scope();

		$this->assertSame( '_default', $envelope['scopeName']() );
		$this->assertSame( 5001, $envelope['productId']() );
	}

	/**
	 * @testdox state.productScope.baseProduct, .productVariation, .product and .cartItem all resolve as though the product were never loaded when its products[id] entry is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_base_product_resolves_to_null_when_the_entry_is_not_an_array( $malformed ): void {
		wp_interactivity_state( $this->store_namespace, array( 'products' => array( 5001 => $malformed ) ) );

		$this->push_woocommerce_context( array( 'productId' => 5001 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['baseProduct']() );
		$this->assertNull( $envelope['productVariation']() );
		$this->assertNull( $envelope['product']() );
		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox no base product, variation, product or cart line resolves, without a fatal, when the seeded products state itself is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_everything_resolves_to_null_when_the_products_map_itself_is_not_an_array( $malformed ): void {
		wp_interactivity_state( $this->store_namespace, array( 'products' => $malformed ) );

		$this->push_woocommerce_context( array( 'productId' => 5001 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['baseProduct']() );
		$this->assertNull( $envelope['productVariation']() );
		$this->assertNull( $envelope['product']() );
		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox state.productScope.productVariation resolves to null, and .product falls back to the base product, when the matched variation's own entry is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_product_variation_resolves_to_null_when_the_matched_entry_is_not_an_array( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array( 6001 => $malformed ),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation']() );
		$this->assertSame( 5001, $envelope['product']()['id'] );
	}

	/**
	 * @testdox state.productScope.productVariation resolves to null, and .product falls back to the base product, when the base product's variations list is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_product_variation_resolves_to_null_when_the_variations_list_is_not_an_array( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products' => array(
					5001 => array(
						'id'         => 5001,
						'variations' => $malformed,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation']() );
		$this->assertSame( 5001, $envelope['product']()['id'] );
	}

	/**
	 * @testdox a variation summary that is not an array or object is cast without a fatal, and, carrying no attributes, does not match — resolution continues with the next summary.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_a_non_array_variation_summary_does_not_match( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							$malformed,
							array(
								'id'         => 6002,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array( 6002 => array( 'id' => 6002 ) ),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();
		$resolved = $envelope['productVariation']();

		$this->assertIsArray( $resolved );
		$this->assertSame( 6002, $resolved['id'] );
	}

	/**
	 * @testdox a variation summary whose attributes is not an array does not match, and resolution continues with the next summary.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_a_variation_summary_without_an_attributes_array_does_not_match( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => $malformed,
							),
							array(
								'id'         => 6002,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array( 'id' => 6001 ),
					6002 => array( 'id' => 6002 ),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();
		$resolved = $envelope['productVariation']();

		$this->assertIsArray( $resolved );
		$this->assertSame( 6002, $resolved['id'], 'The first, malformed summary must not vacuously match every selection.' );
	}

	/**
	 * @testdox a variation attribute entry that is not an array is treated as carrying no name and no value, so it is not satisfied and the summary does not match.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_a_variation_attribute_entry_that_is_not_an_array_does_not_satisfy_the_attribute( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array( $malformed ),
							),
							array(
								'id'         => 6002,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array( 'id' => 6001 ),
					6002 => array( 'id' => 6002 ),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();
		$resolved = $envelope['productVariation']();

		$this->assertIsArray( $resolved );
		$this->assertSame( 6002, $resolved['id'] );
	}

	/**
	 * @testdox a variation attribute whose name is not a string is treated as having no name, so it is not satisfied by the selection and the summary does not match.
	 * @dataProvider provider_shapes_not_a_string
	 * @param mixed $malformed A shape that is not a string.
	 */
	public function test_a_variation_attribute_with_a_non_string_name_does_not_satisfy_the_attribute( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => $malformed,
										'value' => 'red',
									),
								),
							),
							array(
								'id'         => 6002,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array( 'id' => 6001 ),
					6002 => array( 'id' => 6002 ),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();
		$resolved = $envelope['productVariation']();

		$this->assertIsArray( $resolved );
		$this->assertSame( 6002, $resolved['id'] );
	}

	/**
	 * @testdox a matched variation summary whose id is not usable as an array key resolves to no variation, and the search does not continue to a later, otherwise matching summary.
	 * @dataProvider provider_shapes_not_usable_as_an_array_key
	 * @param mixed $malformed A shape not usable as an array key.
	 */
	public function test_a_matched_summary_with_an_unusable_id_stops_the_search( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => $malformed,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
							array(
								'id'         => 6002,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array( 6002 => array( 'id' => 6002 ) ),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation']() );
		$this->assertSame( 5001, $envelope['product']()['id'], 'product should fall back to the base product rather than the later matching summary.' );
	}

	/**
	 * @testdox matching a cart line by attributes does not fatal, and resolves the line's variation parent as absent, when the seeded productVariations map itself is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_cart_item_attribute_matching_survives_a_malformed_product_variations_map( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array( 5001 => array( 'id' => 5001 ) ),
				'productVariations' => $malformed,
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 5001,
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => 'colour',
									'value'     => 'red',
								),
							),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-1', $cart_item['key'] );
	}

	/**
	 * @testdox a cart line's attributes resolve against an empty attribute list, without a fatal, when its matched variation's parent is not usable as an array key.
	 * @dataProvider provider_shapes_not_usable_as_an_array_key
	 * @param mixed $malformed A shape not usable as an array key.
	 */
	public function test_cart_item_attribute_matching_treats_an_unusable_parent_as_no_parent( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array(
						'id'     => 6001,
						'parent' => $malformed,
					),
				),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 6001,
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => 'colour',
									'value'     => 'red',
								),
							),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-1', $cart_item['key'] );
	}

	/**
	 * @testdox a cart line's attributes resolve against an empty attribute list, without a fatal, when the parent product's attributes is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_cart_item_attribute_matching_treats_non_array_parent_attributes_as_empty( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'attributes' => $malformed,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array(
						'id'     => 6001,
						'parent' => 5001,
					),
				),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 6001,
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => 'colour',
									'value'     => 'red',
								),
							),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-1', $cart_item['key'] );
	}

	/**
	 * @testdox a product attribute whose name is not a string is treated as having no name, so no term resolves under it and the cart line's label is used as its own slug.
	 * @dataProvider provider_shapes_not_a_string
	 * @param mixed $malformed A shape that is not a string.
	 */
	public function test_a_product_attribute_with_a_non_string_name_is_skipped( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'attributes' => array(
							array(
								'name'  => $malformed,
								'terms' => array(
									array(
										'name' => 'Red',
										'slug' => 'red',
									),
								),
							),
						),
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array(
						'id'     => 6001,
						'parent' => 5001,
					),
				),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 6001,
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => 'colour',
									'value'     => 'Red',
								),
							),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-1', $cart_item['key'] );
	}

	/**
	 * @testdox a product attribute whose terms is not an array resolves no term, without a fatal, so the cart line's label is used as its own slug.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_a_product_attribute_with_non_array_terms_resolves_no_term( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'attributes' => array(
							array(
								'name'  => 'colour',
								'terms' => $malformed,
							),
						),
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array(
						'id'     => 6001,
						'parent' => 5001,
					),
				),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 6001,
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => 'colour',
									'value'     => 'red',
								),
							),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-1', $cart_item['key'] );
	}

	/**
	 * @testdox a matching term whose slug is not a string resolves to the term's own label unchanged, as when no term matches.
	 * @dataProvider provider_shapes_not_a_string
	 * @param mixed $malformed A shape that is not a string.
	 */
	public function test_a_term_with_a_non_string_slug_resolves_to_the_label( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'attributes' => array(
							array(
								'name'  => 'colour',
								'terms' => array(
									array(
										'name' => 'Red',
										'slug' => $malformed,
									),
								),
							),
						),
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array(
						'id'     => 6001,
						'parent' => 5001,
					),
				),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 6001,
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => 'colour',
									'value'     => 'Red',
								),
							),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-1', $cart_item['key'] );
	}

	/**
	 * @testdox state.productScope.cartItem resolves to null, without a fatal, when the seeded cart itself is not an array.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_cart_item_resolves_without_fatal_when_cart_itself_is_not_an_array( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products' => array( 5001 => array( 'id' => 5001 ) ),
				'cart'     => $malformed,
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 5001 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox a cart line that is not an array never matches by product id, and the remaining lines are still searched.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_a_non_array_cart_line_never_matches_and_the_search_continues( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products' => array( 5001 => array( 'id' => 5001 ) ),
				'cart'     => array(
					'items' => array(
						$malformed,
						array(
							'key'  => 'line-2',
							'id'   => 5001,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 5001 ) );

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-2', $cart_item['key'] );
	}

	/**
	 * @testdox a cart line that is not an array never matches by cartItemKey either, and the search continues to the line with the declared key.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_a_non_array_cart_line_never_matches_by_key_and_the_search_continues( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'cart' => array(
					'items' => array(
						$malformed,
						array(
							'key'  => 'line-2',
							'id'   => 5001,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId'   => 5001,
				'cartItemKey' => 'line-2',
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-2', $cart_item['key'] );
	}

	/**
	 * @testdox a cart line whose own id is not usable as an array key never matches a simple product, and the search continues with the next line.
	 * @dataProvider provider_shapes_not_usable_as_an_array_key
	 * @param mixed $malformed A shape not usable as an array key.
	 */
	public function test_a_cart_line_with_an_unusable_id_never_matches_a_simple_product( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products' => array( 5001 => array( 'id' => 5001 ) ),
				'cart'     => array(
					'items' => array(
						array(
							'key'  => 'line-1',
							'id'   => $malformed,
							'type' => 'simple',
						),
						array(
							'key'  => 'line-2',
							'id'   => 5001,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 5001 ) );

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-2', $cart_item['key'] );
	}

	/**
	 * @testdox a variation cart line whose own id is not usable as an array key never matches, and the search continues with the next line.
	 * @dataProvider provider_shapes_not_usable_as_an_array_key
	 * @param mixed $malformed A shape not usable as an array key.
	 */
	public function test_a_cart_line_with_an_unusable_id_never_matches_a_variation( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array( 6001 => array( 'id' => 6001 ) ),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => $malformed,
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => 'colour',
									'value'     => 'red',
								),
							),
						),
						array(
							'key'       => 'line-2',
							'id'        => 6001,
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => 'colour',
									'value'     => 'red',
								),
							),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-2', $cart_item['key'] );
	}

	/**
	 * @testdox a cart line whose own id is a non-integral float that would match by truncation never matches, and no implicit float-to-int array-key deprecation is produced.
	 */
	public function test_a_cart_line_with_a_non_integral_float_id_close_to_a_match_never_matches(): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products' => array( 5001 => array( 'id' => 5001 ) ),
				'cart'     => array(
					'items' => array(
						array(
							'key'  => 'line-1',
							'id'   => 5001.5,
							'type' => 'simple',
						),
						array(
							'key'  => 'line-2',
							'id'   => 5001,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 5001 ) );

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-2', $cart_item['key'] );
	}

	/**
	 * @testdox state.productScope.cartItem resolves to null, without a warning, when the resolved base product's own id is not usable as an array key.
	 * @dataProvider provider_shapes_not_usable_as_an_array_key
	 * @param mixed $malformed A shape not usable as an array key.
	 */
	public function test_cart_item_is_null_when_the_resolved_products_id_is_not_usable_as_an_array_key( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products' => array( 5001 => array( 'id' => $malformed ) ),
				'cart'     => array(
					'items' => array(
						array(
							'key'  => 'line-1',
							'id'   => 5001,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 5001 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox state.productScope.cartItem resolves to null, without a warning, when the resolved variation's own id is not usable as an array key.
	 * @dataProvider provider_shapes_not_usable_as_an_array_key
	 * @param mixed $malformed A shape not usable as an array key.
	 */
	public function test_cart_item_is_null_when_the_resolved_variations_id_is_not_usable_as_an_array_key( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array( 6001 => array( 'id' => $malformed ) ),
				'cart'              => array(
					'items' => array(
						array(
							'key'  => 'line-1',
							'id'   => 6001,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox a variation cart line whose own variation is not an array never matches, and cartItem resolves from the remaining lines.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_a_variation_cart_line_with_a_non_array_variation_never_matches( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products' => array( 5001 => array( 'id' => 5001 ) ),
				'cart'     => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 5001,
							'type'      => 'variation',
							'variation' => $malformed,
						),
						array(
							'key'  => 'line-2',
							'id'   => 5001,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-2', $cart_item['key'] );
	}

	/**
	 * @testdox a cart line's variation entry that is not an array is treated as carrying no attribute and no value, so the line does not match.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_a_cart_line_variation_entry_that_is_not_an_array_does_not_match( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array(
						'id'     => 6001,
						'parent' => 5001,
					),
				),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 6001,
							'type'      => 'variation',
							'variation' => array( $malformed ),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox a cart line's variation entry whose attribute is not a string is treated as having no attribute, so the line does not match.
	 * @dataProvider provider_shapes_not_a_string
	 * @param mixed $malformed A shape that is not a string.
	 */
	public function test_a_cart_line_variation_entry_with_a_non_string_attribute_does_not_match( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array(
						'id'     => 6001,
						'parent' => 5001,
					),
				),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 6001,
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => $malformed,
									'value'     => 'red',
								),
							),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox a cart line's variation entry whose value is not a string is treated as having no value, so the line does not match.
	 * @dataProvider provider_shapes_not_a_string
	 * @param mixed $malformed A shape that is not a string.
	 */
	public function test_a_cart_line_variation_entry_with_a_non_string_value_does_not_match( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					6001 => array(
						'id'     => 6001,
						'parent' => 5001,
					),
				),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 6001,
							'type'      => 'variation',
							'variation' => array(
								array(
									'attribute' => 'colour',
									'value'     => $malformed,
								),
							),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox a selection entry (from the record, the context or the template) that is not an array satisfies no candidate attribute.
	 * @dataProvider provider_shapes_not_an_array
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_a_non_array_selection_entry_satisfies_no_attribute( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array( 6001 => array( 'id' => 6001 ) ),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array( $malformed ),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation']() );
	}

	/**
	 * @testdox a selection entry whose attribute is not a string is treated as having no attribute, so it satisfies no candidate attribute.
	 * @dataProvider provider_shapes_not_a_string
	 * @param mixed $malformed A shape that is not a string.
	 */
	public function test_a_selection_entry_with_a_non_string_attribute_satisfies_no_attribute( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array( 6001 => array( 'id' => 6001 ) ),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => $malformed,
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation']() );
	}

	/**
	 * @testdox a selection entry whose value is not a string never satisfies a string-valued candidate attribute.
	 * @dataProvider provider_shapes_not_a_string
	 * @param mixed $malformed A shape that is not a string.
	 */
	public function test_a_selection_entry_with_a_non_string_value_does_not_satisfy_a_string_valued_attribute( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array( 6001 => array( 'id' => 6001 ) ),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => $malformed,
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation']() );
	}

	/**
	 * @testdox a selection entry whose value is not a string does not satisfy an "Any" candidate attribute — one whose own value is null — the same as an explicit null value would.
	 * @dataProvider provider_shapes_not_a_string
	 * @param mixed $malformed A shape that is not a string.
	 */
	public function test_a_selection_entry_with_a_non_string_value_does_not_satisfy_an_any_attribute( $malformed ): void {
		wp_interactivity_state(
			$this->store_namespace,
			array(
				'products'          => array(
					5001 => array(
						'id'         => 5001,
						'variations' => array(
							array(
								'id'         => 6001,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => null,
									),
								),
							),
						),
					),
				),
				'productVariations' => array( 6001 => array( 'id' => 6001 ) ),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => $malformed,
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation'](), 'A non-string, non-null selected value must not satisfy an "Any" attribute.' );
	}

	/**
	 * The eight shape classes a value in externally writable state or
	 * context can carry in place of the shape a path expects, each a
	 * representative of its class.
	 *
	 * @return array<string, mixed>
	 */
	private function shape_classes(): array {
		return array(
			'null'     => null,
			'bool'     => true,
			'int'      => 42,
			'float'    => 1.5,
			'string'   => 'x',
			'list'     => array( 'x' ),
			'map'      => array( 'k' => 'v' ),
			'stdClass' => (object) array( 'k' => 'v' ),
		);
	}

	/**
	 * The shape classes above, minus the ones named in $expects — the
	 * shapes for which a path is well formed and so outside its
	 * "any other shape" sweep. Used to build a data provider per guarded
	 * read from the closed set of shape classes, rather than by listing
	 * cases by hand.
	 *
	 * @param string[] $expects The shape class keys the path accepts.
	 * @return array<string, array<mixed>> Provider-ready rows, one per remaining shape.
	 */
	private function shapes_other_than( array $expects ): array {
		$rows = array();

		foreach ( $this->shape_classes() as $name => $value ) {
			if ( in_array( $name, $expects, true ) ) {
				continue;
			}
			$rows[ $name ] = array( $value );
		}

		return $rows;
	}

	/**
	 * Every shape class except the two PHP array shapes (list and map),
	 * for a path whose well-formed shape is an array.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provider_shapes_not_an_array(): array {
		return $this->shapes_other_than( array( 'list', 'map' ) );
	}

	/**
	 * Every shape class except int and string, for a path whose
	 * well-formed shape is a value usable as an array key.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provider_shapes_not_usable_as_an_array_key(): array {
		return $this->shapes_other_than( array( 'int', 'string' ) );
	}

	/**
	 * Every shape class except string, for a path whose well-formed shape
	 * is a string.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provider_shapes_not_a_string(): array {
		return $this->shapes_other_than( array( 'string' ) );
	}

	/**
	 * Every path a third party can seed in the `woocommerce` state or the
	 * element's context, crossed with the eight shape classes: one entry
	 * per row of the envelope's seeded-paths tables, keyed by that row's
	 * own path text, plus one further entry per extra source for the
	 * resolved selection's entry, attribute and value, each naming its
	 * source in its key.
	 *
	 * Each entry carries: `seed`, a callable that places a value at the
	 * entry's path within an otherwise well-formed state and context and
	 * returns the resulting envelope; `expects`, the shape classes for
	 * which the path is well formed; `members`, the outcome for a shape
	 * outside `expects` as `member name => assertion`; `inside`, keyed by
	 * shape class, the outcome for a shape inside `expects`; and
	 * `control`, a well-formed value together with the assertion its
	 * participation makes true.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function sweep_entries(): array {
		$layered_state   = $this->layered_identity_state();
		$layered_context = $this->layered_identity_context();

		$template_only_state   = $this->template_only_state();
		$template_only_context = array();

		$context_only_state   = $this->context_only_state();
		$context_only_context = $this->context_only_context();

		$matching_state = $this->matching_product_state();
		$matched        = array(
			'productId' => 5001,
			'variation' => array(
				array(
					'attribute' => 'colour',
					'value'     => 'red',
				),
			),
		);
		$unmatched      = array(
			'productId' => 5001,
			'variation' => array(
				array(
					'attribute' => 'colour',
					'value'     => 'green',
				),
			),
		);

		$cart_state   = $this->cart_matching_state();
		$cart_context = array( 'productId' => 6002 );

		return array_merge(
			$this->sweep_entries_record_chain( $layered_state, $layered_context ),
			$this->sweep_entries_template( $template_only_state, $template_only_context ),
			$this->sweep_entries_context( $context_only_state, $context_only_context ),
			$this->sweep_entries_base_product( $matching_state, $unmatched ),
			$this->sweep_entries_variations( $matching_state, $matched ),
			$this->sweep_entries_product_variations( $matching_state, $matched ),
			$this->sweep_entries_attributes_and_terms( $matching_state, $matched ),
			$this->sweep_entries_cart( $cart_state, $cart_context, $matching_state, $matched ),
			$this->sweep_entries_resolved_selection( $matching_state )
		);
	}

	/**
	 * A `woocommerce` state and context whose record, context and template
	 * each declare their own draft product id and attribute value, so a
	 * member that falls back from one source to the next reports which
	 * source actually supplied it.
	 *
	 * @return array<string, mixed>
	 */
	private function layered_identity_state(): array {
		return array(
			'productScopes'     => array(
				'_default' => array(
					'draftCartItem' => array(
						'id'        => 9001,
						'variation' => array(
							array(
								'attribute' => 'colour',
								'value'     => 'crimson',
							),
						),
						'quantity'  => 3,
						'gift'      => 'wrapped',
					),
				),
				'my-scope' => array(
					'draftCartItem' => array(
						'id'        => 9002,
						'variation' => array(
							array(
								'attribute' => 'colour',
								'value'     => 'violet',
							),
						),
						'quantity'  => 4,
						'gift'      => 'boxed',
					),
				),
			),
			'template'          => array(
				'productId' => 7001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'teal',
					),
				),
			),
			'products'          => array(),
			'productVariations' => array(),
			'cart'              => array( 'items' => array() ),
		);
	}

	/**
	 * The context paired with layered_identity_state(): it names the
	 * 'my-scope' record and its own distinct product id and attribute
	 * value.
	 *
	 * @return array<string, mixed>
	 */
	private function layered_identity_context(): array {
		return array(
			'scopeName' => 'my-scope',
			'productId' => 5001,
			'variation' => array(
				array(
					'attribute' => 'colour',
					'value'     => 'azure',
				),
			),
		);
	}

	/**
	 * A `woocommerce` state that declares only a well-formed `template`,
	 * with no record and no products, so a member's resolution reflects
	 * the template alone.
	 *
	 * @return array<string, mixed>
	 */
	private function template_only_state(): array {
		return array(
			'productScopes'     => array(),
			'template'          => array(
				'productId' => 7001,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'teal',
					),
				),
			),
			'products'          => array(),
			'productVariations' => array(),
			'cart'              => array( 'items' => array() ),
		);
	}

	/**
	 * A `woocommerce` state whose only fallback source is the template, for
	 * proving that a malformed context value falls through to it.
	 *
	 * @return array<string, mixed>
	 */
	private function context_only_state(): array {
		return $this->template_only_state();
	}

	/**
	 * The well-formed context paired with context_only_state(): its own
	 * product id and attribute value, distinct from the template's.
	 *
	 * @return array<string, mixed>
	 */
	private function context_only_context(): array {
		return array(
			'scopeName' => 'irrelevant',
			'productId' => 5001,
			'variation' => array(
				array(
					'attribute' => 'colour',
					'value'     => 'azure',
				),
			),
		);
	}

	/**
	 * A `woocommerce` state carrying one real product (5001, with two
	 * variations, 6002 for colour "red" and 6001 for colour "blue") and a
	 * cart line matching each, so a resolved selection can be driven all
	 * the way through variation matching and cart-item attribute matching.
	 * A cart line's own attribute value is its display label ("Scarlet" /
	 * "Azure"), distinct from the slug ("red" / "blue") a selection names,
	 * so a broken label-to-slug lookup is observable rather than
	 * coinciding by letter case.
	 *
	 * @return array<string, mixed>
	 */
	private function matching_product_state(): array {
		return array(
			'productScopes'     => array(),
			'template'          => array(),
			'products'          => array(
				5001 => array(
					'id'         => 5001,
					'variations' => array(
						array(
							'id'         => 6002,
							'attributes' => array(
								array(
									'name'  => 'colour',
									'value' => 'red',
								),
							),
						),
						array(
							'id'         => 6001,
							'attributes' => array(
								array(
									'name'  => 'colour',
									'value' => 'blue',
								),
							),
						),
					),
					'attributes' => array(
						array(
							'name'  => 'colour',
							'terms' => array(
								array(
									'name' => 'Scarlet',
									'slug' => 'red',
								),
								array(
									'name' => 'Azure',
									'slug' => 'blue',
								),
							),
						),
					),
				),
			),
			'productVariations' => array(
				6002 => array(
					'id'     => 6002,
					'parent' => 5001,
				),
				6001 => array(
					'id'     => 6001,
					'parent' => 5001,
				),
				9001 => array(
					'id'     => 9001,
					'parent' => 5001,
				),
				9002 => array(
					'id'     => 9002,
					'parent' => 5001,
				),
				9003 => array(
					'id'     => 9003,
					'parent' => 5001,
				),
				9004 => array(
					'id'     => 9004,
					'parent' => 5001,
				),
				9005 => array(
					'id'     => 9005,
					'parent' => 5001,
				),
			),
			'cart'              => array(
				'items' => array(
					array(
						'key'       => 'line-red',
						'id'        => 6002,
						'type'      => 'variation',
						'variation' => array(
							array(
								'attribute' => 'colour',
								'value'     => 'Scarlet',
							),
						),
					),
					array(
						'key'       => 'line-blue',
						'id'        => 6001,
						'type'      => 'variation',
						'variation' => array(
							array(
								'attribute' => 'colour',
								'value'     => 'Azure',
							),
						),
					),
					array(
						'key'  => 'line-simple',
						'id'   => 5001,
						'type' => 'simple',
					),
				),
			),
		);
	}

	/**
	 * A minimal `woocommerce` state carrying one real simple product
	 * (6002) and two cart lines matching it by id alone, for sweep entries
	 * about the cart itself rather than about attribute matching.
	 *
	 * @return array<string, mixed>
	 */
	private function cart_matching_state(): array {
		return array(
			'productScopes'     => array(),
			'template'          => array(),
			'products'          => array( 6002 => array( 'id' => 6002 ) ),
			'productVariations' => array(),
			'cart'              => array(
				'items' => array(
					array(
						'key'  => 'line-target',
						'id'   => 6002,
						'type' => 'simple',
					),
					array(
						'key'  => 'line-fallback',
						'id'   => 6002,
						'type' => 'simple',
					),
				),
			),
		);
	}

	/**
	 * Place $value at $path inside $root, creating any intermediate array
	 * the path needs.
	 *
	 * @param array $root  The array to modify, by reference.
	 * @param array $path  The sequence of keys leading to the value.
	 * @param mixed $value The value to place at $path.
	 */
	private static function place( array &$root, array $path, $value ): void {
		$node = &$root;
		$last = array_pop( $path );
		foreach ( $path as $key ) {
			if ( ! isset( $node[ $key ] ) || ! is_array( $node[ $key ] ) ) {
				$node[ $key ] = array();
			}
			$node = &$node[ $key ];
		}
		$node[ $last ] = $value;
	}

	/**
	 * Build a sweep entry's `seed` callable: copies the given well-formed
	 * state and context, places a value at one path within whichever plane
	 * names it, sets up the state and context through
	 * push_woocommerce_context() and the reflection registration
	 * get_product_scope() itself uses, and returns the envelope's own
	 * closure, not yet invoked, so a caller can resolve it under its own
	 * error handler.
	 *
	 * @param array  $state   The well-formed `woocommerce` state to copy.
	 * @param array  $context The well-formed context to copy.
	 * @param string $plane   Either 'state' or 'context'.
	 * @param array  $path    The path within that plane to place the value at.
	 * @return \Closure function( mixed $value ): \Closure
	 */
	private function seed_at( array $state, array $context, string $plane, array $path ): \Closure {
		return function ( $value ) use ( $state, $context, $plane, $path ): \Closure {
			if ( 'context' === $plane ) {
				self::place( $context, $path, $value );
			} else {
				self::place( $state, $path, $value );
			}
			wp_interactivity_state( $this->store_namespace, $state );
			$this->push_woocommerce_context( $context );
			return $this->product_scope_getter();
		};
	}

	/**
	 * Register the `productScope` getter, exactly as get_product_scope()
	 * does, and return it unexecuted so its own resolution — the part that
	 * can raise a diagnostic — happens under a caller's error handler
	 * rather than under the reflection call this registration needs.
	 *
	 * @return \Closure function(): array<string, \Closure> The envelope getter.
	 */
	private function product_scope_getter(): \Closure {
		$reflection = new \ReflectionClass( TestedProductsStore::class );
		$method     = $reflection->getMethod( 'register_getters' );
		$method->setAccessible( true );
		$method->invoke( null );

		$state = wp_interactivity_state( $this->store_namespace );

		return $state['productScope'];
	}

	/**
	 * Compare an expected value against an envelope member's resolved
	 * value, using loose equality for an object (identity is not
	 * guaranteed once a value has passed through array copies) and strict
	 * equality otherwise.
	 *
	 * @param mixed $expected The value the entry requires.
	 * @param mixed $actual   The value the envelope resolved.
	 */
	private function assert_shape_equal( $expected, $actual ): void {
		if ( is_object( $expected ) || is_object( $actual ) ) {
			$this->assertEquals( $expected, $actual );
			return;
		}
		$this->assertSame( $expected, $actual );
	}

	/**
	 * An assertion requiring a member to resolve to exactly $value,
	 * regardless of the shape seeded.
	 *
	 * @param mixed $value The required value.
	 * @return \Closure function( mixed $actual, mixed $seed ): void
	 */
	private function fixed( $value ): \Closure {
		return function ( $actual, $seed ) use ( $value ): void {
			unset( $seed );
			$this->assert_shape_equal( $value, $actual );
		};
	}

	/**
	 * An assertion requiring a member to resolve to null.
	 *
	 * @return \Closure function( mixed $actual, mixed $seed ): void
	 */
	private function absent(): \Closure {
		return function ( $actual, $seed ): void {
			unset( $seed );
			$this->assertNull( $actual );
		};
	}

	/**
	 * An assertion requiring a member to resolve to the seeded value
	 * itself (a pass-through path with no sink).
	 *
	 * @return \Closure function( mixed $actual, mixed $seed ): void
	 */
	private function passthrough(): \Closure {
		return function ( $actual, $seed ): void {
			$this->assert_shape_equal( $seed, $actual );
		};
	}

	/**
	 * An assertion requiring a member to resolve to an array entry
	 * carrying the given id.
	 *
	 * @param int|string $id The required id.
	 * @return \Closure function( mixed $actual, mixed $seed ): void
	 */
	private function entry_with_id( $id ): \Closure {
		return function ( $actual, $seed ) use ( $id ): void {
			unset( $seed );
			$this->assertIsArray( $actual );
			$this->assertSame( $id, $actual['id'] ?? null );
		};
	}

	/**
	 * An assertion requiring a member to resolve to an array entry
	 * carrying the given key.
	 *
	 * @param string $key The required key.
	 * @return \Closure function( mixed $actual, mixed $seed ): void
	 */
	private function entry_with_key( string $key ): \Closure {
		return function ( $actual, $seed ) use ( $key ): void {
			unset( $seed );
			$this->assertIsArray( $actual );
			$this->assertSame( $key, $actual['key'] ?? null );
		};
	}

	/**
	 * Resolve which member assertions a shape outside `expects` takes: the
	 * entry's default, overridden per member for a shape named in
	 * `override`.
	 *
	 * @param array  $outside   The entry's 'default' and optional 'override' members.
	 * @param string $shape_key The shape class being seeded.
	 * @return array<string, \Closure>
	 */
	private function outside_members( array $outside, string $shape_key ): array {
		return array_merge( $outside['default'], $outside['override'][ $shape_key ] ?? array() );
	}

	/**
	 * The productScopes-chain sweep entries: the map, the record, its
	 * draftCartItem, and the draftCartItem's own id, variation, quantity
	 * and an extension key. Each falls back to the context's own values
	 * when malformed, and to the record's own ('my-scope') values when
	 * well formed.
	 *
	 * @param array $state   The layered-identity state.
	 * @param array $context The layered-identity context.
	 * @return array<string, array<string, mixed>>
	 */
	private function sweep_entries_record_chain( array $state, array $context ): array {
		$entries = array();

		$entries['productScopes'] = array(
			'seed'    => $this->seed_at( $state, $context, 'state', array( 'productScopes' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array(
				'default' => array(
					'productId' => $this->fixed( 5001 ),
					'variation' => $this->fixed( $context['variation'] ),
				),
			),
			'inside'  => array(
				'list' => array(
					'productId' => $this->fixed( 5001 ),
					'variation' => $this->fixed( $context['variation'] ),
				),
				'map'  => array(
					'productId' => $this->fixed( 5001 ),
					'variation' => $this->fixed( $context['variation'] ),
				),
			),
			'control' => array(
				'value'  => array( 'my-scope' => array( 'draftCartItem' => array( 'id' => 4242 ) ) ),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 4242, $envelope['productId']() );
				},
			),
		);

		$entries['productScopes[<key>] (the record)'] = array(
			'seed'    => $this->seed_at( $state, $context, 'state', array( 'productScopes', 'my-scope' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array(
				'default' => array(
					'productId' => $this->fixed( 5001 ),
					'variation' => $this->fixed( $context['variation'] ),
				),
			),
			'inside'  => array(
				'list' => array(
					'productId' => $this->fixed( 5001 ),
					'variation' => $this->fixed( $context['variation'] ),
				),
				'map'  => array(
					'productId' => $this->fixed( 5001 ),
					'variation' => $this->fixed( $context['variation'] ),
				),
			),
			'control' => array(
				'value'  => array( 'draftCartItem' => array( 'id' => 3333 ) ),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 3333, $envelope['productId']() );
				},
			),
		);

		$entries['…[<key>].draftCartItem'] = array(
			'seed'    => $this->seed_at( $state, $context, 'state', array( 'productScopes', 'my-scope', 'draftCartItem' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array(
				'default' => array(
					'productId' => $this->fixed( 5001 ),
					'variation' => $this->fixed( $context['variation'] ),
				),
			),
			'inside'  => array(
				'list' => array(
					'productId' => $this->fixed( 5001 ),
					'variation' => $this->fixed( $context['variation'] ),
				),
				'map'  => array(
					'productId' => $this->fixed( 5001 ),
					'variation' => $this->fixed( $context['variation'] ),
				),
			),
			'control' => array(
				'value'  => array( 'id' => 8001 ),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 8001, $envelope['productId']() );
				},
			),
		);

		$entries['…draftCartItem.id'] = array(
			'seed'    => $this->seed_at( $state, $context, 'state', array( 'productScopes', 'my-scope', 'draftCartItem', 'id' ) ),
			'expects' => array( 'int', 'string' ),
			'outside' => array( 'default' => array( 'productId' => $this->fixed( 5001 ) ) ),
			'inside'  => array(
				'int'    => array( 'productId' => $this->fixed( 42 ) ),
				'string' => array( 'productId' => $this->fixed( 'x' ) ),
			),
			'control' => array(
				'value'  => 8002,
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 8002, $envelope['productId']() );
				},
			),
		);

		$entries['…draftCartItem.variation'] = array(
			'seed'    => $this->seed_at( $state, $context, 'state', array( 'productScopes', 'my-scope', 'draftCartItem', 'variation' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'variation' => $this->fixed( $context['variation'] ) ) ),
			'inside'  => array(
				'list' => array( 'variation' => $this->fixed( array( 'x' ) ) ),
				'map'  => array( 'variation' => $this->fixed( array( 'k' => 'v' ) ) ),
			),
			'control' => array(
				'value'  => array(
					array(
						'attribute' => 'colour',
						'value'     => 'gold',
					),
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame(
						array(
							array(
								'attribute' => 'colour',
								'value'     => 'gold',
							),
						),
						$envelope['variation']()
					);
				},
			),
		);

		$entries['…draftCartItem.quantity'] = array(
			'seed'    => $this->seed_at( $state, $context, 'state', array( 'productScopes', 'my-scope', 'draftCartItem', 'quantity' ) ),
			'expects' => array(),
			'outside' => array( 'default' => array( 'draftCartItem' => $this->quantity_passthrough() ) ),
			'inside'  => array(),
			'control' => array(
				'value'  => 7,
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 7, $envelope['draftCartItem']()['quantity'] );
				},
			),
		);

		$entries['…draftCartItem.<any other key>'] = array(
			'seed'    => $this->seed_at( $state, $context, 'state', array( 'productScopes', 'my-scope', 'draftCartItem', 'giftMessage' ) ),
			'expects' => array(),
			'outside' => array( 'default' => array( 'draftCartItem' => $this->gift_message_passthrough() ) ),
			'inside'  => array(),
			'control' => array(
				'value'  => 'wrap it',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'wrap it', $envelope['draftCartItem']()['giftMessage'] );
				},
			),
		);

		return $entries;
	}

	/**
	 * An assertion requiring draftCartItem's own `quantity` key to equal
	 * the seeded value, for every shape.
	 *
	 * @return \Closure function( mixed $actual, mixed $seed ): void
	 */
	private function quantity_passthrough(): \Closure {
		return function ( $actual, $seed ): void {
			$this->assertIsArray( $actual );
			$this->assert_shape_equal( $seed, $actual['quantity'] );
		};
	}

	/**
	 * An assertion requiring draftCartItem's own `giftMessage` key to equal
	 * the seeded value, for every shape.
	 *
	 * @return \Closure function( mixed $actual, mixed $seed ): void
	 */
	private function gift_message_passthrough(): \Closure {
		return function ( $actual, $seed ): void {
			$this->assertIsArray( $actual );
			$this->assert_shape_equal( $seed, $actual['giftMessage'] );
		};
	}

	/**
	 * The template sweep entries: the template itself, its productId and
	 * its variation, with no record and no context to compete with it.
	 *
	 * @param array $state   The template-only state.
	 * @param array $context An empty context.
	 * @return array<string, array<string, mixed>>
	 */
	private function sweep_entries_template( array $state, array $context ): array {
		$entries = array();

		$entries['template'] = array(
			'seed'    => $this->seed_at( $state, $context, 'state', array( 'template' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array(
				'default' => array(
					'productId' => $this->absent(),
					'variation' => $this->fixed( array() ),
				),
			),
			'inside'  => array(
				'list' => array(
					'productId' => $this->absent(),
					'variation' => $this->fixed( array() ),
				),
				'map'  => array(
					'productId' => $this->absent(),
					'variation' => $this->fixed( array() ),
				),
			),
			'control' => array(
				'value'  => array(
					'productId' => 8003,
					'variation' => array(),
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 8003, $envelope['productId']() );
				},
			),
		);

		$entries['template.productId'] = array(
			'seed'    => $this->seed_at( $state, $context, 'state', array( 'template', 'productId' ) ),
			'expects' => array( 'int', 'string' ),
			'outside' => array( 'default' => array( 'productId' => $this->absent() ) ),
			'inside'  => array(
				'int'    => array( 'productId' => $this->fixed( 42 ) ),
				'string' => array( 'productId' => $this->fixed( 'x' ) ),
			),
			'control' => array(
				'value'  => 8004,
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 8004, $envelope['productId']() );
				},
			),
		);

		$entries['template.variation'] = array(
			'seed'    => $this->seed_at( $state, $context, 'state', array( 'template', 'variation' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'variation' => $this->fixed( array() ) ) ),
			'inside'  => array(
				'list' => array( 'variation' => $this->fixed( array( 'x' ) ) ),
				'map'  => array( 'variation' => $this->fixed( array( 'k' => 'v' ) ) ),
			),
			'control' => array(
				'value'  => array(
					array(
						'attribute' => 'colour',
						'value'     => 'silver',
					),
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame(
						array(
							array(
								'attribute' => 'colour',
								'value'     => 'silver',
							),
						),
						$envelope['variation']()
					);
				},
			),
		);

		return $entries;
	}

	/**
	 * The context-plane sweep entries whose fallback is the template:
	 * scopeName, productId and variation.
	 *
	 * @param array $state   The context-only state (a well-formed template, no record).
	 * @param array $context The well-formed context.
	 * @return array<string, array<string, mixed>>
	 */
	private function sweep_entries_context( array $state, array $context ): array {
		$entries = array();

		$entries['scopeName'] = array(
			'seed'    => $this->seed_at( $state, $context, 'context', array( 'scopeName' ) ),
			'expects' => array( 'int', 'string' ),
			'outside' => array( 'default' => array( 'scopeName' => $this->fixed( '_default' ) ) ),
			'inside'  => array(
				'int'    => array( 'scopeName' => $this->fixed( 42 ) ),
				'string' => array( 'scopeName' => $this->fixed( 'x' ) ),
			),
			'control' => array(
				'value'  => 'my-scope',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'my-scope', $envelope['scopeName']() );
				},
			),
		);

		$entries['productId'] = array(
			'seed'    => $this->seed_at( $state, $context, 'context', array( 'productId' ) ),
			'expects' => array( 'int', 'string' ),
			'outside' => array( 'default' => array( 'productId' => $this->fixed( 7001 ) ) ),
			'inside'  => array(
				'int'    => array( 'productId' => $this->fixed( 42 ) ),
				'string' => array( 'productId' => $this->fixed( 'x' ) ),
			),
			'control' => array(
				'value'  => 8005,
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 8005, $envelope['productId']() );
				},
			),
		);

		$entries['variation'] = array(
			'seed'    => $this->seed_at( $state, $context, 'context', array( 'variation' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'variation' => $this->fixed( $state['template']['variation'] ) ) ),
			'inside'  => array(
				'list' => array( 'variation' => $this->fixed( array( 'x' ) ) ),
				'map'  => array( 'variation' => $this->fixed( array( 'k' => 'v' ) ) ),
			),
			'control' => array(
				'value'  => array(
					array(
						'attribute' => 'colour',
						'value'     => 'bronze',
					),
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame(
						array(
							array(
								'attribute' => 'colour',
								'value'     => 'bronze',
							),
						),
						$envelope['variation']()
					);
				},
			),
		);

		return $entries;
	}

	/**
	 * The sweep entries about the base product itself: the products map
	 * and one entry, plus the two rows whose reachability requires a
	 * selection that matches no summary (products[<id>].id) or one
	 * (productVariations[<id>].id) — the latter is built alongside the
	 * variation entries below, since it needs a match.
	 *
	 * @param array $state     The matching-product state.
	 * @param array $unmatched A context whose selection matches no variation summary.
	 * @return array<string, array<string, mixed>>
	 */
	private function sweep_entries_base_product( array $state, array $unmatched ): array {
		$entries = array();

		$entries['products (the map)'] = array(
			'seed'    => $this->seed_at( $state, $unmatched, 'state', array( 'products' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array(
				'default' => array(
					'baseProduct'      => $this->absent(),
					'productVariation' => $this->absent(),
					'product'          => $this->absent(),
					'cartItem'         => $this->absent(),
				),
			),
			'inside'  => array(
				'list' => array(
					'baseProduct'      => $this->absent(),
					'productVariation' => $this->absent(),
					'product'          => $this->absent(),
					'cartItem'         => $this->absent(),
				),
				'map'  => array(
					'baseProduct'      => $this->absent(),
					'productVariation' => $this->absent(),
					'product'          => $this->absent(),
					'cartItem'         => $this->absent(),
				),
			),
			'control' => array(
				'value'  => $state['products'],
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 5001, $envelope['baseProduct']()['id'] );
				},
			),
		);

		$entries['products[<id>] (the entry)'] = array(
			'seed'    => $this->seed_at( $state, $unmatched, 'state', array( 'products', 5001 ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array(
				'default' => array(
					'baseProduct'      => $this->absent(),
					'productVariation' => $this->absent(),
					'product'          => $this->absent(),
					'cartItem'         => $this->absent(),
				),
			),
			'inside'  => array(
				'list' => array(
					'baseProduct'      => $this->passthrough(),
					'productVariation' => $this->absent(),
					'product'          => $this->passthrough(),
					'cartItem'         => $this->absent(),
				),
				'map'  => array(
					'baseProduct'      => $this->passthrough(),
					'productVariation' => $this->absent(),
					'product'          => $this->passthrough(),
					'cartItem'         => $this->absent(),
				),
			),
			'control' => array(
				'value'  => array(
					'id'         => 9030,
					'variations' => array(),
					'attributes' => array(),
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 9030, $envelope['baseProduct']()['id'] );
					$this->assertSame( 9030, $envelope['product']()['id'] );
				},
			),
		);

		$entries['products[<id>].id'] = array(
			'seed'    => $this->seed_at( $state, $unmatched, 'state', array( 'products', 5001, 'id' ) ),
			'expects' => array( 'int', 'string' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array(
				'int'    => array( 'cartItem' => $this->absent() ),
				'string' => array( 'cartItem' => $this->absent() ),
			),
			'control' => array(
				'value'  => 5001,
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-simple', $envelope['cartItem']()['key'] );
				},
			),
		);

		return $entries;
	}

	/**
	 * The sweep entries about the base product's variations list and the
	 * matched summary's own content: the list itself, the summary, its
	 * attributes container, an attribute entry, an attribute's name and
	 * value, and the summary's own id (and productVariations[<id>].id,
	 * which needs the same matching selection).
	 *
	 * @param array $state   The matching-product state.
	 * @param array $matched A context whose selection matches variation 6002.
	 * @return array<string, array<string, mixed>>
	 */
	private function sweep_entries_variations( array $state, array $matched ): array {
		$entries = array();

		$entries['products[<id>].variations'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'products', 5001, 'variations' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array(
				'default' => array(
					'productVariation' => $this->absent(),
					'product'          => $this->entry_with_id( 5001 ),
				),
			),
			'inside'  => array(
				'list' => array(
					'productVariation' => $this->absent(),
					'product'          => $this->entry_with_id( 5001 ),
				),
				'map'  => array(
					'productVariation' => $this->absent(),
					'product'          => $this->entry_with_id( 5001 ),
				),
			),
			'control' => array(
				'value'  => $state['products'][5001]['variations'],
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 6002, $envelope['productVariation']()['id'] );
				},
			),
		);

		$summary_state = $state;
		self::place(
			$summary_state,
			array( 'products', 5001, 'variations' ),
			array(
				null,
				array(
					'id'         => 6002,
					'attributes' => array(
						array(
							'name'  => 'colour',
							'value' => 'red',
						),
					),
				),
			)
		);
		$entries['products[<id>].variations[k] (the summary)'] = array(
			'seed'    => $this->seed_at( $summary_state, $matched, 'state', array( 'products', 5001, 'variations', 0 ) ),
			'expects' => array(),
			'outside' => array(
				'default' => array(
					'productVariation' => $this->entry_with_id( 6002 ),
					'product'          => $this->entry_with_id( 6002 ),
				),
			),
			'inside'  => array(),
			'control' => array(
				'value'  => array(
					'id'         => 9002,
					'attributes' => array(
						array(
							'name'  => 'colour',
							'value' => 'red',
						),
					),
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 9002, $envelope['productVariation']()['id'] );
				},
			),
		);

		$attributes_state = $state;
		self::place(
			$attributes_state,
			array( 'products', 5001, 'variations' ),
			array(
				array( 'id' => 9001 ),
				array(
					'id'         => 6002,
					'attributes' => array(
						array(
							'name'  => 'colour',
							'value' => 'red',
						),
					),
				),
			)
		);
		$entries['…variations[k].attributes'] = array(
			'seed'    => $this->seed_at( $attributes_state, $matched, 'state', array( 'products', 5001, 'variations', 0, 'attributes' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array(
				'default' => array(
					'productVariation' => $this->entry_with_id( 6002 ),
					'product'          => $this->entry_with_id( 6002 ),
				),
			),
			'inside'  => array(
				'list' => array(
					'productVariation' => $this->entry_with_id( 6002 ),
					'product'          => $this->entry_with_id( 6002 ),
				),
				'map'  => array(
					'productVariation' => $this->entry_with_id( 6002 ),
					'product'          => $this->entry_with_id( 6002 ),
				),
			),
			'control' => array(
				'value'  => array(
					array(
						'name'  => 'colour',
						'value' => 'red',
					),
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 9001, $envelope['productVariation']()['id'] );
				},
			),
		);

		$attribute_entry_state = $attributes_state;
		self::place(
			$attribute_entry_state,
			array( 'products', 5001, 'variations', 0 ),
			array(
				'id'         => 9003,
				'attributes' => array( null ),
			)
		);
		$entries['…variations[k].attributes[j] (the entry)'] = array(
			'seed'    => $this->seed_at( $attribute_entry_state, $matched, 'state', array( 'products', 5001, 'variations', 0, 'attributes', 0 ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'productVariation' => $this->entry_with_id( 6002 ) ) ),
			'inside'  => array(
				'list' => array( 'productVariation' => $this->entry_with_id( 6002 ) ),
				'map'  => array( 'productVariation' => $this->entry_with_id( 6002 ) ),
			),
			'control' => array(
				'value'  => array(
					'name'  => 'colour',
					'value' => 'red',
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 9003, $envelope['productVariation']()['id'] );
				},
			),
		);

		$attribute_name_state = $attributes_state;
		self::place(
			$attribute_name_state,
			array( 'products', 5001, 'variations', 0 ),
			array(
				'id'         => 9004,
				'attributes' => array(
					array(
						'name'  => null,
						'value' => 'red',
					),
				),
			)
		);
		$entries['…attributes[j].name'] = array(
			'seed'    => $this->seed_at( $attribute_name_state, $matched, 'state', array( 'products', 5001, 'variations', 0, 'attributes', 0, 'name' ) ),
			'expects' => array( 'string' ),
			'outside' => array( 'default' => array( 'productVariation' => $this->entry_with_id( 6002 ) ) ),
			'inside'  => array( 'string' => array( 'productVariation' => $this->entry_with_id( 6002 ) ) ),
			'control' => array(
				'value'  => 'colour',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 9004, $envelope['productVariation']()['id'] );
				},
			),
		);

		$attribute_value_state = $attributes_state;
		self::place(
			$attribute_value_state,
			array( 'products', 5001, 'variations', 0 ),
			array(
				'id'         => 9005,
				'attributes' => array(
					array(
						'name'  => 'colour',
						'value' => null,
					),
				),
			)
		);
		$entries['…attributes[j].value'] = array(
			'seed'    => $this->seed_at( $attribute_value_state, $matched, 'state', array( 'products', 5001, 'variations', 0, 'attributes', 0, 'value' ) ),
			'expects' => array(),
			'outside' => array(
				'default'  => array( 'productVariation' => $this->entry_with_id( 6002 ) ),
				'override' => array( 'null' => array( 'productVariation' => $this->entry_with_id( 9005 ) ) ),
			),
			'inside'  => array(),
			'control' => array(
				'value'  => 'red',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 9005, $envelope['productVariation']()['id'] );
				},
			),
		);

		$id_state = $state;
		self::place(
			$id_state,
			array( 'products', 5001, 'variations' ),
			array(
				array(
					'id'         => null,
					'attributes' => array(
						array(
							'name'  => 'colour',
							'value' => 'red',
						),
					),
				),
				array(
					'id'         => 6001,
					'attributes' => array(
						array(
							'name'  => 'colour',
							'value' => 'blue',
						),
					),
				),
			)
		);
		$entries['…variations[k].id'] = array(
			'seed'    => $this->seed_at( $id_state, $matched, 'state', array( 'products', 5001, 'variations', 0, 'id' ) ),
			'expects' => array( 'int', 'string' ),
			'outside' => array(
				'default' => array(
					'productVariation' => $this->absent(),
					'product'          => $this->entry_with_id( 5001 ),
				),
			),
			'inside'  => array(
				'int'    => array(
					'productVariation' => $this->absent(),
					'product'          => $this->entry_with_id( 5001 ),
				),
				'string' => array(
					'productVariation' => $this->absent(),
					'product'          => $this->entry_with_id( 5001 ),
				),
			),
			'control' => array(
				'value'  => 6002,
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 6002, $envelope['productVariation']()['id'] );
				},
			),
		);

		$entries['productVariations[<id>].id'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'productVariations', 6002, 'id' ) ),
			'expects' => array( 'int', 'string' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array(
				'int'    => array( 'cartItem' => $this->absent() ),
				'string' => array( 'cartItem' => $this->absent() ),
			),
			'control' => array(
				'value'  => 6002,
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		return $entries;
	}

	/**
	 * The sweep entries about the productVariations map, one of its
	 * entries, and that entry's own parent.
	 *
	 * @param array $state   The matching-product state.
	 * @param array $matched A context whose selection matches variation 6002.
	 * @return array<string, array<string, mixed>>
	 */
	private function sweep_entries_product_variations( array $state, array $matched ): array {
		$entries = array();

		$entries['productVariations (the map)'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'productVariations' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array(
				'default' => array(
					'productVariation' => $this->absent(),
					'product'          => $this->entry_with_id( 5001 ),
					'cartItem'         => $this->entry_with_key( 'line-simple' ),
				),
			),
			'inside'  => array(
				'list' => array(
					'productVariation' => $this->absent(),
					'product'          => $this->entry_with_id( 5001 ),
					'cartItem'         => $this->entry_with_key( 'line-simple' ),
				),
				'map'  => array(
					'productVariation' => $this->absent(),
					'product'          => $this->entry_with_id( 5001 ),
					'cartItem'         => $this->entry_with_key( 'line-simple' ),
				),
			),
			'control' => array(
				'value'  => $state['productVariations'],
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 6002, $envelope['productVariation']()['id'] );
				},
			),
		);

		$entries['productVariations[<id>] (the entry)'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'productVariations', 6002 ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array(
				'default' => array(
					'productVariation' => $this->absent(),
					'product'          => $this->entry_with_id( 5001 ),
				),
			),
			'inside'  => array(
				'list' => array(
					'productVariation' => $this->passthrough(),
					'product'          => $this->passthrough(),
				),
				'map'  => array(
					'productVariation' => $this->passthrough(),
					'product'          => $this->passthrough(),
				),
			),
			'control' => array(
				'value'  => array(
					'marker' => 'present',
					'parent' => 5001,
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'present', $envelope['productVariation']()['marker'] );
				},
			),
		);

		$entries['productVariations[<id>].parent'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'productVariations', 6002, 'parent' ) ),
			'expects' => array( 'int', 'string' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array(
				'int'    => array( 'cartItem' => $this->absent() ),
				'string' => array( 'cartItem' => $this->absent() ),
			),
			'control' => array(
				'value'  => 5001,
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		return $entries;
	}

	/**
	 * The sweep entries about the parent product's attributes and their
	 * terms, observed through cart-item attribute matching: a cart line's
	 * own label ("Scarlet") resolves through a matching term to its slug
	 * ("red"), which is what a selection names, so a broken lookup shows
	 * up as no cart line matching rather than the label coinciding with
	 * the slug by letter case.
	 *
	 * @param array $state   The matching-product state.
	 * @param array $matched A context whose selection matches variation 6002.
	 * @return array<string, array<string, mixed>>
	 */
	private function sweep_entries_attributes_and_terms( array $state, array $matched ): array {
		$entries = array();

		$entries['products[<parent>].attributes'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'products', 5001, 'attributes' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array(
				'list' => array( 'cartItem' => $this->absent() ),
				'map'  => array( 'cartItem' => $this->absent() ),
			),
			'control' => array(
				'value'  => $state['products'][5001]['attributes'],
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		$entries['…attributes[k] (the product attribute)'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'products', 5001, 'attributes', 0 ) ),
			'expects' => array( 'list', 'map', 'stdClass' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array(
				'list'     => array( 'cartItem' => $this->absent() ),
				'map'      => array( 'cartItem' => $this->absent() ),
				'stdClass' => array( 'cartItem' => $this->absent() ),
			),
			'control' => array(
				'value'  => $state['products'][5001]['attributes'][0],
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		$entries['…attributes[k].name'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'products', 5001, 'attributes', 0, 'name' ) ),
			'expects' => array( 'string' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array( 'string' => array( 'cartItem' => $this->absent() ) ),
			'control' => array(
				'value'  => 'colour',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		$entries['…attributes[k].terms'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'products', 5001, 'attributes', 0, 'terms' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array(
				'list' => array( 'cartItem' => $this->absent() ),
				'map'  => array( 'cartItem' => $this->absent() ),
			),
			'control' => array(
				'value'  => $state['products'][5001]['attributes'][0]['terms'],
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		$entries['…terms[j]'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'products', 5001, 'attributes', 0, 'terms', 0 ) ),
			'expects' => array( 'list', 'map', 'stdClass' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array(
				'list'     => array( 'cartItem' => $this->absent() ),
				'map'      => array( 'cartItem' => $this->absent() ),
				'stdClass' => array( 'cartItem' => $this->absent() ),
			),
			'control' => array(
				'value'  => array(
					'name' => 'Scarlet',
					'slug' => 'red',
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		$entries['…terms[j].name'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'products', 5001, 'attributes', 0, 'terms', 0, 'name' ) ),
			'expects' => array(),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array(),
			'control' => array(
				'value'  => 'Scarlet',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		$entries['…terms[j].slug'] = array(
			'seed'    => $this->seed_at( $state, $matched, 'state', array( 'products', 5001, 'attributes', 0, 'terms', 0, 'slug' ) ),
			'expects' => array( 'string' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array( 'string' => array( 'cartItem' => $this->absent() ) ),
			'control' => array(
				'value'  => 'red',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		return $entries;
	}

	/**
	 * The sweep entries about the cart itself: its base, its items list,
	 * one line, that line's key, type and id, and the resolved product's
	 * own reachability rows.
	 *
	 * @param array $cart_state     A state carrying one simple product (6002) and two cart lines matching it by id.
	 * @param array $cart_context   A context naming that product.
	 * @param array $matching_state The matching-product state, for the entries needing attribute matching.
	 * @param array $matched        A context whose selection matches variation 6002.
	 * @return array<string, array<string, mixed>>
	 */
	private function sweep_entries_cart( array $cart_state, array $cart_context, array $matching_state, array $matched ): array {
		$entries = array();

		$entries['cart, as the base of [\'items\']'] = array(
			'seed'    => $this->seed_at( $cart_state, $cart_context, 'state', array( 'cart' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array(
				'list' => array( 'cartItem' => $this->absent() ),
				'map'  => array( 'cartItem' => $this->absent() ),
			),
			'control' => array(
				'value'  => array(
					'items' => array(
						array(
							'key'  => 'line-target',
							'id'   => 6002,
							'type' => 'simple',
						),
					),
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-target', $envelope['cartItem']()['key'] );
				},
			),
		);

		$entries['cart.items'] = array(
			'seed'    => $this->seed_at( $cart_state, $cart_context, 'state', array( 'cart', 'items' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->absent() ) ),
			'inside'  => array(
				'list' => array( 'cartItem' => $this->absent() ),
				'map'  => array( 'cartItem' => $this->absent() ),
			),
			'control' => array(
				'value'  => array(
					array(
						'key'  => 'line-target',
						'id'   => 6002,
						'type' => 'simple',
					),
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-target', $envelope['cartItem']()['key'] );
				},
			),
		);

		$entries['cart.items[n] (the entry)'] = array(
			'seed'    => $this->seed_at( $cart_state, $cart_context, 'state', array( 'cart', 'items', 0 ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ) ),
			'inside'  => array(
				'list' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ),
				'map'  => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ),
			),
			'control' => array(
				'value'  => array(
					'key'  => 'line-target',
					'id'   => 6002,
					'type' => 'simple',
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-target', $envelope['cartItem']()['key'] );
				},
			),
		);

		$key_state = $cart_state;
		self::place(
			$key_state,
			array( 'cart', 'items' ),
			array(
				array(
					'key'  => null,
					'id'   => 111,
					'type' => 'simple',
				),
				array(
					'key'  => 'target-key',
					'id'   => 222,
					'type' => 'simple',
				),
			)
		);
		$entries['cart.items[n].key'] = array(
			'seed'    => $this->seed_at(
				$key_state,
				array(
					'productId'   => 6002,
					'cartItemKey' => 'target-key',
				),
				'state',
				array( 'cart', 'items', 0, 'key' )
			),
			'expects' => array(),
			'outside' => array( 'default' => array( 'cartItem' => $this->entry_with_id( 222 ) ) ),
			'inside'  => array(),
			'control' => array(
				'value'  => 'target-key',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 111, $envelope['cartItem']()['id'] );
				},
			),
		);

		$type_state = $cart_state;
		self::place(
			$type_state,
			array( 'cart', 'items' ),
			array(
				array(
					'key'  => 'line-0',
					'id'   => 6002,
					'type' => null,
				),
			)
		);
		$entries['cart.items[n].type'] = array(
			'seed'    => $this->seed_at( $type_state, $cart_context, 'state', array( 'cart', 'items', 0, 'type' ) ),
			'expects' => array(),
			'outside' => array( 'default' => array( 'cartItem' => $this->entry_with_key( 'line-0' ) ) ),
			'inside'  => array(),
			'control' => array(
				'value'  => 'variation',
				'assert' => function ( array $envelope ): void {
					$this->assertNull( $envelope['cartItem']() );
				},
			),
		);

		$entries['cart.items[n].id'] = array(
			'seed'    => $this->seed_at( $cart_state, $cart_context, 'state', array( 'cart', 'items', 0, 'id' ) ),
			'expects' => array( 'int', 'string' ),
			'outside' => array(
				'default'  => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ),
				'override' => array( 'float' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ) ),
			),
			'inside'  => array(
				'int'    => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ),
				'string' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ),
			),
			'control' => array(
				'value'  => 6002,
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-target', $envelope['cartItem']()['key'] );
				},
			),
		);

		$variation_container_state = $matching_state;
		self::place(
			$variation_container_state,
			array( 'cart', 'items' ),
			array(
				array(
					'key'       => 'line-red',
					'id'        => 6002,
					'type'      => 'variation',
					'variation' => null,
				),
				array(
					'key'  => 'line-fallback',
					'id'   => 6002,
					'type' => 'simple',
				),
			)
		);
		$entries['cart.items[n].variation'] = array(
			'seed'    => $this->seed_at( $variation_container_state, $matched, 'state', array( 'cart', 'items', 0, 'variation' ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ) ),
			'inside'  => array(
				'list' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ),
				'map'  => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ),
			),
			'control' => array(
				'value'  => array(
					array(
						'attribute' => 'colour',
						'value'     => 'Scarlet',
					),
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		$variation_entry_state = $matching_state;
		self::place(
			$variation_entry_state,
			array( 'cart', 'items' ),
			array(
				array(
					'key'       => 'line-red',
					'id'        => 6002,
					'type'      => 'variation',
					'variation' => array( null ),
				),
				array(
					'key'  => 'line-fallback',
					'id'   => 6002,
					'type' => 'simple',
				),
			)
		);
		$entries['cart.items[n].variation[k] (the entry)'] = array(
			'seed'    => $this->seed_at( $variation_entry_state, $matched, 'state', array( 'cart', 'items', 0, 'variation', 0 ) ),
			'expects' => array( 'list', 'map' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ) ),
			'inside'  => array(
				'list' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ),
				'map'  => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ),
			),
			'control' => array(
				'value'  => array(
					'attribute' => 'colour',
					'value'     => 'Scarlet',
				),
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		$attribute_state = $matching_state;
		self::place(
			$attribute_state,
			array( 'cart', 'items' ),
			array(
				array(
					'key'       => 'line-red',
					'id'        => 6002,
					'type'      => 'variation',
					'variation' => array(
						array(
							'attribute' => null,
							'value'     => 'Scarlet',
						),
					),
				),
				array(
					'key'  => 'line-fallback',
					'id'   => 6002,
					'type' => 'simple',
				),
			)
		);
		$entries['…variation[k].attribute'] = array(
			'seed'    => $this->seed_at( $attribute_state, $matched, 'state', array( 'cart', 'items', 0, 'variation', 0, 'attribute' ) ),
			'expects' => array( 'string' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ) ),
			'inside'  => array( 'string' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ) ),
			'control' => array(
				'value'  => 'colour',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		$value_state = $matching_state;
		self::place(
			$value_state,
			array( 'cart', 'items' ),
			array(
				array(
					'key'       => 'line-red',
					'id'        => 6002,
					'type'      => 'variation',
					'variation' => array(
						array(
							'attribute' => 'colour',
							'value'     => null,
						),
					),
				),
				array(
					'key'  => 'line-fallback',
					'id'   => 6002,
					'type' => 'simple',
				),
			)
		);
		$entries['…variation[k].value'] = array(
			'seed'    => $this->seed_at( $value_state, $matched, 'state', array( 'cart', 'items', 0, 'variation', 0, 'value' ) ),
			'expects' => array( 'string' ),
			'outside' => array( 'default' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ) ),
			'inside'  => array( 'string' => array( 'cartItem' => $this->entry_with_key( 'line-fallback' ) ) ),
			'control' => array(
				'value'  => 'Scarlet',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
				},
			),
		);

		return $entries;
	}

	/**
	 * The six sweep entries duplicating the resolved selection's own
	 * entry, attribute and value across its three sources — the record's
	 * draftCartItem.variation, the context's variation, and the
	 * template's — plus the context-sourced base of each, nine in all.
	 *
	 * @param array $matching_state The matching-product state.
	 * @return array<string, array<string, mixed>>
	 */
	private function sweep_entries_resolved_selection( array $matching_state ): array {
		$sources = array(
			'context'  => array(
				'state'   => $matching_state,
				'context' => array(
					'productId' => 5001,
					'variation' => null,
				),
				'plane'   => 'context',
				'path'    => array( 'variation' ),
			),
			'record'   => array(
				'state'   => array_replace_recursive(
					$matching_state,
					array(
						'productScopes' => array(
							'_default' => array(
								'draftCartItem' => array(
									'id'        => 5001,
									'variation' => null,
								),
							),
						),
					)
				),
				'context' => array(),
				'plane'   => 'state',
				'path'    => array( 'productScopes', '_default', 'draftCartItem', 'variation' ),
			),
			'template' => array(
				'state'   => array_replace_recursive(
					$matching_state,
					array(
						'template' => array(
							'productId' => 5001,
							'variation' => null,
						),
					)
				),
				'context' => array(),
				'plane'   => 'state',
				'path'    => array( 'template', 'variation' ),
			),
		);

		$entries = array();

		foreach ( $sources as $source_name => $source ) {
			$entry_path     = array_merge( $source['path'], array( 0 ) );
			$attribute_path = array_merge( $entry_path, array( 'attribute' ) );
			$value_path     = array_merge( $entry_path, array( 'value' ) );
			$seeded_state   = $source['state'];
			$seeded_context = $source['context'];
			$baseline       = array(
				array(
					'attribute' => 'colour',
					'value'     => 'red',
				),
			);
			if ( 'context' === $source['plane'] ) {
				self::place( $seeded_context, $source['path'], $baseline );
			} else {
				self::place( $seeded_state, $source['path'], $baseline );
			}

			$entries[ "variation[n] (an entry of the resolved selection, from record, context or template) — {$source_name}" ] = array(
				'seed'    => $this->seed_at( $seeded_state, $seeded_context, $source['plane'], $entry_path ),
				'expects' => array( 'list', 'map' ),
				'outside' => array(
					'default' => array(
						'productVariation' => $this->absent(),
						'product'          => $this->entry_with_id( 5001 ),
						'cartItem'         => $this->entry_with_key( 'line-simple' ),
					),
				),
				'inside'  => array(
					'list' => array(
						'productVariation' => $this->absent(),
						'product'          => $this->entry_with_id( 5001 ),
						'cartItem'         => $this->entry_with_key( 'line-simple' ),
					),
					'map'  => array(
						'productVariation' => $this->absent(),
						'product'          => $this->entry_with_id( 5001 ),
						'cartItem'         => $this->entry_with_key( 'line-simple' ),
					),
				),
				'control' => array(
					'value'  => array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
					'assert' => function ( array $envelope ): void {
						$this->assertSame( 6002, $envelope['productVariation']()['id'] );
						$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
					},
				),
			);

			$entries[ "variation[n].attribute — {$source_name}" ] = array(
				'seed'    => $this->seed_at( $seeded_state, $seeded_context, $source['plane'], $attribute_path ),
				'expects' => array( 'string' ),
				'outside' => array(
					'default' => array(
						'productVariation' => $this->absent(),
						'product'          => $this->entry_with_id( 5001 ),
						'cartItem'         => $this->entry_with_key( 'line-simple' ),
					),
				),
				'inside'  => array(
					'string' => array(
						'productVariation' => $this->absent(),
						'product'          => $this->entry_with_id( 5001 ),
						'cartItem'         => $this->entry_with_key( 'line-simple' ),
					),
				),
				'control' => array(
					'value'  => 'colour',
					'assert' => function ( array $envelope ): void {
						$this->assertSame( 6002, $envelope['productVariation']()['id'] );
					},
				),
			);

			$entries[ "variation[n].value — {$source_name}" ] = array(
				'seed'    => $this->seed_at( $seeded_state, $seeded_context, $source['plane'], $value_path ),
				'expects' => array( 'string' ),
				'outside' => array(
					'default' => array(
						'productVariation' => $this->absent(),
						'product'          => $this->entry_with_id( 5001 ),
						'cartItem'         => $this->entry_with_key( 'line-simple' ),
					),
				),
				'inside'  => array(
					'string' => array(
						'productVariation' => $this->absent(),
						'product'          => $this->entry_with_id( 5001 ),
						'cartItem'         => $this->entry_with_key( 'line-simple' ),
					),
				),
				'control' => array(
					'value'  => 'red',
					'assert' => function ( array $envelope ): void {
						$this->assertSame( 6002, $envelope['productVariation']()['id'] );
						$this->assertSame( 'line-red', $envelope['cartItem']()['key'] );
					},
				),
			);
		}

		$cart_only_state        = $this->cart_matching_state();
		$cart_only_context      = array( 'productId' => 6002 );
		$entries['cartItemKey'] = array(
			'seed'    => $this->seed_at( $cart_only_state, $cart_only_context, 'context', array( 'cartItemKey' ) ),
			'expects' => array(),
			'outside' => array(
				'default'  => array( 'cartItem' => $this->absent() ),
				'override' => array( 'null' => array( 'cartItem' => $this->entry_with_key( 'line-target' ) ) ),
			),
			'inside'  => array(),
			'control' => array(
				'value'  => 'line-fallback',
				'assert' => function ( array $envelope ): void {
					$this->assertSame( 'line-fallback', $envelope['cartItem']()['key'] );
				},
			),
		);

		return $entries;
	}

	/**
	 * Register an error handler that turns every diagnostic — including a
	 * deprecation, which this suite's configuration and bootstrap would
	 * otherwise both let through — into a thrown exception, for the
	 * duration of resolving one sweep case. Registered for E_ALL and does
	 * not consult error_reporting(), so it fires despite the bootstrap's
	 * mask.
	 *
	 * @return \Closure The handler, ready to pass to set_error_handler().
	 */
	private static function diagnostics_as_exceptions(): \Closure {
		return function ( int $errno, string $errstr, string $errfile = '', int $errline = 0 ): bool {
			throw new \ErrorException( $errstr, 0, $errno, $errfile, $errline ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Carrying the diagnostic's own message into the failure is the assertion.
		};
	}

	/**
	 * Every path a third party can seed, crossed with every shape class:
	 * one data set per cell of the sweep, named after the path and the
	 * shape so `--testdox` prints the whole sweep one line per cell.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provider_sweep_cells(): array {
		$rows = array();
		foreach ( array_keys( $this->sweep_entries() ) as $path ) {
			foreach ( array_keys( $this->shape_classes() ) as $shape ) {
				$rows[ "$path | $shape" ] = array( $path, $shape );
			}
		}
		return $rows;
	}

	/**
	 * One data set per sweep entry, for its control case.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function provider_sweep_controls(): array {
		$rows = array();
		foreach ( array_keys( $this->sweep_entries() ) as $path ) {
			$rows[ $path ] = array( $path );
		}
		return $rows;
	}

	/**
	 * @testdox sweep cell: $path | $shape_key
	 * @dataProvider provider_sweep_cells
	 * @param string $path      The sweep entry's path text.
	 * @param string $shape_key The shape class seeded at that path.
	 */
	public function test_sweep_cell_resolves_as_required( string $path, string $shape_key ): void {
		$entry = $this->sweep_entries()[ $path ];
		$value = $this->shape_classes()[ $shape_key ];

		$members = in_array( $shape_key, $entry['expects'], true )
			? $entry['inside'][ $shape_key ]
			: $this->outside_members( $entry['outside'], $shape_key );

		$scope_getter = $entry['seed']( $value );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Turning every diagnostic into a failure is the assertion; this suite's configuration and bootstrap would otherwise let a deprecation through uncaught.
		set_error_handler( self::diagnostics_as_exceptions(), E_ALL );
		try {
			$envelope = $scope_getter();
			foreach ( $members as $member => $assertion ) {
				$assertion( $envelope[ $member ](), $value );
			}
		} finally {
			restore_error_handler();
		}
	}

	/**
	 * @testdox sweep control: $path
	 * @dataProvider provider_sweep_controls
	 * @param string $path The sweep entry's path text.
	 */
	public function test_sweep_control_case_reaches_its_read( string $path ): void {
		$entry = $this->sweep_entries()[ $path ];

		$scope_getter = $entry['seed']( $entry['control']['value'] );
		$envelope     = $scope_getter();

		( $entry['control']['assert'] )( $envelope );
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
