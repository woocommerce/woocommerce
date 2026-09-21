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
	 * @dataProvider provider_non_array_shapes
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
	 * @dataProvider provider_non_array_shapes
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
	 * @dataProvider provider_non_array_shapes
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
	 */
	public function test_product_id_resolves_without_fatal_when_record_draft_id_is_not_an_array_key(): void {
		$product = WC_Helper_Product::create_simple_product();

		TestedProductsStore::load_product( $this->consent, $product->get_id() );

		wp_interactivity_state(
			$this->store_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array( 'draftCartItem' => array( 'id' => array( 1, 2, 3 ) ) ),
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
	 * @dataProvider provider_non_array_shapes
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
	 * @dataProvider provider_non_array_shapes
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
	 * @dataProvider provider_non_array_shapes
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
	 * @dataProvider provider_non_array_shapes
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
	 * @dataProvider provider_non_array_shapes
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
	 * @dataProvider provider_non_array_shapes
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
	 * state.productScope.scopeName member and helpers deeper below the
	 * envelope's nine top-level reads.
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
	 * Shapes that are not arrays, for the data providers above: a string, a
	 * boolean and an integer, standing in for anything a seeded value in
	 * externally writable state could carry in place of an array.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provider_non_array_shapes(): array {
		return array(
			'a string'   => array( 'not-an-array' ),
			'a boolean'  => array( true ),
			'an integer' => array( 42 ),
		);
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
