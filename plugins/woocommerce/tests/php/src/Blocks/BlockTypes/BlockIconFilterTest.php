<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use WC_Unit_Test_Case;

/**
 * Render-level tests for the block icon filter integration.
 */
class BlockIconFilterTest extends WC_Unit_Test_Case {
	private const REPLACEMENT_SVG = '<svg class="fixture-icon" viewBox="0 0 24 24"><path d="M2 2h20v20H2z" fill="currentColor"/></svg>';

	/**
	 * Captured icon filter calls.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $icon_filter_calls = array();

	/**
	 * Number of calls to the no-op icon filter.
	 *
	 * @var int
	 */
	private $no_op_filter_calls = 0;

	/**
	 * Original WooCommerce cart instance.
	 *
	 * @var \WC_Cart|null
	 */
	private $original_cart;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->reset_cart_checkout_page_cache();
		$this->original_cart = WC()->cart;
		add_filter( 'woocommerce_cart_session_initialize', array( $this, 'disable_cart_session_hooks' ) );
		WC()->cart = new \WC_Cart();
		remove_filter( 'woocommerce_cart_session_initialize', array( $this, 'disable_cart_session_hooks' ) );

		add_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_icon_filter_call' ), PHP_INT_MAX, 4 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_icon_filter_call' ), PHP_INT_MAX );
		remove_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_no_op_icon_filter_call' ), PHP_INT_MAX );
		remove_filter( 'woocommerce_cart_session_initialize', array( $this, 'disable_cart_session_hooks' ) );

		WC()->cart = $this->original_cart;

		parent::tearDown();
	}

	/**
	 * @testdox Cart Link filters its selected icon with caller context during metadata-backed rendering.
	 */
	public function test_cart_link_filters_selected_icon_during_render(): void {
		$output = $this->render_block(
			'woocommerce/cart-link',
			array(
				'cartIcon' => 'bag-alt',
				'content'  => 'Fixture cart',
			)
		);

		$this->assertCount( 1, $this->icon_filter_calls, 'Cart Link should filter exactly one icon.' );
		$this->assertSame(
			array(
				'icon_name'  => 'bag-alt',
				'block_name' => 'woocommerce/cart-link',
				'attributes' => array(
					'cartIcon' => 'bag-alt',
					'content'  => 'Fixture cart',
				),
			),
			$this->icon_filter_calls[0]
		);
		$this->assert_filtered_icon( $output, 'wc-block-mini-cart__icon' );
	}

	/**
	 * @testdox Mini-Cart filters its primary icon on the interactive render path.
	 *
	 * Full-suite cart tests define WOOCOMMERCE_CART irreversibly, so this path needs a fresh process.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_mini_cart_filters_primary_icon_on_interactive_render_path(): void {
		$this->log_in_fixture_user();
		$this->assertFalse( is_cart(), 'The interactive path must render away from the Cart page.' );
		$this->assertFalse( is_checkout(), 'The interactive path must render away from the Checkout page.' );

		$output = $this->render_block(
			'woocommerce/mini-cart',
			array(
				'miniCartIcon' => 'bag',
				'iconColor'    => array( 'color' => '#123456' ),
			)
		);

		$this->assert_icon_filter_call( 0, 'bag', 'woocommerce/mini-cart' );
		$this->assertCount( 1, $this->icon_filter_calls, 'Mini-Cart should filter only its primary icon.' );
		$this->assertSame( 'bag', $this->icon_filter_calls[0]['attributes']['miniCartIcon'] );
		$this->assertSame( array( 'color' => '#123456' ), $this->icon_filter_calls[0]['attributes']['iconColor'] );
		$this->assert_filtered_icon( $output, 'wc-block-mini-cart__icon' );
	}

	/**
	 * @testdox Mini-Cart filters its primary icon with migrated color context on the Cart fallback path.
	 */
	public function test_mini_cart_filters_primary_icon_on_cart_fallback_path(): void {
		$this->log_in_fixture_user();
		$cart_page_id = $this->create_page_for_option( 'woocommerce_cart_page_id', 'Fixture cart page' );
		$this->go_to( get_permalink( $cart_page_id ) );
		$this->assertTrue( is_cart(), 'The test must exercise the actual Cart-page fallback path.' );

		$output = $this->render_block(
			'woocommerce/mini-cart',
			array(
				'miniCartIcon'           => 'bag-alt',
				'iconColorValue'         => '#654321',
				'productCountVisibility' => 'never',
			)
		);

		$this->assert_icon_filter_call( 0, 'bag-alt', 'woocommerce/mini-cart' );
		$this->assertCount( 1, $this->icon_filter_calls, 'The fallback should filter exactly one icon.' );
		$this->assertSame( 'bag-alt', $this->icon_filter_calls[0]['attributes']['miniCartIcon'] );
		$this->assertSame( array( 'color' => '#654321' ), $this->icon_filter_calls[0]['attributes']['iconColor'] );
		$this->assertArrayNotHasKey( 'iconColorValue', $this->icon_filter_calls[0]['attributes'] );
		$this->assert_filtered_icon( $output, 'wc-block-mini-cart__icon' );
	}

	/**
	 * @testdox Customer Account filters a line icon with its custom class on the simple-link path.
	 */
	public function test_customer_account_filters_line_icon_on_simple_link_path(): void {
		$output = $this->render_block(
			'woocommerce/customer-account',
			array(
				'displayStyle'          => 'icon_and_text',
				'hasDropdownNavigation' => false,
				'iconStyle'             => 'line',
				'iconClass'             => 'fixture-account-icon',
			)
		);

		$processor = new \WP_HTML_Tag_Processor( $output );

		$this->assertTrue(
			$processor->next_tag(
				array(
					'tag_name'   => 'a',
					'class_name' => 'wc-block-customer-account__link',
				)
			),
			'Expected the simple-link anchor path.'
		);
		$this->assertStringNotContainsString( 'wc-block-customer-account__toggle', $output );
		$this->assertStringNotContainsString( 'wc-block-customer-account__dropdown', $output );

		$this->assert_icon_filter_call( 0, 'line', 'woocommerce/customer-account' );
		$this->assertCount( 1, $this->icon_filter_calls, 'Customer Account should filter exactly one icon.' );
		$this->assertSame( 'line', $this->icon_filter_calls[0]['attributes']['iconStyle'] );
		$this->assertSame( 'fixture-account-icon', $this->icon_filter_calls[0]['attributes']['iconClass'] );
		$this->assertSame( 'icon_and_text', $this->icon_filter_calls[0]['attributes']['displayStyle'] );
		$this->assert_filtered_icon( $output, 'fixture-account-icon' );
	}

	/**
	 * @testdox Customer Account filters only the primary alt icon and retains its bundled dropdown caret.
	 */
	public function test_customer_account_filters_only_primary_icon_on_dropdown_path(): void {
		$this->log_in_fixture_user();
		$this->create_page_for_option( 'woocommerce_myaccount_page_id', 'Fixture account page' );

		$output = $this->render_block(
			'woocommerce/customer-account',
			array(
				'displayStyle'          => 'icon_only',
				'hasDropdownNavigation' => true,
				'iconStyle'             => 'alt',
				'iconClass'             => 'fixture-dropdown-icon',
			)
		);

		$this->assert_icon_filter_call( 0, 'alt', 'woocommerce/customer-account' );
		$this->assertCount( 1, $this->icon_filter_calls, 'The bundled caret must not pass through the icon filter.' );
		$this->assertSame( true, $this->icon_filter_calls[0]['attributes']['hasDropdownNavigation'] );
		$this->assertSame( 'alt', $this->icon_filter_calls[0]['attributes']['iconStyle'] );
		$this->assert_filtered_icon( $output, 'fixture-dropdown-icon' );
		$this->assertStringContainsString( 'wc-block-customer-account__caret', $output, 'The bundled dropdown caret should remain.' );
	}

	/**
	 * @testdox Customer Account canonicalizes an unknown icon style without changing the visible default fallback.
	 */
	public function test_customer_account_canonicalizes_unknown_icon_style_to_default(): void {
		$attributes = array(
			'displayStyle'          => 'icon_and_text',
			'hasDropdownNavigation' => false,
			'iconStyle'             => 'fixture-unknown',
			'iconClass'             => 'fixture-unknown-style-icon',
		);
		$output     = $this->render_block( 'woocommerce/customer-account', $attributes );

		$this->assert_icon_filter_call( 0, 'default', 'woocommerce/customer-account' );
		$this->assertCount( 1, $this->icon_filter_calls );
		$this->assert_filtered_icon( $output, 'fixture-unknown-style-icon' );

		remove_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_icon_filter_call' ), PHP_INT_MAX );
		$unknown_default_output  = $this->render_block( 'woocommerce/customer-account', $attributes );
		$attributes['iconStyle'] = 'default';
		$explicit_default_output = $this->render_block( 'woocommerce/customer-account', $attributes );
		add_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_icon_filter_call' ), PHP_INT_MAX, 4 );

		$this->assertSame(
			$this->extract_primary_svg_bytes( $explicit_default_output, 'fixture-unknown-style-icon' ),
			$this->extract_primary_svg_bytes( $unknown_default_output, 'fixture-unknown-style-icon' ),
			'Unknown styles should keep the visible default icon semantics.'
		);
	}

	/**
	 * @testdox Customer Account text-only display does not invoke the icon filter.
	 */
	public function test_customer_account_text_only_display_does_not_filter_an_icon(): void {
		$output = $this->render_block(
			'woocommerce/customer-account',
			array(
				'displayStyle'          => 'text_only',
				'hasDropdownNavigation' => false,
				'iconStyle'             => 'line',
				'iconClass'             => 'fixture-text-only-icon',
			)
		);

		$this->assertCount( 0, $this->icon_filter_calls, 'Text-only display must not invoke the icon filter.' );
		$this->assertStringNotContainsString( 'fixture-icon', $output );
		$this->assertStringNotContainsString( '<svg', $output, 'Text-only display should not render an icon element.' );
	}

	/**
	 * @testdox Customer Account preserves its unfiltered $style icon output when the filter is a no-op.
	 * @dataProvider provide_customer_account_icon_styles
	 *
	 * @param string $style Customer Account icon style.
	 */
	public function test_customer_account_no_op_filter_preserves_icon_output( string $style ): void {
		remove_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_icon_filter_call' ), PHP_INT_MAX );
		$attributes = array(
			'displayStyle'          => 'icon_and_text',
			'hasDropdownNavigation' => false,
			'iconStyle'             => $style,
			'iconClass'             => 'fixture-byte-icon',
		);
		$unfiltered = $this->render_block( 'woocommerce/customer-account', $attributes );

		add_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_no_op_icon_filter_call' ), PHP_INT_MAX );
		$filtered = $this->render_block(
			'woocommerce/customer-account',
			$attributes
		);

		$this->assertSame( $this->extract_primary_svg_bytes( $unfiltered ), $this->extract_primary_svg_bytes( $filtered ) );
		$this->assertSame( 1, $this->no_op_filter_calls, 'Customer Account should invoke the no-op icon filter exactly once.' );
	}

	/**
	 * Provides Customer Account icon styles.
	 *
	 * @return array<string, array{string}>
	 */
	public function provide_customer_account_icon_styles(): array {
		return array(
			'default' => array( 'default' ),
			'line'    => array( 'line' ),
			'alt'     => array( 'alt' ),
		);
	}

	/**
	 * Captures stable caller context and replaces the icon.
	 *
	 * @param string $svg              Default SVG markup.
	 * @param string $icon_name        Canonical icon name.
	 * @param string $block_name       Block name.
	 * @param array  $block_attributes Render-time block attributes.
	 * @return string Replacement SVG markup.
	 */
	public function capture_icon_filter_call( $svg, $icon_name, $block_name, $block_attributes ): string {
		$context_keys = array_flip(
			array(
				'cartIcon',
				'content',
				'miniCartIcon',
				'iconColor',
				'iconColorValue',
				'displayStyle',
				'hasDropdownNavigation',
				'iconStyle',
				'iconClass',
			)
		);

		$this->icon_filter_calls[] = array(
			'icon_name'  => $icon_name,
			'block_name' => $block_name,
			'attributes' => array_intersect_key( $block_attributes, $context_keys ),
		);

		return self::REPLACEMENT_SVG;
	}

	/**
	 * Counts a no-op icon filter call and returns the exact default bytes.
	 *
	 * @param string $svg Default SVG markup.
	 * @return string Unchanged SVG markup.
	 */
	public function capture_no_op_icon_filter_call( $svg ): string {
		++$this->no_op_filter_calls;

		return $svg;
	}

	/**
	 * Prevents the isolated cart from registering session hooks.
	 *
	 * @param bool $must_initialize Whether cart session hooks should initialize.
	 * @return bool
	 */
	public function disable_cart_session_hooks( $must_initialize ): bool {
		return false;
	}

	/**
	 * Renders a registered dynamic block from serialized block markup.
	 *
	 * @param string               $block_name Block name.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string Rendered block output.
	 */
	private function render_block( string $block_name, array $attributes ): string {
		return do_blocks( '<!-- wp:' . $block_name . ' ' . wp_json_encode( $attributes ) . ' /-->' );
	}

	/**
	 * Asserts the stable identifying fields for one filter call.
	 *
	 * @param int    $index      Call index.
	 * @param string $icon_name  Expected canonical icon name.
	 * @param string $block_name Expected block name.
	 */
	private function assert_icon_filter_call( int $index, string $icon_name, string $block_name ): void {
		$this->assertArrayHasKey( $index, $this->icon_filter_calls, 'Expected an icon filter call.' );
		$this->assertSame( $icon_name, $this->icon_filter_calls[ $index ]['icon_name'] );
		$this->assertSame( $block_name, $this->icon_filter_calls[ $index ]['block_name'] );
	}

	/**
	 * Asserts filtered icon markup and caller-owned root invariants.
	 *
	 * @param string $output         Rendered block output.
	 * @param string $required_class Caller-required SVG class.
	 */
	private function assert_filtered_icon( string $output, string $required_class ): void {
		$processor         = new \WP_HTML_Tag_Processor( $output );
		$primary_svg_roots = 0;

		while ( $processor->next_tag( array( 'tag_name' => 'svg' ) ) ) {
			if ( ! $processor->has_class( $required_class ) ) {
				continue;
			}

			++$primary_svg_roots;
			$this->assertTrue( $processor->has_class( 'fixture-icon' ), 'The caller primary-icon class must belong to the replacement SVG.' );
			$this->assertSame( 'true', $processor->get_attribute( 'aria-hidden' ) );
			$this->assertSame( 'false', $processor->get_attribute( 'focusable' ) );
		}

		$this->assertSame( 1, $primary_svg_roots, "Expected exactly one SVG root with the {$required_class} caller class." );
	}

	/**
	 * Extracts the primary SVG substring without reserializing its markup.
	 *
	 * @param string $output         Rendered block output.
	 * @param string $required_class Class identifying the primary SVG.
	 * @return string Exact SVG bytes.
	 */
	private function extract_primary_svg_bytes( string $output, string $required_class = 'fixture-byte-icon' ): string {
		$matched = preg_match( '/<svg\b[^>]*\bclass="' . preg_quote( $required_class, '/' ) . '"[^>]*>.*?<\/svg>/s', $output, $matches );

		$this->assertSame( 1, $matched, 'Expected the primary Customer Account SVG.' );

		return $matches[0];
	}

	/**
	 * Creates and authenticates a generic fixture user.
	 */
	private function log_in_fixture_user(): void {
		wp_set_current_user( self::factory()->user->create() );
	}

	/**
	 * Resets request-scoped page detection cached by production code.
	 */
	private function reset_cart_checkout_page_cache(): void {
		$reflection = new \ReflectionClass( CartCheckoutUtils::class );
		foreach ( array( 'is_cart_page', 'is_checkout_page' ) as $property_name ) {
			$property = $reflection->getProperty( $property_name );
			$property->setAccessible( true );
			$property->setValue( null, null );
		}
	}

	/**
	 * Creates a published page and assigns it to a WooCommerce page option.
	 *
	 * @param string $option_name WooCommerce page option name.
	 * @param string $title       Page title.
	 * @return int Page ID.
	 */
	private function create_page_for_option( string $option_name, string $title ): int {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);
		update_option( $option_name, $page_id );

		return $page_id;
	}
}
