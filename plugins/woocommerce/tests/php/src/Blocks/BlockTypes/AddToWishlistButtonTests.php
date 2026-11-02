<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToWishlistButton;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use WP_Block_Supports;
use WP_UnitTestCase;

/**
 * Tests for the AddToWishlistButton block type.
 */
class AddToWishlistButtonTests extends WP_UnitTestCase {

	/**
	 * System under test, instantiated without invoking the constructor so the
	 * test bootstrap's existing block registration isn't re-applied. The
	 * methods under test only read `$this->namespace` / `$this->block_name`
	 * (class defaults) plus an injected `asset_data_registry`.
	 *
	 * @var AddToWishlistButton
	 */
	private AddToWishlistButton $sut;

	/**
	 * Cached for restoration in tearDown — render() temporarily sets it so
	 * `get_block_wrapper_attributes()` can resolve layout/style supports.
	 *
	 * @var array|null
	 */
	private ?array $previous_block_to_render = null;

	/**
	 * Instantiate the block without invoking its constructor and inject the
	 * registry mock so render() can call its registered helpers without
	 * NPEing.
	 */
	public function setUp(): void {
		parent::setUp();

		$reflection = new ReflectionClass( AddToWishlistButton::class );
		$this->sut  = $reflection->newInstanceWithoutConstructor();

		$registry_prop = new ReflectionProperty( AddToWishlistButton::class, 'asset_data_registry' );
		$registry_prop->setAccessible( true );
		$registry_prop->setValue(
			$this->sut,
			new AssetDataRegistryMock( Package::container()->get( Api::class ) )
		);
	}

	/**
	 * Reset any global state we mutated for `get_block_wrapper_attributes()`.
	 */
	public function tearDown(): void {
		if ( null !== $this->previous_block_to_render ) {
			WP_Block_Supports::$block_to_render = $this->previous_block_to_render;
			$this->previous_block_to_render     = null;
		}
		parent::tearDown();
	}

	/**
	 * Build a minimal $block stub carrying a `context` array — enough for
	 * `render()` to read `$block->context['postId']`. A `stdClass` is
	 * sufficient because the renderer only ever reads via property access.
	 *
	 * @param int|null $post_id Post ID to seed (or null for the "no context" case).
	 * @return stdClass
	 */
	private function build_block_stub( ?int $post_id ): stdClass {
		$block          = new stdClass();
		$block->context = null === $post_id ? array() : array( 'postId' => $post_id );
		return $block;
	}

	/**
	 * Call the protected `render()` method via reflection. Sets
	 * `WP_Block_Supports::$block_to_render` so `get_block_wrapper_attributes()`
	 * inside render() has the context it expects when invoked outside the
	 * normal block-render pipeline.
	 *
	 * @param stdClass|null $block Block stub.
	 * @return string Rendered markup.
	 */
	private function invoke_render( ?stdClass $block ): string {
		$attributes = array();

		$this->previous_block_to_render     = WP_Block_Supports::$block_to_render;
		WP_Block_Supports::$block_to_render = array(
			'blockName' => 'woocommerce/add-to-wishlist-button',
			'attrs'     => $attributes,
		);

		$render = new ReflectionMethod( AddToWishlistButton::class, 'render' );
		$render->setAccessible( true );

		return (string) $render->invoke( $this->sut, $attributes, '', $block );
	}

	/**
	 * `render()` returns an empty string for logged-out shoppers, before any
	 * product/context lookups happen.
	 */
	public function test_render_returns_empty_for_logged_out_user(): void {
		wp_set_current_user( 0 );

		$this->assertSame(
			'',
			$this->invoke_render( $this->build_block_stub( 12345 ) ),
			'Guests must never see the wishlist trigger.'
		);
	}

	/**
	 * `render()` returns an empty string when `$block->context` has no
	 * `postId` — the button has no product to act on.
	 */
	public function test_render_returns_empty_when_no_post_id_in_context(): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		$this->assertSame( '', $this->invoke_render( $this->build_block_stub( null ) ) );
	}

	/**
	 * `render()` returns an empty string when the supplied `postId` doesn't
	 * resolve to a `WC_Product` (e.g. the post was deleted, or it's a non-
	 * product post type).
	 */
	public function test_render_returns_empty_when_product_cannot_be_loaded(): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		// 999999 is intentionally a non-existent post ID.
		$this->assertSame( '', $this->invoke_render( $this->build_block_stub( 999999 ) ) );
	}

	/**
	 * For a logged-in shopper on a simple product not in the wishlist, the
	 * rendered markup carries the iAPI wrapper, the per-block context (with
	 * `isVariableType` false and `isPending` false), and the empty-star
	 * initial state with `aria-pressed="false"`.
	 */
	public function test_render_simple_product_not_in_wishlist(): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$markup = $this->invoke_render( $this->build_block_stub( $product_id ) );

		$this->assertStringContainsString(
			'data-wp-interactive="woocommerce/add-to-wishlist-button"',
			$markup,
			'Wrapper must declare its iAPI interactive scope.'
		);

		// `data-wp-context` is HTML-encoded; the embedded quotes appear as `&quot;`.
		$this->assertStringContainsString( '&quot;productId&quot;:' . $product_id, $markup );
		$this->assertStringContainsString( '&quot;isVariableType&quot;:false', $markup );
		$this->assertStringContainsString( '&quot;isPending&quot;:false', $markup );

		$this->assertStringContainsString(
			'aria-pressed="false"',
			$markup,
			'Simple product not in the wishlist starts with aria-pressed=false.'
		);
		$this->assertStringNotContainsString(
			' disabled',
			$markup,
			'Simple products are always actionable — never disabled on first paint.'
		);
	}

	/**
	 * For a variable product, the button must be `disabled` on first paint
	 * and show the "Select options first" label, since no variation has been
	 * selected yet.
	 */
	public function test_render_variable_product_is_disabled_with_select_options_label(): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		$product    = \WC_Helper_Product::create_variation_product();
		$product_id = $product->get_id();

		$markup = $this->invoke_render( $this->build_block_stub( $product_id ) );

		$this->assertStringContainsString( '&quot;isVariableType&quot;:true', $markup );

		$this->assertMatchesRegularExpression(
			'/<button[^>]*\bdisabled\b/',
			$markup,
			'Variable product without a selected variation must render the button disabled.'
		);

		$this->assertStringContainsString(
			'Select options first',
			$markup,
			'Initial label for variable products is "Select options first".'
		);
	}

	/**
	 * The hidden empty-star span and the (initially hidden) filled-star span
	 * are both present, so iAPI can toggle visibility without DOM swaps.
	 */
	public function test_render_emits_both_star_icons(): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$markup = $this->invoke_render( $this->build_block_stub( $product_id ) );

		$this->assertStringContainsString( 'wc-block-add-to-wishlist-button__icon--empty', $markup );
		$this->assertStringContainsString( 'wc-block-add-to-wishlist-button__icon--filled', $markup );

		// Both spans are bound to `state.isInWishlist` (one positive, one
		// negated) so iAPI can toggle without removing/re-adding nodes.
		$this->assertStringContainsString( 'data-wp-bind--hidden="state.isInWishlist"', $markup );
		$this->assertStringContainsString( 'data-wp-bind--hidden="!state.isInWishlist"', $markup );
	}

	/**
	 * `is_initial_in_wishlist()` is the small helper that decides whether the
	 * SSR-rendered button starts in the "saved" state. Unit-testable in
	 * isolation: pass a synthetic prefetched-items array plus a product and
	 * assert the boolean result without touching the REST layer.
	 *
	 * @return array<string, array{array<int, array<string, mixed>>, string, bool}>
	 */
	public function provider_is_initial_in_wishlist(): array {
		return array(
			'simple in list'                  => array(
				array( array( 'id' => 42 ) ),
				'simple',
				true,
			),
			'simple not in list'              => array(
				array( array( 'id' => 99 ) ),
				'simple',
				false,
			),
			'simple with empty list'          => array(
				array(),
				'simple',
				false,
			),
			'variable parent ignores entries' => array(
				array( array( 'id' => 42 ) ),
				'variable',
				false,
			),
		);
	}

	/**
	 * @dataProvider provider_is_initial_in_wishlist
	 *
	 * @param array<int, array<string, mixed>> $items    Schema-shape items from the prefetch.
	 * @param string                           $type     Product type to construct ("simple" or "variable").
	 * @param bool                             $expected Expected boolean result.
	 */
	public function test_is_initial_in_wishlist( array $items, string $type, bool $expected ): void {
		$product = 'variable' === $type
			? \WC_Helper_Product::create_variation_product()
			: \WC_Helper_Product::create_simple_product();

		// Re-key the synthetic items to use this product's real ID for the
		// "in list" cases, so the test data stays independent of the
		// auto-generated post IDs the factory hands out.
		$normalized = array();
		foreach ( $items as $item ) {
			if ( isset( $item['id'] ) && 42 === $item['id'] ) {
				$item['id'] = $product->get_id();
			}
			$normalized[] = $item;
		}

		$method = new ReflectionMethod( AddToWishlistButton::class, 'is_initial_in_wishlist' );
		$method->setAccessible( true );

		$this->assertSame( $expected, $method->invoke( $this->sut, $normalized, $product ) );
	}
}
