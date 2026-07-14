<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\SharedStores\CartStore as TestedCartStore;
use WC_Helper_Product;

/**
 * Tests for the CartStore shared store's scope service.
 */
class CartStoreTest extends \WC_Unit_Test_Case {

	/**
	 * Consent string required by the CartStore API.
	 *
	 * @var string
	 */
	protected $consent = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * The Interactivity API store namespace under test.
	 *
	 * @var string
	 */
	protected $store_namespace = 'woocommerce/cart';

	/**
	 * Reset static state on the CartStore and the global
	 * WP_Interactivity_API instance between tests so state does not bleed.
	 */
	public function tearDown(): void {
		$this->reset_cart_store_static_state();
		$this->reset_interactivity_state();
		parent::tearDown();
	}

	/**
	 * @testdox mint_page_scope() rejects calls without the consent string.
	 */
	public function test_mint_page_scope_throws_without_consent(): void {
		$this->expectException( \InvalidArgumentException::class );

		TestedCartStore::mint_page_scope( 'nope' );
	}

	/**
	 * @testdox push_scope() rejects calls without the consent string.
	 */
	public function test_push_scope_throws_without_consent(): void {
		$this->expectException( \InvalidArgumentException::class );

		TestedCartStore::push_scope( 'nope', 'collection/q1/123' );
	}

	/**
	 * @testdox pop_scope() rejects calls without the consent string.
	 */
	public function test_pop_scope_throws_without_consent(): void {
		$this->expectException( \InvalidArgumentException::class );

		TestedCartStore::pop_scope( 'nope' );
	}

	/**
	 * @testdox get_current_scope() rejects calls without the consent string.
	 */
	public function test_get_current_scope_throws_without_consent(): void {
		$this->expectException( \InvalidArgumentException::class );

		TestedCartStore::get_current_scope( 'nope' );
	}

	/**
	 * @testdox get_current_scope() returns the seeded page scope at the top level, reproducibly.
	 */
	public function test_get_current_scope_returns_stable_page_scope(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		$first  = TestedCartStore::get_current_scope( $this->consent );
		$second = TestedCartStore::get_current_scope( $this->consent );

		$this->assertSame( 'page/' . $product->get_id(), $first );
		$this->assertSame( $first, $second, 'Repeated calls within the same render should return the same page scope.' );

		$product->delete( true );
	}

	/**
	 * @testdox get_current_scope() reproduces the same page scope across repeated renders of the same page.
	 */
	public function test_page_scope_reproduces_across_renders_of_same_page(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		$first_render = TestedCartStore::get_current_scope( $this->consent );

		// Simulate a fresh render of the same page (e.g. a router-region
		// re-render), which is a new PHP request in production.
		$this->reset_cart_store_static_state();
		$this->go_to( get_permalink( $product->get_id() ) );

		$second_render = TestedCartStore::get_current_scope( $this->consent );

		$this->assertSame( $first_render, $second_render );

		$product->delete( true );
	}

	/**
	 * @testdox get_current_scope() yields different page scopes for different queried pages.
	 */
	public function test_different_queried_pages_yield_different_page_scopes(): void {
		$product_a = WC_Helper_Product::create_simple_product();
		$product_b = WC_Helper_Product::create_simple_product();

		$this->go_to( get_permalink( $product_a->get_id() ) );
		$scope_a = TestedCartStore::get_current_scope( $this->consent );

		$this->reset_cart_store_static_state();

		$this->go_to( get_permalink( $product_b->get_id() ) );
		$scope_b = TestedCartStore::get_current_scope( $this->consent );

		$this->assertNotSame( $scope_a, $scope_b );

		$product_a->delete( true );
		$product_b->delete( true );
	}

	/**
	 * @testdox push_scope()/pop_scope() make get_current_scope() return the innermost pushed scope, and restore the previous scope after popping.
	 */
	public function test_push_and_pop_scope_stack(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		$page_scope = TestedCartStore::get_current_scope( $this->consent );

		TestedCartStore::push_scope( $this->consent, 'collection/q1/123' );
		$this->assertSame( 'collection/q1/123', TestedCartStore::get_current_scope( $this->consent ) );

		TestedCartStore::push_scope( $this->consent, 'collection/q1/456' );
		$this->assertSame(
			'collection/q1/456',
			TestedCartStore::get_current_scope( $this->consent ),
			'The innermost pushed scope should be in effect.'
		);

		TestedCartStore::pop_scope( $this->consent );
		$this->assertSame(
			'collection/q1/123',
			TestedCartStore::get_current_scope( $this->consent ),
			'Popping should restore the previous container scope.'
		);

		TestedCartStore::pop_scope( $this->consent );
		$this->assertSame(
			$page_scope,
			TestedCartStore::get_current_scope( $this->consent ),
			'Popping the last container scope should restore the page scope.'
		);

		$product->delete( true );
	}

	/**
	 * @testdox pop_scope() beyond the bottom of the stack leaves the page scope in effect.
	 */
	public function test_pop_scope_beyond_bottom_is_a_no_op(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		$page_scope = TestedCartStore::get_current_scope( $this->consent );

		TestedCartStore::pop_scope( $this->consent );
		TestedCartStore::pop_scope( $this->consent );

		$this->assertSame( $page_scope, TestedCartStore::get_current_scope( $this->consent ) );

		$product->delete( true );
	}

	/**
	 * @testdox The page scope is seeded into the client-visible woocommerce/cart state.
	 */
	public function test_page_scope_is_seeded_into_state(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		$scope = TestedCartStore::get_current_scope( $this->consent );

		$state = wp_interactivity_state( $this->store_namespace );

		$this->assertArrayHasKey( 'pageScope', $state );
		$this->assertSame( $scope, $state['pageScope'] );

		$product->delete( true );
	}

	/**
	 * @testdox mint_page_scope() seeds state directly, without requiring a container push.
	 */
	public function test_mint_page_scope_seeds_state_directly(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		$minted = TestedCartStore::mint_page_scope( $this->consent );

		$state = wp_interactivity_state( $this->store_namespace );

		$this->assertSame( 'page/' . $product->get_id(), $minted );
		$this->assertSame( $minted, $state['pageScope'] );

		$product->delete( true );
	}

	/**
	 * Reset the CartStore's private static properties between tests.
	 */
	private function reset_cart_store_static_state(): void {
		$reflection = new \ReflectionClass( TestedCartStore::class );

		$page_scope = $reflection->getProperty( 'page_scope' );
		$page_scope->setAccessible( true );
		$page_scope->setValue( null, null );

		$scope_stack = $reflection->getProperty( 'scope_stack' );
		$scope_stack->setAccessible( true );
		$scope_stack->setValue( null, array() );
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
