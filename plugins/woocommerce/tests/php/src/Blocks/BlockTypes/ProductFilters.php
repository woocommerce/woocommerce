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
	 * Reflection method used to generate responsive styles with controlled viewport queries.
	 *
	 * @var \ReflectionMethod
	 */
	private $responsive_styles_method;

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

		$this->responsive_styles_method = new \ReflectionMethod( ProductFiltersBlock::class, 'get_responsive_styles' );
		$this->responsive_styles_method->setAccessible( true );
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
	 * @testdox Renders canonical and legacy overlay settings safely.
	 * @dataProvider overlay_attributes_provider
	 *
	 * @param array  $attributes    Block attributes.
	 * @param string $expected_mode Expected overlay mode.
	 * @param bool   $right         Whether the overlay is expected on the right.
	 */
	public function test_overlay_attributes( array $attributes, string $expected_mode, bool $right ): void {
		$parsed          = parse_blocks(
			'<!-- wp:woocommerce/product-filters --><!-- wp:html --><span data-product-filters-test>Inner</span><!-- /wp:html --><!-- /wp:woocommerce/product-filters -->'
		)[0];
		$parsed['attrs'] = $attributes;
		$output          = render_block( $parsed );
		$has_overlay     = 'off' !== $expected_mode;

		$this->assertSame( 1, substr_count( $output, 'data-product-filters-test' ), 'Inner blocks should render exactly once.' );
		if ( $has_overlay ) {
			$this->assertStringContainsString( '__overlay-dialog', $output, 'Overlay modes should render the overlay dialog.' );
			$this->assertStringNotContainsString( 'is-filter-drawer-disabled', $output, 'Overlay modes should not render the disabled class.' );
			$this->assertStringContainsString( 'overlay-wrapper" data-wp-on--click="actions.closeOverlayOnBackdrop"', $output, 'Overlay modes should retain backdrop closing behavior.' );
		} else {
			$this->assertStringNotContainsString( '__overlay-dialog', $output, 'Off mode should not render the overlay dialog.' );
			$this->assertStringContainsString( 'is-filter-drawer-disabled', $output, 'Off mode should render the disabled class.' );
		}

		if ( 'mobile' === $expected_mode ) {
			$this->assertStringContainsString( 'is-mobile-overlay', $output, 'Mobile mode should render its runtime marker.' );
		} else {
			$this->assertStringNotContainsString( 'is-mobile-overlay', $output, 'Non-mobile modes should not render the mobile marker.' );
		}

		if ( 'tablet' === $expected_mode ) {
			$this->assertStringContainsString( 'is-tablet-overlay', $output, 'Tablet mode should render its runtime marker.' );
		} else {
			$this->assertStringNotContainsString( 'is-tablet-overlay', $output, 'Non-tablet modes should not render the tablet marker.' );
		}

		$this->assertStringNotContainsString( 'is-responsive-overlay', $output, 'Deprecated responsive marker should not be rendered.' );
		$this->assertStringNotContainsString( 'has-desktop-overlay', $output, 'Deprecated desktop marker should not be rendered.' );
		if ( $right ) {
			$this->assertStringContainsString( 'is-overlay-right', $output, 'Right position should render its runtime marker.' );
		} else {
			$this->assertStringNotContainsString( 'is-overlay-right', $output, 'Left position should not render the right marker.' );
		}
	}

	/**
	 * Provides overlay settings.
	 *
	 * @return array<string, array{array, string, bool}>
	 */
	public function overlay_attributes_provider(): array {
		return array(
			'legacy default tablet'   => array( array(), 'tablet', false ),
			'legacy off'              => array( array( 'showFilterDrawer' => false ), 'off', false ),
			'legacy malformed tablet' => array( array( 'showFilterDrawer' => 'false' ), 'tablet', false ),
			'off'                     => array( array( 'overlayMode' => 'off' ), 'off', false ),
			'mobile right'            => array(
				array(
					'overlayMode'     => 'mobile',
					'overlayPosition' => 'right',
				),
				'mobile',
				true,
			),
			'tablet invalid position' => array(
				array(
					'overlayMode'     => 'tablet',
					'overlayPosition' => 'start',
				),
				'tablet',
				false,
			),
			'always'                  => array( array( 'overlayMode' => 'always' ), 'always', false ),
			'invalid enum fallback'   => array(
				array(
					'overlayMode'      => 'desktop',
					'showFilterDrawer' => false,
				),
				'off',
				false,
			),
		);
	}

	/**
	 * @testdox Generates scoped responsive styles from resolved viewport queries.
	 * @dataProvider responsive_styles_provider
	 *
	 * @param array    $viewport_media_queries Viewport media queries.
	 * @param string[] $expected                Expected CSS fragments.
	 * @param string[] $unexpected              Unexpected CSS fragments.
	 */
	public function test_responsive_styles( array $viewport_media_queries, array $expected, array $unexpected = array() ): void {
		$styles = (string) $this->responsive_styles_method->invoke( $this->product_filters, $viewport_media_queries );

		foreach ( $expected as $fragment ) {
			$this->assertStringContainsString( $fragment, $styles, "Responsive CSS should contain: {$fragment}" );
		}
		foreach ( $unexpected as $fragment ) {
			$this->assertStringNotContainsString( $fragment, $styles, "Responsive CSS should not contain: {$fragment}" );
		}
	}

	/**
	 * Provides resolved viewport queries and expected scoped output.
	 *
	 * @return array<string, array{array<string, string>, string[], string[]}>
	 */
	public function responsive_styles_provider(): array {
		return array(
			'default viewports' => array(
				array(
					'@mobile'  => '@media (width <= 480px)',
					'@tablet'  => '@media (480px < width <= 782px)',
					'@desktop' => '@media (width > 782px)',
				),
				array( '@media (480px < width <= 782px)', '@media (width > 782px)', '.is-mobile-overlay', '.is-tablet-overlay', 'top:0;right:0;bottom:0;left:0', '--wc-product-filters-overlay-transition:none' ),
				array( '@media (width <= 480px)', 'is-responsive-overlay', 'is-overlay-opened', 'wp-block-group', '600px', 'has-desktop-overlay' ),
			),
			'custom rem units'  => array(
				array(
					'@mobile'  => '@media (width <= 30rem)',
					'@tablet'  => '@media (30rem < width <= 45rem)',
					'@desktop' => '@media (width > 45rem)',
				),
				array( '@media (30rem < width <= 45rem)', '@media (width > 45rem)' ),
				array( '@media (width <= 30rem)' ),
			),
			'custom em units'   => array(
				array(
					'@mobile'  => '@media (width <= 32em)',
					'@tablet'  => '@media (32em < width <= 48em)',
					'@desktop' => '@media (width > 48em)',
				),
				array( '@media (32em < width <= 48em)', '@media (width > 48em)' ),
				array( '@media (width <= 32em)' ),
			),
			'only mobile alias' => array(
				array(
					'@mobile'  => '@media (width <= 30rem)',
					'@desktop' => '@media (width > 30rem)',
				),
				array( '@media (width > 30rem)', '.is-mobile-overlay', '.is-tablet-overlay' ),
				array( '@media (width <= 30rem)', 'is-responsive-overlay' ),
			),
			'only tablet alias' => array(
				array(
					'@tablet'  => '@media (width <= 45rem)',
					'@desktop' => '@media (width > 45rem)',
				),
				array( '@media (width <= 45rem)', '@media (width > 45rem)', '.is-mobile-overlay', '.is-tablet-overlay' ),
				array( 'is-responsive-overlay' ),
			),
		);
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
}
