<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;

/**
 * Render-level tests for the block icon filter integration.
 */
class BlockIconFilterTest extends WC_Unit_Test_Case {
	private const REPLACEMENT_SVG = '<svg class="fixture-icon" viewBox="0 0 24 24"><path d="M2 2h20v20H2z" fill="currentColor"/></svg>';

	private const CUSTOMER_ACCOUNT_DEFAULT_SVG = '<svg class="fixture-byte-icon" xmlns="http://www.w3.org/2000/svg" viewbox="-5 -5 25 25">' .
		"\n\t\t\t" .
		'<path fill-rule="evenodd" clip-rule="evenodd" d="M8.00009 8.34785C10.3096 8.34785 12.1819 6.47909 12.1819 4.17393C12.1819 1.86876 10.3096 0 8.00009 0C5.69055 0 3.81824 1.86876 3.81824 4.17393C3.81824 6.47909 5.69055 8.34785 8.00009 8.34785ZM0.333496 15.6522C0.333496 15.8444 0.489412 16 0.681933 16H15.3184C15.5109 16 15.6668 15.8444 15.6668 15.6522V14.9565C15.6668 12.1428 13.7821 9.73911 10.0912 9.73911H5.90931C2.21828 9.73911 0.333645 12.1428 0.333645 14.9565L0.333496 15.6522Z" fill="currentColor" />' .
		"\n\t\t" .
		'</svg>';

	private const CUSTOMER_ACCOUNT_LINE_SVG = '<svg class="fixture-byte-icon" viewbox="1 1 29 29" xmlns="http://www.w3.org/2000/svg">' .
		"\n\t\t\t\t" .
		'<circle cx="16" cy="10.5" r="3.5" stroke="currentColor" stroke-width="2" fill="none" />' .
		"\n\t\t\t\t" .
		'<path fill-rule="evenodd" clip-rule="evenodd" d="M11.5 18.5H20.5C21.8807 18.5 23 19.6193 23 21V25.5H25V21C25 18.5147 22.9853 16.5 20.5 16.5H11.5C9.01472 16.5 7 18.5147 7 21V25.5H9V21C9 19.6193 10.1193 18.5 11.5 18.5Z" fill="currentColor" />' .
		"\n\t\t\t" .
		'</svg>';

	private const CUSTOMER_ACCOUNT_ALT_SVG = '<svg class="fixture-byte-icon" xmlns="http://www.w3.org/2000/svg" viewbox="-4 -4 25 25">' .
		"\n\t\t\t\t" .
		'<path d="M9 0C4.03579 0 0 4.03579 0 9C0 13.9642 4.03579 18 9 18C13.9642 18 18 13.9642 18 9C18 4.03579 13.9642 0 9 0ZM9 4.32C10.5347 4.32 11.7664 5.57056 11.7664 7.08638C11.7664 8.62109 10.5158 9.85277 9 9.85277C7.4653 9.85277 6.23362 8.60221 6.23362 7.08638C6.23362 5.57056 7.46526 4.32 9 4.32ZM9 10.7242C11.1221 10.7242 12.96 12.2021 13.7937 14.4189C12.5242 15.5559 10.8379 16.238 9 16.238C7.16207 16.238 5.49474 15.5369 4.20632 14.4189C5.05891 12.2021 6.87793 10.7242 9 10.7242Z" fill="currentColor" />' .
		"\n\t\t\t" .
		'</svg>';

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
	 * Original current user ID.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Original WooCommerce cart instance.
	 *
	 * @var \WC_Cart|null
	 */
	private $original_cart;

	/**
	 * Isolated empty cart used by render tests.
	 *
	 * @var \WC_Cart
	 */
	private $test_cart;

	/**
	 * Registered Mini-Cart block instance.
	 *
	 * @var object|null
	 */
	private $mini_cart_block_instance;

	/**
	 * Whether the Mini-Cart footer callback existed before the test.
	 *
	 * @var bool
	 */
	private $mini_cart_footer_hook_was_registered = false;

	/**
	 * Original option state.
	 *
	 * @var array<string, array{exists: bool, value: mixed}>
	 */
	private $original_options = array();

	/**
	 * Page IDs created by this test.
	 *
	 * @var array<int>
	 */
	private $created_page_ids = array();

	/**
	 * Original query-related globals.
	 *
	 * @var array<string, array{exists: bool, value: mixed}>
	 */
	private $original_query_globals = array();

	/**
	 * Original request state.
	 *
	 * @var array<string, mixed>
	 */
	private $original_request_state = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_user_id = get_current_user_id();
		$this->remember_options();
		$this->remember_query_state();
		$this->remember_mini_cart_footer_hook_state();

		$this->original_cart = WC()->cart;
		add_filter( 'woocommerce_cart_session_initialize', array( $this, 'disable_cart_session_hooks' ) );
		$this->test_cart = new \WC_Cart();
		remove_filter( 'woocommerce_cart_session_initialize', array( $this, 'disable_cart_session_hooks' ) );
		WC()->cart = $this->test_cart;

		add_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_icon_filter_call' ), PHP_INT_MAX, 4 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_icon_filter_call' ), PHP_INT_MAX );
		remove_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_no_op_icon_filter_call' ), PHP_INT_MAX );
		remove_filter( 'woocommerce_cart_session_initialize', array( $this, 'disable_cart_session_hooks' ) );

		$this->remove_test_cart_hooks();
		WC()->cart = $this->original_cart;
		$this->remove_mini_cart_footer_hook();

		wp_set_current_user( $this->original_user_id );
		foreach ( $this->created_page_ids as $page_id ) {
			wp_delete_post( $page_id, true );
		}
		$this->restore_options();
		$this->restore_query_state();

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
	 * @testdox Customer Account preserves exact $style icon bytes when the filter is a no-op.
	 * @dataProvider provide_customer_account_default_icon_bytes
	 *
	 * @param string $style        Customer Account icon style.
	 * @param string $expected_svg Exact post-KSES SVG bytes.
	 */
	public function test_customer_account_no_op_filter_preserves_exact_icon_bytes( string $style, string $expected_svg ): void {
		remove_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_icon_filter_call' ), PHP_INT_MAX );
		add_filter( 'woocommerce_blocks_icon_svg', array( $this, 'capture_no_op_icon_filter_call' ), PHP_INT_MAX );

		$output = $this->render_block(
			'woocommerce/customer-account',
			array(
				'displayStyle'          => 'icon_and_text',
				'hasDropdownNavigation' => false,
				'iconStyle'             => $style,
				'iconClass'             => 'fixture-byte-icon',
			)
		);

		$this->assertSame( $expected_svg, $this->extract_primary_svg_bytes( $output ) );
		$this->assertSame( 1, $this->no_op_filter_calls, 'Customer Account should invoke the no-op icon filter exactly once.' );
	}

	/**
	 * Provides exact caller-visible Customer Account SVG bytes.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function provide_customer_account_default_icon_bytes(): array {
		return array(
			'default' => array( 'default', self::CUSTOMER_ACCOUNT_DEFAULT_SVG ),
			'line'    => array( 'line', self::CUSTOMER_ACCOUNT_LINE_SVG ),
			'alt'     => array( 'alt', self::CUSTOMER_ACCOUNT_ALT_SVG ),
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
	 * Creates a published page and assigns it to a WooCommerce page option.
	 *
	 * @param string $option_name WooCommerce page option name.
	 * @param string $title       Page title.
	 * @return int Page ID.
	 */
	private function create_page_for_option( string $option_name, string $title ): int {
		$page_id                  = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);
		$this->created_page_ids[] = $page_id;
		update_option( $option_name, $page_id );

		return $page_id;
	}

	/**
	 * Remembers WooCommerce page options changed by render fixtures.
	 */
	private function remember_options(): void {
		foreach ( array( 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id', 'woocommerce_myaccount_page_id' ) as $option_name ) {
			$missing = new \stdClass();
			$value   = get_option( $option_name, $missing );

			$this->original_options[ $option_name ] = array(
				'exists' => $missing !== $value,
				'value'  => $value,
			);
		}
	}

	/**
	 * Restores WooCommerce page options changed by render fixtures.
	 */
	private function restore_options(): void {
		foreach ( $this->original_options as $option_name => $state ) {
			if ( $state['exists'] ) {
				update_option( $option_name, $state['value'] );
			} else {
				delete_option( $option_name );
			}
		}
	}

	/**
	 * Remembers query globals and request values changed by go_to().
	 */
	private function remember_query_state(): void {
		foreach ( array( 'wp', 'wp_query', 'wp_the_query', 'post' ) as $global_name ) {
			$exists = array_key_exists( $global_name, $GLOBALS );
			$value  = $exists ? $GLOBALS[ $global_name ] : null;

			$this->original_query_globals[ $global_name ] = array(
				'exists' => $exists,
				'value'  => is_object( $value ) ? clone $value : $value,
			);
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw request globals are captured only for test teardown.
		$this->original_request_state = array(
			'get'          => $_GET,
			'request'      => $_REQUEST,
			'request_uri'  => array_key_exists( 'REQUEST_URI', $_SERVER ) ? $_SERVER['REQUEST_URI'] : null,
			'query_string' => array_key_exists( 'QUERY_STRING', $_SERVER ) ? $_SERVER['QUERY_STRING'] : null,
		);
		// phpcs:enable
	}

	/**
	 * Restores query globals and request values changed by go_to().
	 */
	private function restore_query_state(): void {
		foreach ( $this->original_query_globals as $global_name => $state ) {
			if ( $state['exists'] ) {
				$GLOBALS[ $global_name ] = $state['value'];
			} else {
				unset( $GLOBALS[ $global_name ] );
			}
		}

		$_GET     = $this->original_request_state['get'];
		$_REQUEST = $this->original_request_state['request'];
		$this->restore_server_value( 'REQUEST_URI', $this->original_request_state['request_uri'] );
		$this->restore_server_value( 'QUERY_STRING', $this->original_request_state['query_string'] );
	}

	/**
	 * Restores a request server value, including its missing state.
	 *
	 * @param string      $key   Server key.
	 * @param string|null $value Original value, or null when absent.
	 */
	private function restore_server_value( string $key, $value ): void {
		if ( null === $value ) {
			unset( $_SERVER[ $key ] );
		} else {
			$_SERVER[ $key ] = $value;
		}
	}

	/**
	 * Removes every hook callback registered by the isolated WC_Cart instance.
	 */
	private function remove_test_cart_hooks(): void {
		global $wp_filter;

		$callbacks_to_remove = array();
		foreach ( $wp_filter as $hook_name => $hook ) {
			if ( ! $hook instanceof \WP_Hook ) {
				continue;
			}

			foreach ( $hook->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = is_array( $callback ) ? ( $callback['function'] ?? null ) : null;
					if ( is_array( $function ) && isset( $function[0] ) && $this->test_cart === $function[0] ) {
						$callbacks_to_remove[] = array( $hook_name, $function, $priority );
					}
				}
			}
		}

		foreach ( $callbacks_to_remove as $callback ) {
			remove_filter( $callback[0], $callback[1], $callback[2] );
		}
	}

	/**
	 * Captures the registered Mini-Cart block's footer hook state.
	 */
	private function remember_mini_cart_footer_hook_state(): void {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce/mini-cart' );
		$callback   = $block_type ? $block_type->render_callback : null;

		if ( is_array( $callback ) && isset( $callback[0] ) && is_object( $callback[0] ) ) {
			$this->mini_cart_block_instance = $callback[0];

			$this->mini_cart_footer_hook_was_registered = false !== has_action( 'wp_footer', array( $this->mini_cart_block_instance, 'render_mini_cart_overlay' ) );
		}
	}

	/**
	 * Removes only a Mini-Cart footer callback newly registered by the test.
	 */
	private function remove_mini_cart_footer_hook(): void {
		if ( ! $this->mini_cart_block_instance ) {
			return;
		}

		$callback = array( $this->mini_cart_block_instance, 'render_mini_cart_overlay' );

		if ( ! $this->mini_cart_footer_hook_was_registered && false !== has_action( 'wp_footer', $callback ) ) {
			remove_action( 'wp_footer', $callback, 10 );
		}
	}
}
