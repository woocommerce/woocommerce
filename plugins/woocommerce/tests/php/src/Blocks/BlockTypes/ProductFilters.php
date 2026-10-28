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
	 * Test-only filter block name.
	 */
	private const TEST_FILTER_BLOCK = 'woocommerce/test-product-filter-empty-state';

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
	 * Reflection method used to invoke the protected render method.
	 *
	 * @var \ReflectionMethod
	 */
	private $render_method;

	/**
	 * @testdox Local filter counts preserve saved taxonomy restrictions, including empty collections.
	 */
	public function test_nested_local_filters_preserve_saved_taxonomy_and_empty_collection(): void {
		$red  = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Local red',
				'slug'     => 'local-red',
			)
		);
		$blue = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Local blue',
				'slug'     => 'local-blue',
			)
		);
		foreach ( array( 10, 30, 40 ) as $price ) {
			$product = \WC_Helper_Product::create_simple_product();
			$product->set_regular_price( (string) $price );
			$product->set_category_ids( array( 40 === $price ? $blue : $red ) );
			$product->save();
		}
		$attributes = array(
			'queryId' => 0,
			'query'   => array(
				'isProductCollectionBlock' => true,
				'postType'                 => 'product',
				'inherit'                  => false,
				'perPage'                  => 10,
				'taxQuery'                 => array( 'product_cat' => array( $red ) ),
			),
		);
		$markup     = '<!-- wp:woocommerce/product-collection ' . wp_json_encode( $attributes ) . ' --><div class="wp-block-woocommerce-product-collection">'
			. '<!-- wp:woocommerce/product-filters --><div class="wp-block-woocommerce-product-filters">'
			. '<!-- wp:woocommerce/product-filter-taxonomy {"taxonomy":"product_cat","showCounts":true} --><div>'
			. '<!-- wp:woocommerce/product-filter-checkbox-list --><div class="wc-block-product-filter-checkbox-list"></div><!-- /wp:woocommerce/product-filter-checkbox-list -->'
			. '</div><!-- /wp:woocommerce/product-filter-taxonomy --></div><!-- /wp:woocommerce/product-filters -->'
			. '<!-- wp:woocommerce/product-template --><!-- wp:post-title /--><!-- /wp:woocommerce/product-template -->'
			. '</div><!-- /wp:woocommerce/product-collection -->';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Restore the original request verbatim after the test.
		$original_uri = $_SERVER['REQUEST_URI'] ?? null;
		try {
			$_SERVER['REQUEST_URI'] = '/?wcp-0-categories=local-blue&wcp-0-min_price=20&min_price=999';
			$html                   = do_blocks( $markup );
			$this->assertStringContainsString( 'wp-block-woocommerce-product-collection', $html );
			$this->assertStringNotContainsString( 'wp-block-post-title', $html );
			$this->assertStringContainsString( 'aria-label="Local red"', $html );
			$this->assertStringNotContainsString( 'aria-label="Local blue"', $html );
			$this->assertStringContainsString( 'data-wp-text="context.item.count">1</span>', $html );

			$_SERVER['REQUEST_URI'] = '/?wcp-0-categories=local-red&min_price=999';
			$html                   = do_blocks( $markup );
			$this->assertStringContainsString( 'wp-block-post-title', $html );
			$this->assertStringContainsString( 'data-wp-text="context.item.count">2</span>', $html );
		} finally {
			if ( null === $original_uri ) {
				unset( $_SERVER['REQUEST_URI'] );
			} else {
				$_SERVER['REQUEST_URI'] = $original_uri;
			}
		}
	}

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

		$this->render_method = new \ReflectionMethod( ProductFiltersBlock::class, 'render' );
		$this->render_method->setAccessible( true );

		register_block_type(
			self::TEST_FILTER_BLOCK,
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'productFiltersKey' ),
				'attributes'      => array(
					'empty' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
				'render_callback' => static function ( $attributes, $content, $block ) {
					$product_filters_key = $block->context['productFiltersKey'] ?? '';
					if ( is_string( $product_filters_key ) && '' !== $product_filters_key ) {
						$state = wp_interactivity_state( 'woocommerce/product-filters' );
						$state['filterEmptyStates'][ $product_filters_key ][] = (bool) $attributes['empty'];
						wp_interactivity_state( 'woocommerce/product-filters', $state );
					}

					return '<div class="test-product-filter"></div>';
				},
			)
		);
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

		if ( \WP_Block_Type_Registry::get_instance()->is_registered( self::TEST_FILTER_BLOCK ) ) {
			unregister_block_type( self::TEST_FILTER_BLOCK );
		}

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
	 * Render Product Filters with test filter states.
	 *
	 * @param bool[] $empty_states Empty-state value for each test filter.
	 * @return string Rendered Product Filters markup.
	 */
	private function render_with_filter_states( array $empty_states ): string {
		$inner_blocks = array_map(
			static function ( $is_empty ) {
				return array(
					'blockName'    => self::TEST_FILTER_BLOCK,
					'attrs'        => array( 'empty' => $is_empty ),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				);
			},
			$empty_states
		);

		$parsed_block = array(
			'blockName'    => 'woocommerce/product-filters',
			'attrs'        => array(),
			'innerBlocks'  => $inner_blocks,
			'innerHTML'    => '',
			'innerContent' => array(),
		);
		$block        = new \WP_Block( $parsed_block, array() );

		return (string) $this->render_method->invoke( $this->product_filters, array(), '', $block );
	}

	/**
	 * @testdox All participating filters being empty marks the wrapper as empty.
	 *
	 * @return void
	 */
	public function test_wrapper_has_empty_class_when_all_filters_are_empty(): void {
		$output = $this->render_with_filter_states( array( true, true ) );

		$this->assertStringContainsString( 'wc-block-product-filters--empty', $output );
	}

	/**
	 * @testdox One visible filter keeps the wrapper out of the empty state in either order.
	 *
	 * @param bool[] $empty_states Empty-state value for each test filter.
	 * @return void
	 *
	 * @testWith [[true, false]]
	 *           [[false, true]]
	 */
	public function test_wrapper_has_no_empty_class_when_one_filter_has_options( array $empty_states ): void {
		$output = $this->render_with_filter_states( $empty_states );

		$this->assertStringNotContainsString( 'wc-block-product-filters--empty', $output );
	}

	/**
	 * @testdox Empty state is scoped independently for identical and different Product Filters wrappers.
	 *
	 * @return void
	 */
	public function test_empty_state_is_scoped_per_wrapper(): void {
		$previous_states        = wp_interactivity_state( 'woocommerce/product-filters' )['filterEmptyStates'] ?? array();
		$empty_output           = $this->render_with_filter_states( array( true ) );
		$duplicate_empty_output = $this->render_with_filter_states( array( true ) );
		$visible_output         = $this->render_with_filter_states( array( false ) );

		$this->assertStringContainsString( 'wc-block-product-filters--empty', $empty_output );
		$this->assertStringContainsString( 'wc-block-product-filters--empty', $duplicate_empty_output );
		$this->assertStringNotContainsString( 'wc-block-product-filters--empty', $visible_output );

		$current_states = wp_interactivity_state( 'woocommerce/product-filters' )['filterEmptyStates'];
		$wrapper_states = array_diff_key( $current_states, $previous_states );
		$this->assertCount( 3, $wrapper_states );
		foreach ( $wrapper_states as $empty_states ) {
			$this->assertCount( 1, $empty_states );
		}
	}

	/**
	 * @testdox A wrapper with no participating filters is not marked empty.
	 *
	 * @return void
	 */
	public function test_wrapper_without_participating_filters_is_not_empty(): void {
		$output = $this->render_with_filter_states( array() );

		$this->assertStringNotContainsString( 'wc-block-product-filters--empty', $output );
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
