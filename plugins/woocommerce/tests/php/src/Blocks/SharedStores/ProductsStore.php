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
	 * The Interactivity API namespace the raw product and variation data
	 * is seeded into.
	 *
	 * @var string
	 */
	protected $data_namespace = 'woocommerce';

	/**
	 * The Interactivity API store namespace under test for the selection
	 * state and derived getters.
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

		$state = wp_interactivity_state( $this->data_namespace );

		$this->assertArrayHasKey( 'products', $state );
		$this->assertArrayHasKey( $product->get_id(), $state['products'] );
		$this->assertSame( $product->get_name(), $state['products'][ $product->get_id() ]['name'] );
		$this->assertSame( $product->get_name(), $result['name'], 'Return value should contain the product data.' );

		$legacy_state = wp_interactivity_state( $this->store_namespace );
		$this->assertArrayNotHasKey( 'products', $legacy_state, 'The raw product data should not also be seeded into the legacy woocommerce/products namespace.' );

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

		$state = wp_interactivity_state( $this->data_namespace );

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

		$data = wp_interactivity_state( $this->data_namespace );
		$this->assertArrayHasKey( $purchasable->get_id(), $data['products'] );
		$this->assertArrayNotHasKey( $non_purchasable->get_id(), $data['products'] );

		$grouped->delete( true );
		$purchasable->delete( true );
		$non_purchasable->delete( true );
	}

	/**
	 * @testdox register_getters() registers the derived state closures exactly once, and also registers the productScope envelope.
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

		$data_state = wp_interactivity_state( $this->data_namespace );
		$this->assertArrayHasKey( 'productScope', $data_state );
		$this->assertInstanceOf( \Closure::class, $data_state['productScope'] );

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
	 * @testdox variation matching normalizes an attribute_pa_-prefixed selection and treats a null-valued attribute as "Any".
	 */
	public function test_variation_matching_normalizes_prefixed_attribute_and_treats_null_value_as_any(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array(
						'id'         => 1,
						'attributes' => array(
							array(
								'name'  => 'size',
								'terms' => array(
									array(
										'name' => 'huge',
										'slug' => 'huge',
									),
								),
							),
						),
						'variations' => array(
							array(
								'id'         => 2,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => null,
									),
									array(
										'name'  => 'size',
										'value' => 'huge',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					2 => array(
						'id'     => 2,
						'parent' => 1,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 1,
				'variation' => array(
					array(
						'attribute' => 'attribute_pa_colour',
						'value'     => 'red',
					),
					array(
						'attribute' => 'attribute_pa_size',
						'value'     => 'huge',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( 2, $resolved_variation['id'] );
	}

	/**
	 * @testdox variation matching resolves a candidate's term name to its slug before comparing, rather than comparing the raw label.
	 */
	public function test_variation_matching_resolves_term_slug_before_comparing(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array(
						'id'         => 1,
						'attributes' => array(
							array(
								'name'  => 'colour',
								'terms' => array(
									array(
										'name' => 'Bright Red',
										'slug' => 'bright-red',
									),
								),
							),
						),
						'variations' => array(
							array(
								'id'         => 2,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'Bright Red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					2 => array(
						'id'     => 2,
						'parent' => 1,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 1,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'bright-red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( 2, $resolved_variation['id'] );
	}

	/**
	 * @testdox state.productScope.product resolves a productId that is itself a loaded variation id directly, with .baseProduct its parent.
	 */
	public function test_product_scope_resolves_direct_variation_id(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					10 => array(
						'id'   => 10,
						'name' => 'Parent',
					),
				),
				'productVariations' => array(
					20 => array(
						'id'     => 20,
						'parent' => 10,
						'name'   => 'Variation',
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 20 ) );

		$envelope = $this->get_product_scope();

		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( 20, $resolved_variation['id'] );

		$resolved_product = $envelope['product']();
		$this->assertSame( 20, $resolved_product['id'] );

		$base_product = $envelope['baseProduct']();
		$this->assertIsArray( $base_product );
		$this->assertSame( 10, $base_product['id'] );
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
			$this->data_namespace,
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
			$this->data_namespace,
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
	 * @testdox a state.productScopes record's draftCartItem.variation wins over the declared context for the same scopeName.
	 */
	public function test_product_scope_record_variation_wins_over_declared_context(): void {
		$product  = WC_Helper_Product::create_simple_product();
		$selected = array(
			array(
				'attribute' => 'colour',
				'value'     => 'red',
			),
		);

		wp_interactivity_state(
			$this->data_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array(
						'draftCartItem' => array( 'variation' => $selected ),
					),
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

		$this->assertSame( $selected, $envelope['variation']() );

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
			$this->data_namespace,
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
	 * @testdox state.productScope.draftCartItem carries a record's extension props unchanged.
	 */
	public function test_draft_cart_item_passes_through_extension_props(): void {
		wp_interactivity_state(
			$this->data_namespace,
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
			$this->data_namespace,
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
			$this->data_namespace,
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
			$this->data_namespace,
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

		wp_interactivity_state( $this->data_namespace, array( 'cart' => array( 'items' => array() ) ) );

		$this->push_woocommerce_context( array( 'productId' => $product->get_id() ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );

		$product->delete( true );
	}

	/**
	 * @testdox state.productScope.cartItem falls back to pairing on the scope's productId when no product resolves.
	 */
	public function test_cart_item_pairs_on_product_id_when_no_product_resolves(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'cart' => array(
					'items' => array(
						array(
							'key'  => 'line-1',
							'id'   => 42,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 42 ) );

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-1', $cart_item['key'] );
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
	 * @testdox a non-array state.productScopes is treated as no record, so productId still resolves from the declared context.
	 * @dataProvider provider_object_shape
	 * @param mixed $malformed The one shape that can fail this read without the guard.
	 */
	public function test_product_scope_survives_non_array_product_scopes( $malformed ): void {
		wp_interactivity_state( $this->data_namespace, array( 'productScopes' => $malformed ) );

		$this->push_woocommerce_context(
			array(
				'scopeName' => 'my-scope',
				'productId' => 42,
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertSame( 42, $envelope['productId']() );
		$this->assertNull( $envelope['baseProduct']() );
	}

	/**
	 * @testdox state.productScope.scopeName falls back to _default when the declared context's scopeName is not usable as an array key.
	 * @dataProvider provider_shapes_not_usable_as_key
	 * @param mixed $malformed A shape that cannot be used as an array offset.
	 */
	public function test_scope_name_falls_back_to_default_when_not_usable_as_key( $malformed ): void {
		$this->push_woocommerce_context( array( 'scopeName' => $malformed ) );

		$envelope = $this->get_product_scope();

		$this->assertSame( '_default', $envelope['scopeName']() );
	}

	/**
	 * @testdox a non-array state.template is treated as absent, so productId falls back to its default instead of fataling.
	 * @dataProvider provider_non_array_shapes
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_product_scope_survives_non_array_template( $malformed ): void {
		wp_interactivity_state( $this->data_namespace, array( 'template' => $malformed ) );

		$this->push_woocommerce_context( array() );

		$envelope = $this->get_product_scope();

		$this->assertSame( 0, $envelope['productId']() );
	}

	/**
	 * @testdox productId falls through to state.template when the declared context's productId is not usable as an array key.
	 * @dataProvider provider_shapes_not_usable_as_key
	 * @param mixed $malformed A shape that cannot be used as an array offset.
	 */
	public function test_product_id_falls_through_to_template_when_context_value_is_not_usable_as_key( $malformed ): void {
		wp_interactivity_state( $this->data_namespace, array( 'template' => array( 'productId' => 42 ) ) );

		$this->push_woocommerce_context( array( 'productId' => $malformed ) );

		$envelope = $this->get_product_scope();

		$this->assertSame( 42, $envelope['productId']() );
	}

	/**
	 * @testdox productId falls back to its default when state.template's productId is not usable as an array key and the context declares none.
	 * @dataProvider provider_shapes_not_usable_as_key
	 * @param mixed $malformed A shape that cannot be used as an array offset.
	 */
	public function test_product_id_falls_back_to_default_when_template_value_is_not_usable_as_key( $malformed ): void {
		wp_interactivity_state( $this->data_namespace, array( 'template' => array( 'productId' => $malformed ) ) );

		$this->push_woocommerce_context( array() );

		$envelope = $this->get_product_scope();

		$this->assertSame( 0, $envelope['productId']() );
	}

	/**
	 * @testdox a non-array draftCartItem on the productScopes record is treated as absent, so productId and variation resolve from the declared context.
	 * @dataProvider provider_non_array_shapes
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_product_scope_survives_non_array_draft_cart_item( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array( 'draftCartItem' => $malformed ),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'scopeName' => 'my-scope',
				'productId' => 42,
				'variation' => array(),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertSame( 42, $envelope['productId']() );
		$this->assertSame( array(), $envelope['variation']() );
	}

	/**
	 * @testdox a draftCartItem.id that is not usable as an array key falls through to the declared context's productId.
	 * @dataProvider provider_shapes_not_usable_as_key
	 * @param mixed $malformed A shape that cannot be used as an array offset.
	 */
	public function test_draft_cart_item_id_falls_through_when_not_usable_as_key( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array( 'draftCartItem' => array( 'id' => $malformed ) ),
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

		$this->assertSame( 42, $envelope['productId']() );
		$this->assertSame( 42, $envelope['draftCartItem']()['id'] );
	}

	/**
	 * @testdox a draftCartItem.variation that is not an array falls through to the declared context's variation.
	 * @dataProvider provider_non_array_shapes
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_draft_cart_item_variation_falls_through_when_not_an_array( $malformed ): void {
		$selected = array(
			array(
				'attribute' => 'colour',
				'value'     => 'red',
			),
		);

		wp_interactivity_state(
			$this->data_namespace,
			array(
				'productScopes' => array(
					'my-scope' => array( 'draftCartItem' => array( 'variation' => $malformed ) ),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'scopeName' => 'my-scope',
				'productId' => 42,
				'variation' => $selected,
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertSame( $selected, $envelope['variation']() );
		$this->assertSame( $selected, $envelope['draftCartItem']()['variation'] );
	}

	/**
	 * @testdox a non-array state.productVariations is treated as absent, so no variation resolves and no fatal error is raised.
	 * @dataProvider provider_object_and_string_shapes
	 * @param mixed $malformed A shape that can fail this read without the guard.
	 */
	public function test_product_scope_survives_non_array_product_variations( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array( 1 => array( 'id' => 1 ) ),
				'productVariations' => $malformed,
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 1 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation']() );

		$resolved = $envelope['product']();
		$this->assertSame( 1, $resolved['id'] );
	}

	/**
	 * @testdox a non-array state.products is treated as absent, so baseProduct and product resolve to null instead of fataling.
	 * @dataProvider provider_object_and_string_shapes
	 * @param mixed $malformed A shape that can fail this read without the guard.
	 */
	public function test_product_scope_survives_non_array_products( $malformed ): void {
		wp_interactivity_state( $this->data_namespace, array( 'products' => $malformed ) );

		$this->push_woocommerce_context( array( 'productId' => 1 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['baseProduct']() );
		$this->assertNull( $envelope['product']() );
	}

	/**
	 * @testdox a non-array state.cart is treated as no cart lines, so cartItem resolves to null instead of fataling.
	 * @dataProvider provider_object_shape
	 * @param mixed $malformed The one shape that can fail this read without the guard.
	 */
	public function test_cart_item_survives_non_array_cart( $malformed ): void {
		wp_interactivity_state( $this->data_namespace, array( 'cart' => $malformed ) );

		$this->push_woocommerce_context( array( 'productId' => 1 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox a non-array state.cart.items is treated as no cart lines, so cartItem resolves to null instead of fataling.
	 * @dataProvider provider_object_shape
	 * @param mixed $malformed The one shape that can fail this read without the guard.
	 */
	public function test_cart_item_survives_non_array_cart_items( $malformed ): void {
		wp_interactivity_state( $this->data_namespace, array( 'cart' => array( 'items' => $malformed ) ) );

		$this->push_woocommerce_context( array( 'productId' => 1 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox a productVariations entry that is not itself an array is treated as no direct variation, falling back to a regular product lookup for the same id.
	 * @dataProvider provider_non_array_shapes
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_product_scope_survives_non_array_direct_variation_entry( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array( 5 => array( 'id' => 5 ) ),
				'productVariations' => array( 5 => $malformed ),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 5 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['productVariation']() );

		$resolved = $envelope['product']();
		$this->assertSame( 5, $resolved['id'], 'Should fall back to the regular product with the same id.' );
	}

	/**
	 * @testdox a direct variation's parent field that is not usable as an array key leaves baseProduct null without affecting productVariation or product.
	 * @dataProvider provider_shapes_not_usable_as_key
	 * @param mixed $malformed A shape that cannot be used as an array offset.
	 */
	public function test_direct_variation_survives_malformed_parent( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array( 'id' => 1 ),
				),
				'productVariations' => array(
					20 => array(
						'id'     => 20,
						'parent' => $malformed,
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 20 ) );

		$envelope = $this->get_product_scope();

		// products[1] is loaded so that, for the float row, truncating
		// 1.5 to 1 would resolve it as a wrong baseProduct instead of null.
		$this->assertNull( $envelope['baseProduct']() );

		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( 20, $resolved_variation['id'] );
	}

	/**
	 * @testdox a products entry that is not itself an array leaves baseProduct, productVariation and product all null instead of fataling.
	 * @dataProvider provider_non_array_shapes
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_product_scope_survives_non_array_base_product_entry( $malformed ): void {
		wp_interactivity_state( $this->data_namespace, array( 'products' => array( 7 => $malformed ) ) );

		$this->push_woocommerce_context( array( 'productId' => 7 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['baseProduct']() );
		$this->assertNull( $envelope['productVariation']() );
		$this->assertNull( $envelope['product']() );
	}

	/**
	 * @testdox a base product whose attributes field is not an array still matches a variation summary, falling back to comparing the raw selected label.
	 * @dataProvider provider_non_array_shapes
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_variation_matching_survives_non_array_product_attributes( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array(
						'id'         => 1,
						'attributes' => $malformed,
						'variations' => array(
							array(
								'id'         => 2,
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
					2 => array(
						'id'     => 2,
						'parent' => 1,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 1,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( 2, $resolved_variation['id'] );
	}

	/**
	 * @testdox a base product whose variations field is not an array leaves productVariation null, with product falling back to baseProduct.
	 * @dataProvider provider_non_iterable_shapes
	 * @param mixed $malformed A shape that fails foreach without the guard; an object is excluded, since foreach over an object is legal.
	 */
	public function test_variation_matching_survives_non_array_variations_list( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products' => array(
					1 => array(
						'id'         => 1,
						'variations' => $malformed,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 1,
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

		$resolved_product = $envelope['product']();
		$this->assertSame( 1, $resolved_product['id'] );
	}

	/**
	 * @testdox a variation summary whose attributes field is not an array does not match, and resolution continues to the next summary.
	 * @dataProvider provider_non_array_shapes
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_variation_matching_skips_summary_with_non_array_attributes( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array(
						'id'         => 1,
						'variations' => array(
							array(
								'id'         => 99,
								'attributes' => $malformed,
							),
							array(
								'id'         => 2,
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
					2 => array(
						'id'     => 2,
						'parent' => 1,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 1,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( 2, $resolved_variation['id'], 'Should skip the malformed summary and match the next one.' );
	}

	/**
	 * @testdox a matched variation summary whose id is not usable as an array key resolves productVariation to null.
	 * @dataProvider provider_shapes_not_usable_as_key
	 * @param mixed $malformed A shape that cannot be used as an array offset.
	 */
	public function test_variation_matching_survives_summary_id_not_usable_as_key( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					5 => array(
						'id'         => 5,
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
						),
					),
				),
				'productVariations' => array(
					1 => array(
						'id'     => 1,
						'parent' => 5,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 5,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		// productVariations[1] is loaded, and the base product's own id (5)
		// is deliberately different, so that for the float row, truncating
		// 1.5 to 1 would resolve it as a wrong match instead of null.
		$this->assertNull( $envelope['productVariation']() );
	}

	/**
	 * @testdox a matched variation summary whose id resolves to a non-array productVariations entry resolves productVariation to null.
	 * @dataProvider provider_non_array_shapes
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_variation_matching_survives_non_array_matched_variation_entry( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array(
						'id'         => 1,
						'variations' => array(
							array(
								'id'         => 2,
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
				'productVariations' => array( 2 => $malformed ),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 1,
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
	}

	/**
	 * @testdox a malformed variation-summary attribute entry, selection entry, or one of their name/attribute/value fields, does not match a real selection, without fataling.
	 * @dataProvider provider_malformed_variation_matching_fields
	 * @param mixed $candidate_attribute The candidate summary's single attribute entry.
	 * @param mixed $selection_entry     The declared selection's single entry.
	 */
	public function test_variation_matching_survives_malformed_single_field( $candidate_attribute, $selection_entry ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array(
						'id'         => 1,
						'variations' => array(
							array(
								'id'         => 2,
								'attributes' => array( $candidate_attribute ),
							),
						),
					),
				),
				'productVariations' => array(
					2 => array(
						'id'     => 2,
						'parent' => 1,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 1,
				'variation' => array( $selection_entry ),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull(
			$envelope['productVariation'](),
			'productVariations[2] is loaded, so a false match would resolve to that variation instead of null.'
		);
	}

	/**
	 * Each row keeps the candidate attribute and the selection entry
	 * well-formed except at the one malformed field the row names, so a
	 * false match points at exactly that field's guard.
	 *
	 * @return array<string, array{0: mixed, 1: mixed}>
	 */
	public function provider_malformed_variation_matching_fields(): array {
		$attribute       = array(
			'name'  => 'colour',
			'value' => 'red',
		);
		$selection       = array(
			'attribute' => 'colour',
			'value'     => 'red',
		);
		$non_array_entry = (object) array( 'k' => 'v' );

		return array(
			'attribute entry'     => array( $non_array_entry, $selection ),
			'selection entry'     => array( $attribute, $non_array_entry ),
			'attribute name'      => array(
				array(
					'name'  => array( 'colour' ),
					'value' => 'red',
				),
				$selection,
			),
			'attribute value'     => array(
				array(
					'name'  => 'colour',
					'value' => array( 'red' ),
				),
				$selection,
			),
			'selection attribute' => array(
				$attribute,
				array(
					'attribute' => array( 'colour' ),
					'value'     => 'red',
				),
			),
			'selection value'     => array(
				$attribute,
				array(
					'attribute' => 'colour',
					'value'     => array( 'red' ),
				),
			),
		);
	}

	/**
	 * @testdox term slug resolution treats a product attribute whose name is not a string as not matching, without fataling.
	 */
	public function test_term_slug_resolution_survives_non_string_attribute_name(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array(
						'id'         => 1,
						'attributes' => array(
							array(
								'name'  => array( 'colour' ),
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
								'id'         => 2,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'Red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					2 => array(
						'id'     => 2,
						'parent' => 1,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 1,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'Red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		// No product attribute matches, so the slug falls back to the raw
		// label 'Red', which still equals the selection's value.
		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( 2, $resolved_variation['id'] );
	}

	/**
	 * @testdox term slug resolution treats a matching product attribute's terms field as empty when it is not an array, without fataling.
	 */
	public function test_term_slug_resolution_survives_non_array_terms(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array(
						'id'         => 1,
						'attributes' => array(
							array(
								'name'  => 'colour',
								'terms' => 'not-an-array',
							),
						),
						'variations' => array(
							array(
								'id'         => 2,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'Red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					2 => array(
						'id'     => 2,
						'parent' => 1,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 1,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'Red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( 2, $resolved_variation['id'] );
	}

	/**
	 * @testdox term slug resolution falls back to the raw label when the matching term's slug is not a string, instead of fataling on its string return type.
	 */
	public function test_term_slug_resolution_survives_non_string_slug(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array(
						'id'         => 1,
						'attributes' => array(
							array(
								'name'  => 'colour',
								'terms' => array(
									array(
										'name' => 'Red',
										'slug' => array( 'red' ),
									),
								),
							),
						),
						'variations' => array(
							array(
								'id'         => 2,
								'attributes' => array(
									array(
										'name'  => 'colour',
										'value' => 'Red',
									),
								),
							),
						),
					),
				),
				'productVariations' => array(
					2 => array(
						'id'     => 2,
						'parent' => 1,
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 1,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'Red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		$resolved_variation = $envelope['productVariation']();
		$this->assertIsArray( $resolved_variation );
		$this->assertSame( 2, $resolved_variation['id'], 'A non-string slug falls back to the raw label, which still equals the selection.' );
	}

	/**
	 * @testdox a cart line that is not itself an array is skipped, and a well-formed line later in the list still matches.
	 * @dataProvider provider_object_shape
	 * @param mixed $malformed The one shape that can fail this read without the guard.
	 */
	public function test_cart_item_survives_non_array_line( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'cart' => array(
					'items' => array(
						$malformed,
						array(
							'key'  => 'line-1',
							'id'   => 5,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 5 ) );

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-1', $cart_item['key'] );
	}

	/**
	 * @testdox cartItem-by-key matching survives a cart line that is not itself an array, still finding a well-formed line later in the list.
	 */
	public function test_cart_item_by_key_survives_non_array_line(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'cart' => array(
					'items' => array(
						(object) array( 'k' => 'v' ),
						array(
							'key' => 'line-2',
							'id'  => 5,
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'cartItemKey' => 'line-2' ) );

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item );
		$this->assertSame( 'line-2', $cart_item['key'] );
	}

	/**
	 * @testdox a variation-typed cart line whose variation field is not an array is skipped instead of fataling on count().
	 * @dataProvider provider_non_array_shapes
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_cart_item_survives_non_array_line_variation( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'cart' => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 2,
							'type'      => 'variation',
							'variation' => $malformed,
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 2,
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
	 * @testdox a cart line whose id is an object does not fatal on the int cast, and the line is skipped as not matching.
	 */
	public function test_cart_item_survives_object_line_id(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'cart' => array(
					'items' => array(
						array(
							'key'  => 'line-1',
							'id'   => (object) array( 'k' => 'v' ),
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 5 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox cartItem falls back to pairing on the scope's productId, without fataling, when the resolved product's own id is not usable as an array key.
	 */
	public function test_cart_item_survives_resolved_product_id_not_usable_as_key(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products' => array(
					5 => array( 'id' => (object) array( 'k' => 'v' ) ),
				),
				'cart'     => array(
					'items' => array(
						array(
							'key'  => 'line-1',
							'id'   => 5,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 5 ) );

		$envelope  = $this->get_product_scope();
		$cart_item = $envelope['cartItem']();

		$this->assertIsArray( $cart_item, 'An id that is not usable as an array key falls back to the scope productId, the same as a missing id.' );
		$this->assertSame( 'line-1', $cart_item['key'] );
	}

	/**
	 * @testdox variation cart-item matching survives a non-array state.productVariations or state.products, falling back to comparing the raw selected label.
	 * @dataProvider provider_object_shape
	 * @param mixed $malformed The one shape that can fail this read without the guard.
	 */
	public function test_cart_item_variation_matching_survives_non_array_lookup_maps( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'productVariations' => $malformed,
				'products'          => $malformed,
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 2,
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
				'productId' => 2,
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

		$this->assertIsArray( $cart_item, 'Should still match by falling back to the raw label when the parent lookup maps are unreadable.' );
		$this->assertSame( 'line-1', $cart_item['key'] );
	}

	/**
	 * @testdox cart-item variation matching survives a parent product whose attributes field is not an array, falling back to the raw label.
	 * @dataProvider provider_non_array_shapes
	 * @param mixed $malformed A shape that is not an array.
	 */
	public function test_cart_item_variation_matching_survives_non_array_parent_attributes( $malformed ): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'products'          => array(
					1 => array(
						'id'         => 1,
						'attributes' => $malformed,
					),
				),
				'productVariations' => array(
					2 => array(
						'id'     => 2,
						'parent' => 1,
					),
				),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 2,
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
				'productId' => 2,
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
	 * @testdox a cart line whose variation entry is not an array is treated as carrying no attribute or value, so the line does not match.
	 */
	public function test_cart_item_variation_matching_survives_non_array_entry(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'cart' => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 2,
							'type'      => 'variation',
							'variation' => array( (object) array( 'k' => 'v' ) ),
						),
					),
				),
			)
		);

		$this->push_woocommerce_context(
			array(
				'productId' => 2,
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
	 * @testdox a malformed entry in the declared variation selection does not fatal while matching a cart line's attributes.
	 */
	public function test_cart_item_variation_matching_survives_non_array_selection_entry(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'cart' => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => 2,
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
				'productId' => 2,
				'variation' => array( (object) array( 'k' => 'v' ) ),
			)
		);

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox cart-item matching skips a line whose id is not usable as an array key, instead of matching it against a scope with no product.
	 */
	public function test_cart_item_variation_matching_survives_line_id_not_usable_as_key(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'productVariations' => array(
					2 => array(
						'id'     => 2,
						'parent' => 1,
					),
				),
				'cart'              => array(
					'items' => array(
						array(
							'key'       => 'line-1',
							'id'        => array( 'not-usable-as-key' ),
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
				'productId' => 1,
				'variation' => array(
					array(
						'attribute' => 'colour',
						'value'     => 'Red',
					),
				),
			)
		);

		$envelope = $this->get_product_scope();

		// productId 1 resolves the same as the malformed line id's own int
		// cast ((int) array( 'not-usable-as-key' ) is 1), so this line
		// would otherwise pass every other check and match.
		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * @testdox a cart line id of 5.5 does not match productId 5 after an int cast, since a non-integral float is not usable as an array key.
	 */
	public function test_cart_item_skips_line_with_non_integer_float_id(): void {
		wp_interactivity_state(
			$this->data_namespace,
			array(
				'cart' => array(
					'items' => array(
						array(
							'key'  => 'line-1',
							'id'   => 5.5,
							'type' => 'simple',
						),
					),
				),
			)
		);

		$this->push_woocommerce_context( array( 'productId' => 5 ) );

		$envelope = $this->get_product_scope();

		$this->assertNull( $envelope['cartItem']() );
	}

	/**
	 * Shapes that fail is_array(): a string, a bool, an int and an object,
	 * chosen so each fails PHP's array checks or casts differently.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provider_non_array_shapes(): array {
		return array(
			'string' => array( 'not-an-array' ),
			'bool'   => array( true ),
			'int'    => array( 42 ),
			'object' => array( (object) array( 'k' => 'v' ) ),
		);
	}

	/**
	 * Shapes that fail foreach() without the guard: a string, a bool and
	 * an int all raise a "foreach() argument must be of type array|object"
	 * warning. An object is excluded — foreach over an object legally
	 * iterates its public properties, so it cannot fail this read.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provider_non_iterable_shapes(): array {
		return array(
			'string' => array( 'not-an-array' ),
			'bool'   => array( true ),
			'int'    => array( 42 ),
		);
	}

	/**
	 * The one shape that can fail a read used only as `$x[ $key ] ?? $fallback`:
	 * a string, a bool or an int base all resolve the fallback under `??`
	 * with no diagnostic, but an object base raises a PHP Error even under
	 * `??`.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provider_object_shape(): array {
		return array(
			'object' => array( (object) array( 'k' => 'v' ) ),
		);
	}

	/**
	 * The shapes that can fail products/productVariations without the
	 * guard: an object base raises a PHP Error even under `??`, and a
	 * string base indexed by a small int id reads a character instead of
	 * falling back, which a downstream is_array() check must still catch.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provider_object_and_string_shapes(): array {
		return array(
			'string' => array( 'not-an-array' ),
			'object' => array( (object) array( 'k' => 'v' ) ),
		);
	}

	/**
	 * Shapes that fail is_usable_as_array_key(): using any of them as an
	 * array offset raises a PHP Error, except the float, whose only
	 * legal offset use is a deprecated implicit conversion.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provider_shapes_not_usable_as_key(): array {
		return array(
			'array'  => array( array( 'x' ) ),
			'object' => array( (object) array( 'k' => 'v' ) ),
			'float'  => array( 1.5 ),
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

		$state = wp_interactivity_state( $this->data_namespace );

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
		$property->setValue( $api, array( array( $this->data_namespace => $context ) ) );
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
