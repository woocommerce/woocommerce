<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypes\SavedForLater;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use WP_UnitTestCase;

/**
 * Tests for the SavedForLater block type.
 */
class SavedForLaterTests extends WP_UnitTestCase {

	/**
	 * System under test.
	 *
	 * Constructed via reflection so `AbstractBlock::__construct` doesn't run
	 * `parent::initialize()` and re-register the block (the test bootstrap
	 * has already registered it). The filter callbacks under test only read
	 * `$this->namespace` and `$this->block_name`, both class defaults.
	 *
	 * @var SavedForLater
	 */
	private SavedForLater $sut;

	/**
	 * Features controller, used to toggle the `cart_save_for_later` feature.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * The `cart_save_for_later` feature state before the test, restored on teardown.
	 *
	 * @var bool
	 */
	private bool $original_feature_enabled;

	/**
	 * Instantiate the block without invoking its constructor and inject a
	 * registry mock so render() can call `->add()` without NPEing.
	 */
	public function setUp(): void {
		parent::setUp();

		$reflection = new ReflectionClass( SavedForLater::class );
		$this->sut  = $reflection->newInstanceWithoutConstructor();

		$registry_prop = new ReflectionProperty( SavedForLater::class, 'asset_data_registry' );
		$registry_prop->setAccessible( true );
		$registry_prop->setValue(
			$this->sut,
			new AssetDataRegistryMock( Package::container()->get( Api::class ) )
		);

		// The block gates its rendering and auto-injection on the feature, so
		// enable it by default; the feature-off tests toggle it off explicitly.
		$this->features_controller      = wc_get_container()->get( FeaturesController::class );
		$this->original_feature_enabled = $this->features_controller->feature_is_enabled( 'cart_save_for_later' );
		$this->features_controller->change_feature_enable( 'cart_save_for_later', true );
	}

	/**
	 * Restore the feature flag to its pre-test state.
	 */
	public function tearDown(): void {
		$this->features_controller->change_feature_enable( 'cart_save_for_later', $this->original_feature_enabled );
		parent::tearDown();
	}

	/**
	 * Auto-injection after `woocommerce/cart` is declared in block.json via
	 * `blockHooks`. Registering it on the block type (rather than through the
	 * `hooked_block_types` filter alone) is what makes the editor treat it as a
	 * first-class hooked block and materialize it, instead of recording it as
	 * ignored on save. This guards that declaration.
	 */
	public function test_block_json_declares_block_hooks_after_cart(): void {
		$block_json = WC_ABSPATH . 'assets/client/blocks/saved-for-later/block.json';
		$this->assertFileExists( $block_json, 'Built saved-for-later block.json should exist.' );

		$metadata = json_decode( (string) file_get_contents( $block_json ), true );
		$this->assertIsArray( $metadata );
		$this->assertArrayHasKey( 'blockHooks', $metadata );
		$this->assertArrayHasKey( 'woocommerce/cart', $metadata['blockHooks'] );
		$this->assertSame( 'after', $metadata['blockHooks']['woocommerce/cart'] );
	}

	/**
	 * The block type is registered unconditionally, but when the feature is off
	 * `disable_when_feature_off` strips the `block_hooks` setting and hides the
	 * block from the inserter, so it neither auto-injects nor can be added
	 * manually while off.
	 */
	public function test_disable_when_feature_off_strips_hooks_and_hides_inserter(): void {
		$this->features_controller->change_feature_enable( 'cart_save_for_later', false );

		$settings = array(
			'block_hooks' => array( 'woocommerce/cart' => 'after' ),
			'supports'    => array( 'interactivity' => true ),
		);
		$metadata = array( 'name' => 'woocommerce/saved-for-later' );

		$result = $this->sut->disable_when_feature_off( $settings, $metadata );

		$this->assertArrayNotHasKey( 'block_hooks', $result );
		$this->assertFalse( $result['supports']['inserter'] );
	}

	/**
	 * When the feature is enabled the settings are left untouched, so the block
	 * keeps its `block_hooks` setting and stays inserter-visible.
	 */
	public function test_disable_when_feature_off_keeps_settings_when_enabled(): void {
		$this->features_controller->change_feature_enable( 'cart_save_for_later', true );

		$settings = array(
			'block_hooks' => array( 'woocommerce/cart' => 'after' ),
			'supports'    => array( 'interactivity' => true ),
		);
		$metadata = array( 'name' => 'woocommerce/saved-for-later' );

		$result = $this->sut->disable_when_feature_off( $settings, $metadata );

		$this->assertArrayHasKey( 'block_hooks', $result );
		$this->assertSame( 'after', $result['block_hooks']['woocommerce/cart'] );
		$this->assertArrayNotHasKey( 'inserter', $result['supports'] );
	}

	/**
	 * `disable_when_feature_off` only touches the Saved for Later block; other
	 * blocks' settings pass through untouched even when the feature is off.
	 */
	public function test_disable_when_feature_off_ignores_other_blocks(): void {
		$this->features_controller->change_feature_enable( 'cart_save_for_later', false );

		$settings = array( 'block_hooks' => array( 'woocommerce/cart' => 'after' ) );
		$metadata = array( 'name' => 'core/paragraph' );

		$this->assertSame( $settings, $this->sut->disable_when_feature_off( $settings, $metadata ) );
	}

	/**
	 * `render()` outputs nothing when the feature is disabled, so a block
	 * persisted while the feature was on renders empty instead of as content.
	 */
	public function test_render_returns_empty_when_feature_disabled(): void {
		$this->features_controller->change_feature_enable( 'cart_save_for_later', false );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$render = new ReflectionMethod( SavedForLater::class, 'render' );
		$render->setAccessible( true );

		$this->assertSame( '', (string) $render->invoke( $this->sut, array(), '', null ) );
	}

	/**
	 * The auto-injected block ships with a seeded `core/heading` inner block so
	 * fresh cart pages render the heading on the frontend out of the box. The
	 * matching `null` push onto `innerContent` is what makes `WP_Block::render()`
	 * walk into the heading when building `$content`.
	 */
	public function test_hooked_block_attributes_seed_heading_inner_block(): void {
		$parsed_hooked_block = array(
			'blockName' => 'woocommerce/saved-for-later',
			'attrs'     => array(),
		);
		$parsed_anchor_block = array( 'blockName' => 'woocommerce/cart' );

		$result = $this->sut->set_hooked_block_attributes(
			$parsed_hooked_block,
			'woocommerce/saved-for-later',
			'after',
			$parsed_anchor_block
		);

		$this->assertArrayHasKey( 'innerBlocks', $result );
		$this->assertCount( 1, $result['innerBlocks'] );

		$heading = $result['innerBlocks'][0];
		$this->assertSame( 'core/heading', $heading['blockName'] );
		$this->assertSame( 2, $heading['attrs']['level'] );
		$this->assertArrayHasKey( 'content', $heading['attrs'] );
		// `attrs.content` is the raw translated string (no `esc_html`) —
		// JSON encoding handles escaping at serialization time. Asserts
		// the en_US source string the test bootstrap runs under.
		$this->assertSame( 'Saved for later', $heading['attrs']['content'] );
		$this->assertStringContainsString( '<h2 class="wp-block-heading">', $heading['innerHTML'] );
		$this->assertSame( array( $heading['innerHTML'] ), $heading['innerContent'] );

		$this->assertArrayHasKey( 'innerContent', $result );
		$this->assertContains( null, $result['innerContent'] );
	}

	/**
	 * Extensions are free to hook `hooked_block_woocommerce/saved-for-later`
	 * to add their own inner blocks at a different priority. Our heading must
	 * still be seeded alongside, not in place of, what they added.
	 */
	public function test_hooked_block_attributes_appends_heading_alongside_existing_inner_blocks(): void {
		$existing_block      = array(
			'blockName'    => 'core/paragraph',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => '<p>From another extension</p>',
			'innerContent' => array( '<p>From another extension</p>' ),
		);
		$parsed_hooked_block = array(
			'blockName'    => 'woocommerce/saved-for-later',
			'attrs'        => array(),
			'innerBlocks'  => array( $existing_block ),
			'innerContent' => array( null ),
		);
		$parsed_anchor_block = array( 'blockName' => 'woocommerce/cart' );

		$result = $this->sut->set_hooked_block_attributes(
			$parsed_hooked_block,
			'woocommerce/saved-for-later',
			'after',
			$parsed_anchor_block
		);

		$this->assertCount( 2, $result['innerBlocks'] );
		// The other-extension block is preserved at its original index.
		$this->assertSame( $existing_block, $result['innerBlocks'][0] );
		// Our heading is appended after it.
		$this->assertSame( 'core/heading', $result['innerBlocks'][1]['blockName'] );
		$this->assertSame( 'Saved for later', $result['innerBlocks'][1]['attrs']['content'] );
		// Parent innerContent gains a `null` placeholder for each inner block,
		// so `WP_Block::render()` walks into both when building `$content`.
		$this->assertCount( 2, $result['innerContent'] );
	}

	/**
	 * `render()` returns an empty string for logged-out shoppers.
	 */
	public function test_render_returns_empty_for_logged_out_user(): void {
		wp_set_current_user( 0 );

		$render = new ReflectionMethod( SavedForLater::class, 'render' );
		$render->setAccessible( true );

		$this->assertSame( '', (string) $render->invoke( $this->sut, array(), '', null ) );
	}

	/**
	 * For a logged-in shopper whose list is empty (the new-shopper /
	 * never-saved-an-item case), SSR must:
	 *   - emit the empty-state `<li>` already `hidden`, so the message
	 *     never flashes between paint and iAPI hydration, and
	 *   - seed the wrapper's iAPI context with `hasShownItems: false` and
	 *     the matching `data-wp-watch` callback, so the JS-side
	 *     `state.isEmpty` getter has the inputs it needs to keep the
	 *     message hidden until the shopper has actually saved an item.
	 *
	 * With no saved items, `prefetch_items()` returns `[]` whether
	 * the Store API route is registered or not (a 404 still resolves to
	 * an empty array), so this stays a unit-level assertion without
	 * feature-flag wiring or fixture items. Sets
	 * `WP_Block_Supports::$block_to_render` up front so
	 * `get_block_wrapper_attributes()` (which reads it for layout/style
	 * supports) has the context it expects when called outside the
	 * usual block-render pipeline.
	 */
	public function test_render_seeds_hidden_empty_state_for_new_shopper(): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		$attributes = array();

		$previous_block_to_render            = \WP_Block_Supports::$block_to_render;
		\WP_Block_Supports::$block_to_render = array(
			'blockName' => 'woocommerce/saved-for-later',
			'attrs'     => $attributes,
		);

		try {
			$render = new ReflectionMethod( SavedForLater::class, 'render' );
			$render->setAccessible( true );

			$markup = (string) $render->invoke( $this->sut, $attributes, '', null );
		} finally {
			\WP_Block_Supports::$block_to_render = $previous_block_to_render;
		}

		// The empty-state `<li>` is always rendered, always initially hidden.
		$this->assertMatchesRegularExpression(
			'/<li[^>]*class="wc-block-saved-for-later__empty"[^>]*\bhidden\b/',
			$markup,
			'Empty-state <li> must be initially hidden so the message does not flash before iAPI hydration.'
		);

		// The wrapper's `data-wp-context` JSON is HTML-escaped into an
		// attribute, so the embedded quotes appear as `&quot;` in the
		// rendered markup.
		$this->assertStringContainsString(
			'&quot;hasShownItems&quot;:false',
			$markup,
			'Wrapper context must seed hasShownItems=false for an empty list so the empty message stays hidden until the shopper actually saves an item.'
		);

		$this->assertStringContainsString(
			'data-wp-watch="callbacks.trackShownItems"',
			$markup,
			'Wrapper must wire the trackShownItems watcher so hasShownItems can flip to true the first time items appear in-session.'
		);
	}

	/**
	 * The seeded heading (and any future sibling inner blocks rendered via
	 * `$content`) must share the empty-state visibility gating: hidden on
	 * first paint for new shoppers / empty refreshes, revealed once the
	 * iAPI watcher flips `context.hasShownItems`. Without this, a saved
	 * cart page rendered with no items would show an orphaned heading
	 * sitting above nothing.
	 */
	public function test_render_wraps_header_with_hidden_visibility_gate_when_empty(): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		$attributes = array();

		$previous_block_to_render            = \WP_Block_Supports::$block_to_render;
		\WP_Block_Supports::$block_to_render = array(
			'blockName' => 'woocommerce/saved-for-later',
			'attrs'     => $attributes,
		);

		try {
			$render = new ReflectionMethod( SavedForLater::class, 'render' );
			$render->setAccessible( true );

			$content = '<h2 class="wp-block-heading">Saved for later</h2>';
			$markup  = (string) $render->invoke( $this->sut, $attributes, $content, null );
		} finally {
			\WP_Block_Supports::$block_to_render = $previous_block_to_render;
		}

		// The header wrapper exists, contains the heading, has the iAPI
		// visibility bind, and is initially hidden because items is empty.
		$this->assertMatchesRegularExpression(
			'/<div[^>]*class="wc-block-saved-for-later__header"[^>]*data-wp-bind--hidden="!context\.hasShownItems"[^>]*\bhidden\b[^>]*>.*Saved for later/s',
			$markup,
			'Header wrapper must be initially hidden for an empty list so a fresh-load empty SC does not show an orphaned heading.'
		);
	}

	/**
	 * Invoke render() against the SUT (which already has a registry injected
	 * in setUp). Render is wrapped in a try/catch because its downstream calls
	 * (REST prefetch, interactivity bootstrap) need bits of the request
	 * lifecycle that aren't set up in unit tests; the flag-setting branch
	 * runs before any of that, so a later fatal doesn't change what we
	 * assert on.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return AssetDataRegistryMock The injected registry, ready to inspect.
	 */
	private function invoke_render_with_registry_mock( array $attributes ): AssetDataRegistryMock {
		$render = new ReflectionMethod( SavedForLater::class, 'render' );
		$render->setAccessible( true );

		try {
			$render->invoke( $this->sut, $attributes, '', null );
		} catch ( \Throwable $e ) {
			// Ignored: flag-setting runs before the parts of render() that need a full request lifecycle.
			unset( $e );
		}

		$registry_prop = new ReflectionProperty( SavedForLater::class, 'asset_data_registry' );
		$registry_prop->setAccessible( true );

		return $registry_prop->getValue( $this->sut );
	}

	/**
	 * @return array<string, array{bool, bool, bool}>
	 */
	public function provider_cart_page_has_saved_for_later_flag(): array {
		return array(
			// label                    => array( logged_in, is_cart, expected ).
			'set on cart, logged-in'    => array( true, true, true ),
			'not set off the cart page' => array( true, false, false ),
			'not set for guests'        => array( false, true, false ),
		);
	}

	/**
	 * `cartPageHasSavedForLater` is the wcSettings flag the cart line item row reads
	 * to decide whether to render the "Save for later" link. The block sets it
	 * only when rendering the saved-for-later list, on the cart page, for a
	 * logged-in shopper — every other combination must leave it unset.
	 *
	 * @dataProvider provider_cart_page_has_saved_for_later_flag
	 *
	 * @param bool $logged_in Whether the test runs as a logged-in customer.
	 * @param bool $is_cart   Whether `is_cart()` is forced to return true.
	 * @param bool $expected  Whether the flag is expected to be registered.
	 */
	public function test_cart_page_has_saved_for_later_flag(
		bool $logged_in,
		bool $is_cart,
		bool $expected
	): void {
		wp_set_current_user( $logged_in ? self::factory()->user->create( array( 'role' => 'customer' ) ) : 0 );

		// Mock the is_cart() call routed through LegacyProxy in render(). Filter/cache
		// approaches don't work in CI: upstream tests can define `WOOCOMMERCE_CART` via
		// `wc_maybe_define_constant`, which makes is_cart() short-circuit to true
		// irreversibly for the rest of the process.
		$legacy_proxy = wc_get_container()->get( LegacyProxy::class );
		$legacy_proxy->reset();
		$legacy_proxy->register_function_mocks(
			array(
				'is_cart' => function () use ( $is_cart ) {
					return $is_cart;
				},
			)
		);

		// This class extends WP_UnitTestCase rather than WC_Unit_Test_Case, so the
		// proxy isn't reset automatically between tests — clean up explicitly to
		// avoid leaking the is_cart() mock into later tests in the same process.
		try {
			$registry = $this->invoke_render_with_registry_mock( array() );

			$this->assertSame(
				$expected,
				array_key_exists( 'cartPageHasSavedForLater', $registry->get() )
			);
		} finally {
			$legacy_proxy->reset();
		}
	}
}
