<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductFilters as ProductFiltersBlock;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;

/**
 * Tests for the ProductFilters block type.
 */
class ProductFilters extends \WP_UnitTestCase {

	/**
	 * Instance of the Product Filters block under test.
	 *
	 * @var ProductFiltersBlock
	 */
	private $product_filters;

	/**
	 * Tracks dynamically added filters so they can be removed during tearDown.
	 *
	 * @var array
	 */
	private $registered_filters = [];

	/**
	 * Reflection method used to invoke the private get_canonical_url_no_pagination method.
	 *
	 * @var \ReflectionMethod
	 */
	private $canonical_method;

	/**
	 * Set up the test subject and dependencies.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$asset_api            = Package::container()->get( Api::class );
		$asset_data_registry  = new AssetDataRegistryMock( $asset_api );
		$integration_registry = new IntegrationRegistry();

		// Override initialize() so block registration does not run during isolated tests.
		$this->product_filters = new class( $asset_api, $asset_data_registry, $integration_registry ) extends ProductFiltersBlock {
			/**
			 * Skip block registration for unit tests.
			 */
			protected function initialize() {}
		};

		$this->canonical_method = new \ReflectionMethod( ProductFiltersBlock::class, 'get_canonical_url_no_pagination' );
		$this->canonical_method->setAccessible( true );
	}

	/**
	 * Clean up dynamically added hooks.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ( array_reverse( $this->registered_filters ) as $filter ) {
			remove_filter( $filter['hook'], $filter['callback'], $filter['priority'] );
		}

		$this->registered_filters = [];
		parent::tearDown();
	}

	/**
	 * Helper to register filters and track them for cleanup.
	 *
	 * @param string   $hook          Filter name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Accepted arguments.
	 * @return void
	 */
	private function register_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		add_filter( $hook, $callback, $priority, $accepted_args );

		$this->registered_filters[] = [
			'hook'     => $hook,
			'callback' => $callback,
			'priority' => $priority,
		];
	}

	/**
	 * Convenience wrapper for invoking the private canonical URL helper.
	 *
	 * @param array $filter_params Filter parameters.
	 * @return string
	 */
	private function invoke_canonical_url_helper( array $filter_params ): string {
		return (string) $this->canonical_method->invoke( $this->product_filters, $filter_params );
	}

	/**
	 * Ensures get_pagenum_link filters receive the expected argument types.
	 *
	 * @param mixed $link    Base URL from WordPress.
	 * @param mixed $pagenum Requested page number.
	 * @param mixed $escape  Escape flag passed by WordPress.
	 * @return void
	 */
	private function assert_get_pagenum_link_signature( $link, $pagenum, $escape ): void {
		$this->assertTrue(
			is_string( $link ),
			'The get_pagenum_link filter must receive a string URL as the first argument.'
		);
		$this->assertTrue(
			is_numeric( $pagenum ),
			'The get_pagenum_link filter must receive a numeric page number as the second argument.'
		);
		$this->assertTrue(
			null === $escape || is_bool( $escape ),
			'The get_pagenum_link filter must receive a null or boolean escape flag as the third argument.'
		);
	}

	/**
	 * Ensures canonical URL strips recognised filter parameters while keeping other arguments in place.
	 *
	 * @return void
	 */
	public function test_canonical_url_strips_registered_filter_params(): void {
		$is_singular_filter      = function ( $is_singular ) {
			$this->assertTrue(
				is_bool( $is_singular ),
				'The is_singular filter should pass a boolean context flag.'
			);
			return false;
		};
		$get_pagenum_link_filter = function ( $link, $pagenum, $escape = null ) {
			$this->assert_get_pagenum_link_signature( $link, $pagenum, $escape );
			return 'https://example.org/shop/?min_price=10&max_price=100&orderby=price';
		};

		$this->register_filter( 'is_singular', $is_singular_filter );
		$this->register_filter( 'get_pagenum_link', $get_pagenum_link_filter, 10, 3 );

		$canonical_url = $this->invoke_canonical_url_helper(
			[
				'min_price' => '10',
				'max_price' => '100',
			]
		);

			$this->assertSame( 'https://example.org/shop/?orderby=price', $canonical_url );
	}

	/**
	 * Ensures canonical URL is returned unchanged when no query string exists.
	 *
	 * @return void
	 */
	public function test_canonical_url_returns_original_when_query_is_empty(): void {
		$is_singular_filter      = function ( $is_singular ) {
			$this->assertTrue(
				is_bool( $is_singular ),
				'The is_singular filter should pass a boolean context flag.'
			);
			return false;
		};
		$get_pagenum_link_filter = function ( $link, $pagenum, $escape = null ) {
			$this->assert_get_pagenum_link_signature( $link, $pagenum, $escape );
			return 'https://example.org/shop/';
		};

		$this->register_filter( 'is_singular', $is_singular_filter );
		$this->register_filter( 'get_pagenum_link', $get_pagenum_link_filter, 10, 3 );

		$canonical_url = $this->invoke_canonical_url_helper(
			[
				'min_price' => '10',
			]
		);

		$this->assertSame( 'https://example.org/shop/', $canonical_url );
	}

	/**
	 * Ensures canonical URL is not returned with escaped HTML entities.
	 *
	 * @param string $encoded_url  URL containing HTML entities.
	 * @param string $expected_url Decoded URL expected from helper.
	 *
	 * @return void
	 *
	 * @dataProvider entity_encoded_url_provider
	 */
	public function test_canonical_url_decodes_html_entities( string $encoded_url, string $expected_url ): void {
		$is_singular_filter = function ( $is_singular ) {
			$this->assertTrue(
				is_bool( $is_singular ),
				'The is_singular filter should pass a boolean context flag.'
			);
			return false;
		};

		$this->register_filter( 'is_singular', $is_singular_filter );
		$this->register_filter(
			'get_pagenum_link',
			function ( $link, $pagenum, $escape = null ) use ( $encoded_url ) {
				$this->assert_get_pagenum_link_signature( $link, $pagenum, $escape );
				return $encoded_url;
			},
			10,
			3
		);

		$canonical_url = $this->invoke_canonical_url_helper( [] );

		$this->assertSame( $expected_url, $canonical_url );
	}

	/**
	 * Provides sample URLs containing common HTML entities.
	 *
	 * @return array[]
	 */
	public function entity_encoded_url_provider(): array {
		return [
			'ampersand'    => [
				'https://example.org/shop/?orderby=price&#038;other=value',
				'https://example.org/shop/?orderby=price&other=value',
			],
			'single_quote' => [
				'https://example.org/shop/?quote=She&#039;s+great',
				'https://example.org/shop/?quote=She\'s+great',
			],
		];
	}

	/**
	 * @testdox Overlay accessibility attributes follow the client-side open state.
	 *
	 * @param string $overlay_mode Overlay presentation mode.
	 * @testWith ["mobile"]
	 *           ["always"]
	 */
	public function test_overlay_accessibility_attributes( string $overlay_mode ): void {
		$processor = new \WP_HTML_Tag_Processor( $this->render_filters( array( 'overlayMode' => $overlay_mode ) ) );

		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'wc-block-product-filters__open-overlay' ) ), 'The overlay should have an opener.' );
		$this->assertSame( 'state.isOverlayOpen', $processor->get_attribute( 'data-wp-bind--aria-expanded' ), 'The opener should expose whether its overlay is open.' );

		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'wc-block-product-filters__overlay' ) ), 'The overlay should be rendered.' );
		$this->assertSame( 'state.isOverlayHidden', $processor->get_attribute( 'data-wp-bind--inert' ), 'A closed overlay should prevent interaction with its controls.' );
		$this->assertNull( $processor->get_attribute( 'inert' ), 'The server should not make desktop inline filters inert before hydration.' );

		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'wc-block-product-filters__overlay-dialog' ) ), 'The overlay should have a dialog container.' );
		$this->assertSame( 'state.overlayRole', $processor->get_attribute( 'data-wp-bind--role' ), 'Dialog semantics should follow the active overlay state.' );
		$this->assertSame( 'state.overlayAriaModal', $processor->get_attribute( 'data-wp-bind--aria-modal' ), 'Only an open overlay should be announced as modal.' );
		$this->assertNull( $processor->get_attribute( 'role' ), 'The closed or inline filters should not be rendered as a dialog.' );
		$this->assertNull( $processor->get_attribute( 'aria-modal' ), 'The server should not announce the initially closed overlay as modal.' );
		$this->assertSame( '-1', $processor->get_attribute( 'tabindex' ), 'The dialog needs a programmatic focus fallback without an extra tab stop.' );
		$this->assertSame( 'Product Filters', $processor->get_attribute( 'aria-label' ), 'The dialog should retain its accessible name.' );
	}

	/**
	 * @testdox Disabling the overlay leaves the filter content inline without modal behavior.
	 */
	public function test_disabled_overlay_keeps_filters_inline(): void {
		$markup    = $this->render_filters( array( 'overlayMode' => 'off' ) );
		$processor = new \WP_HTML_Tag_Processor( $markup );

		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'wc-block-product-filters' ) ), 'The filters wrapper should be rendered.' );
		$this->assertNull( $processor->get_attribute( 'data-wp-on-document--keydown' ), 'Inline filters should not register modal keyboard handling.' );
		$this->assertNull( $processor->get_attribute( 'data-wp-watch--scrolling' ), 'Inline filters should not lock page scrolling.' );
		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'wc-block-product-filters__content' ) ), 'Disabled overlays should render inline content.' );
		$this->assertStringContainsString( 'Filter content', $markup, 'The inner filter content should be preserved.' );
		$this->assertStringNotContainsString( 'wc-block-product-filters__overlay', $markup, 'Disabled overlays should not render a dialog or backdrop.' );
	}

	/**
	 * Render the registered Product Filters block with minimal inner content.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered markup.
	 */
	private function render_filters( array $attributes ): string {
		$blocks = parse_blocks(
			sprintf(
				'<!-- wp:woocommerce/product-filters %s --><div class="wp-block-woocommerce-product-filters"><!-- wp:paragraph --><p>Filter content</p><!-- /wp:paragraph --></div><!-- /wp:woocommerce/product-filters -->',
				wp_json_encode( $attributes )
			)
		);

		return render_block( $blocks[0] );
	}
}
