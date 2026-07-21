<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\CoreBreadcrumbsCompatibility;
use Automattic\WooCommerce\Blocks\Domain\Bootstrap;
use Automattic\WooCommerce\Blocks\Domain\Package as BlocksPackage;
use Automattic\WooCommerce\Blocks\Registry\Container;
use WC_Unit_Test_Case;

/**
 * Unit tests for the CoreBreadcrumbsCompatibility class.
 */
class CoreBreadcrumbsCompatibilityTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CoreBreadcrumbsCompatibility
	 */
	private CoreBreadcrumbsCompatibility $sut;

	/**
	 * Original shop page ID.
	 *
	 * @var mixed
	 */
	private $original_shop_page_id;

	/**
	 * Original My Account page ID.
	 *
	 * @var mixed
	 */
	private $original_myaccount_page_id;

	/**
	 * Original WooCommerce permalinks.
	 *
	 * @var mixed
	 */
	private $original_woocommerce_permalinks;

	/**
	 * Original product post type archive setting.
	 *
	 * @var mixed
	 */
	private $original_product_has_archive;

	/**
	 * Original WooCommerce query object.
	 *
	 * @var \WC_Query
	 */
	private \WC_Query $original_woocommerce_query;

	/**
	 * Original request query vars.
	 *
	 * @var array
	 */
	private $original_wp_query_vars;

	/**
	 * Shop page ID.
	 *
	 * @var int
	 */
	private $shop_page_id;

	/**
	 * My Account page ID.
	 *
	 * @var int
	 */
	private $my_account_page_id;

	/**
	 * Sets up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp;

		$this->sut = new CoreBreadcrumbsCompatibility();

		$product_post_type = get_post_type_object( 'product' );

		$this->original_shop_page_id            = get_option( 'woocommerce_shop_page_id' );
		$this->original_myaccount_page_id       = get_option( 'woocommerce_myaccount_page_id' );
		$this->original_woocommerce_permalinks  = get_option( 'woocommerce_permalinks' );
		$this->original_product_has_archive     = $product_post_type ? $product_post_type->has_archive : null;
		$this->original_woocommerce_query       = WC()->query;
		$this->original_wp_query_vars           = $wp instanceof \WP && is_array( $wp->query_vars ) ? $wp->query_vars : array();
		$this->shop_page_id                     = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Catalog',
				'post_name'   => 'shop',
			)
		);
		$this->my_account_page_id               = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'My account',
				'post_name'   => 'my-account',
			)
		);
		$woocommerce_permalinks                 = is_array( $this->original_woocommerce_permalinks ) ? $this->original_woocommerce_permalinks : array();
		$woocommerce_permalinks['product_base'] = '/shop';

		update_option( 'woocommerce_shop_page_id', $this->shop_page_id );
		update_option( 'woocommerce_myaccount_page_id', $this->my_account_page_id );
		update_option( 'woocommerce_permalinks', $woocommerce_permalinks );

		if ( $product_post_type ) {
			$product_post_type->has_archive = get_page_uri( $this->shop_page_id );
		}
	}

	/**
	 * Tear down test fixtures.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wp;

		remove_filter( 'block_core_breadcrumbs_post_type_settings', array( $this->sut, 'set_product_breadcrumbs_preferred_taxonomy' ), 10 );
		remove_filter( 'block_core_breadcrumbs_items', array( $this->sut, 'apply_woocommerce_breadcrumb_filters' ), 10 );
		remove_filter( 'woocommerce_is_account_page', '__return_true' );

		update_option( 'woocommerce_shop_page_id', $this->original_shop_page_id );
		update_option( 'woocommerce_myaccount_page_id', $this->original_myaccount_page_id );
		update_option( 'woocommerce_permalinks', $this->original_woocommerce_permalinks );

		$product_post_type = get_post_type_object( 'product' );
		if ( $product_post_type ) {
			$product_post_type->has_archive = $this->original_product_has_archive;
		}

		if ( $wp instanceof \WP ) {
			$wp->query_vars = $this->original_wp_query_vars;
		}

		WC()->query = $this->original_woocommerce_query;

		if ( taxonomy_exists( 'pa_test_color' ) ) {
			unregister_taxonomy( 'pa_test_color' );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should register Core Breadcrumbs compatibility filters.
	 */
	public function test_init_registers_core_breadcrumbs_filters(): void {
		$this->sut->init();
		$this->sut->init();

		$this->assertSame( 10, has_filter( 'block_core_breadcrumbs_post_type_settings', array( $this->sut, 'set_product_breadcrumbs_preferred_taxonomy' ) ), 'Product breadcrumb settings filter should be registered.' );
		$this->assertSame( 10, has_filter( 'block_core_breadcrumbs_items', array( $this->sut, 'apply_woocommerce_breadcrumb_filters' ) ), 'Breadcrumb items filter should be registered.' );
	}

	/**
	 * @testdox Should register Core Breadcrumbs compatibility filters through the Blocks bootstrap.
	 */
	public function test_blocks_bootstrap_registers_core_breadcrumbs_filters(): void {
		$container = new Container();
		$container->register(
			BlocksPackage::class,
			function () {
				return new BlocksPackage( 'test', dirname( WC_PLUGIN_FILE ) );
			}
		);

		new Bootstrap( $container );
		$compatibility = $container->get( CoreBreadcrumbsCompatibility::class );

		try {
			$this->assertSame( 10, has_filter( 'block_core_breadcrumbs_post_type_settings', array( $compatibility, 'set_product_breadcrumbs_preferred_taxonomy' ) ), 'Bootstrap should register the product breadcrumb settings filter.' );
			$this->assertSame( 10, has_filter( 'block_core_breadcrumbs_items', array( $compatibility, 'apply_woocommerce_breadcrumb_filters' ) ), 'Bootstrap should register the breadcrumb items filter.' );
		} finally {
			remove_filter( 'block_core_breadcrumbs_post_type_settings', array( $compatibility, 'set_product_breadcrumbs_preferred_taxonomy' ), 10 );
			remove_filter( 'block_core_breadcrumbs_items', array( $compatibility, 'apply_woocommerce_breadcrumb_filters' ), 10 );
		}
	}

	/**
	 * @testdox Should set product category as the preferred product breadcrumb taxonomy.
	 */
	public function test_sets_product_breadcrumbs_preferred_taxonomy(): void {
		$category_id = $this->create_term( 'Shirts', 'product_cat', array( 'slug' => 'shirts' ) );
		$product_id  = self::factory()->post->create(
			array(
				'post_type' => 'product',
			)
		);
		wp_set_post_terms( $product_id, array( $category_id ), 'product_cat' );

		$result = $this->sut->set_product_breadcrumbs_preferred_taxonomy( array(), 'product', $product_id );

		$this->assertSame( 'product_cat', $result['taxonomy'], 'Products should prefer product categories.' );
		$this->assertSame( 'shirts', $result['term'], 'Products should prefer WooCommerce-selected product category terms.' );
	}

	/**
	 * @testdox Should apply the WooCommerce Home breadcrumb URL filter.
	 */
	public function test_core_breadcrumbs_apply_home_breadcrumb_url_filter(): void {
		$callback = function () {
			return home_url( '/storefront/' );
		};
		add_filter( 'woocommerce_breadcrumb_home_url', $callback );
		$this->go_to( $this->get_product_archive_url() );
		$this->set_product_archive_request();

		try {
			$result = $this->apply_core_breadcrumb_filters(
				$this->get_breadcrumb_item( 'Current page' )
			);
		} finally {
			remove_filter( 'woocommerce_breadcrumb_home_url', $callback );
		}

		$this->assertSame(
			array(
				$this->get_home_breadcrumb_item( home_url( '/storefront/' ) ),
				$this->get_breadcrumb_item( 'Current page' ),
			),
			$result,
			'Core Breadcrumbs should use the filtered WooCommerce Home breadcrumb URL.'
		);
	}

	/**
	 * @testdox Should apply the WooCommerce Home breadcrumb URL filter outside WooCommerce contexts.
	 */
	public function test_core_breadcrumbs_apply_home_breadcrumb_url_filter_outside_woocommerce_contexts(): void {
		$callback = function () {
			return home_url( '/storefront/' );
		};
		add_filter( 'woocommerce_breadcrumb_home_url', $callback );
		$this->go_to( '/?s=breadcrumb' );

		try {
			$result = $this->apply_core_breadcrumb_filters(
				$this->get_breadcrumb_item( 'Search results for: "breadcrumb"' )
			);
		} finally {
			remove_filter( 'woocommerce_breadcrumb_home_url', $callback );
		}

		$this->assertSame(
			home_url( '/storefront/' ),
			$result[0]['url'],
			'Core Breadcrumbs should use the filtered WooCommerce Home breadcrumb URL outside WooCommerce contexts.'
		);
	}

	/**
	 * @testdox Should use the default Home breadcrumb URL when the WooCommerce Home URL filter returns a non-string.
	 */
	public function test_core_breadcrumbs_use_default_home_breadcrumb_url_for_non_string_filter_value(): void {
		$callback = function () {
			return array();
		};
		add_filter( 'woocommerce_breadcrumb_home_url', $callback );
		$this->go_to( $this->get_product_archive_url() );
		$this->set_product_archive_request();

		try {
			$result = $this->apply_core_breadcrumb_filters(
				$this->get_breadcrumb_item( 'Current page' )
			);
		} finally {
			remove_filter( 'woocommerce_breadcrumb_home_url', $callback );
		}

		$this->assertSame(
			home_url(),
			$result[0]['url'],
			'Core Breadcrumbs should fall back to the default WooCommerce Home breadcrumb URL.'
		);
	}

	/**
	 * @testdox Should use the shop page title for product archive breadcrumbs.
	 */
	public function test_core_breadcrumbs_use_shop_page_title_for_product_archive_item(): void {
		$product_item = $this->get_breadcrumb_item( 'Logo Tee' );
		$this->go_to( $this->get_product_archive_url() );
		$this->set_product_archive_request();

		$result = $this->apply_core_breadcrumb_filters(
			$this->get_breadcrumb_item( 'All Products', $this->get_product_archive_url() ),
			$product_item
		);

		$this->assertSame(
			array( 'Home', 'Catalog', 'Logo Tee' ),
			$this->get_breadcrumb_labels( $result ),
			'Product archive breadcrumb should use the Shop page title.'
		);
		$this->assertSame(
			$this->get_core_breadcrumb_items(
				$this->get_breadcrumb_item( 'Catalog', $this->get_product_archive_url() ),
				$product_item
			),
			$result,
			'Product archive breadcrumb should preserve Core item shape while updating the label.'
		);
	}

	/**
	 * @testdox Should prepend the shop page to product category breadcrumbs.
	 */
	public function test_core_breadcrumbs_prepend_shop_page_to_product_category_items(): void {
		$category_id = $this->create_term( 'Shirts', 'product_cat', array( 'slug' => 'shirts' ) );
		$this->go_to( get_term_link( $category_id, 'product_cat' ) );

		$this->assert_core_breadcrumb_labels(
			array( 'Home', 'Catalog', 'Shirts' ),
			'Product category breadcrumbs should include the Shop page crumb.',
			$this->get_breadcrumb_item( 'Shirts' )
		);
	}

	/**
	 * @testdox Should not duplicate the shop page when Core already includes it.
	 */
	public function test_core_breadcrumbs_do_not_duplicate_existing_shop_page_item(): void {
		$category_id = $this->create_term( 'Shirts', 'product_cat', array( 'slug' => 'shirts' ) );
		$this->go_to( get_term_link( $category_id, 'product_cat' ) );

		$shop_url   = untrailingslashit( $this->get_shop_page_url() );
		$shirt_item = $this->get_breadcrumb_item( 'Shirts' );
		$result     = $this->apply_core_breadcrumb_filters(
			$this->get_breadcrumb_item( 'Existing catalog', $shop_url ),
			$shirt_item
		);

		$this->assertSame(
			$this->get_core_breadcrumb_items(
				$this->get_breadcrumb_item( 'Catalog', $shop_url ),
				$shirt_item
			),
			$result,
			'Product category breadcrumbs should reuse the existing Shop crumb instead of inserting another one.'
		);
	}

	/**
	 * @testdox Should not prepend the shop page when product permalinks do not include the shop slug.
	 */
	public function test_core_breadcrumbs_do_not_prepend_shop_page_for_product_base_without_shop_slug(): void {
		$woocommerce_permalinks                 = is_array( $this->original_woocommerce_permalinks ) ? $this->original_woocommerce_permalinks : array();
		$woocommerce_permalinks['product_base'] = '/product';
		update_option( 'woocommerce_permalinks', $woocommerce_permalinks );

		$category_id = $this->create_term( 'Shirts', 'product_cat', array( 'slug' => 'shirts' ) );
		$this->go_to( get_term_link( $category_id, 'product_cat' ) );

		$this->assert_core_breadcrumbs_unchanged(
			'Product category breadcrumbs should remain unchanged when WooCommerce would not prepend Shop.',
			$this->get_breadcrumb_item( 'Shirts' )
		);
	}

	/**
	 * @testdox Should preserve pagination when labeling product tag breadcrumbs.
	 */
	public function test_core_breadcrumbs_label_paginated_product_tag_items(): void {
		$tag_id = $this->create_term( 'Sale', 'product_tag', array( 'slug' => 'sale' ) );
		$this->go_to( get_term_link( $tag_id, 'product_tag' ) );
		set_query_var( 'paged', 2 );

		$this->assert_core_breadcrumb_labels(
			array( 'Home', 'Catalog', 'Products tagged &ldquo;Sale&rdquo;', 'Page 2' ),
			'Paginated product tag breadcrumbs should keep the pagination crumb.',
			$this->get_breadcrumb_item( 'Sale', get_term_link( $tag_id, 'product_tag' ) ),
			$this->get_breadcrumb_item( 'Page 2' )
		);
	}

	/**
	 * @testdox Should preserve pagination when labeling product search breadcrumbs.
	 */
	public function test_core_breadcrumbs_label_paginated_product_search_items(): void {
		global $wp_query;

		$this->go_to( '/?s=hoodie&post_type=product' );

		$wp_query->is_search            = true;
		$wp_query->is_post_type_archive = true;
		$wp_query->is_archive           = true;
		$wp_query->is_404               = false;

		set_query_var( 'paged', 2 );

		$this->assert_core_breadcrumb_labels(
			array( 'Home', 'Catalog', 'Search results for &ldquo;hoodie&rdquo;', 'Page 2' ),
			'Paginated product search breadcrumbs should keep the pagination crumb.',
			$this->get_breadcrumb_item( 'Search results for: "hoodie"', get_pagenum_link( 1 ) ),
			$this->get_breadcrumb_item( 'Page 2' )
		);
	}

	/**
	 * @testdox Should preserve the Shop breadcrumb when the current product search item is hidden.
	 */
	public function test_core_breadcrumbs_preserve_shop_item_when_product_search_current_item_is_hidden(): void {
		global $wp_query;

		$this->go_to( '/?s=hoodie&post_type=product' );

		$wp_query->is_search            = true;
		$wp_query->is_post_type_archive = true;
		$wp_query->is_archive           = true;
		$wp_query->is_404               = false;

		$result = $this->apply_core_breadcrumb_filters();

		$this->assertSame(
			array( 'Home', 'Catalog' ),
			$this->get_breadcrumb_labels( $result ),
			'The Shop crumb should not be relabeled as the current search when Core omits that item.'
		);
	}

	/**
	 * @testdox Should preserve Core's hidden Home setting on product searches.
	 */
	public function test_core_breadcrumbs_preserve_hidden_home_item_on_product_search(): void {
		global $wp_query;

		$this->go_to( '/?s=hoodie&post_type=product' );

		$wp_query->is_search            = true;
		$wp_query->is_post_type_archive = true;
		$wp_query->is_archive           = true;
		$wp_query->is_404               = false;

		$result = $this->apply_breadcrumb_filters(
			array( $this->get_breadcrumb_item( 'Search results for: "hoodie"' ) )
		);

		$this->assertSame(
			array( 'Catalog', 'Search results for &ldquo;hoodie&rdquo;' ),
			$this->get_breadcrumb_labels( $result ),
			'The Home crumb should remain hidden when WooCommerce compatibility adds the Shop crumb.'
		);
	}

	/**
	 * @testdox Should preserve Core's hidden Home and current item settings on product searches.
	 */
	public function test_core_breadcrumbs_preserve_hidden_home_and_current_items_on_product_search(): void {
		global $wp_query;

		$this->go_to( '/?s=hoodie&post_type=product' );

		$wp_query->is_search            = true;
		$wp_query->is_post_type_archive = true;
		$wp_query->is_archive           = true;
		$wp_query->is_404               = false;

		$result = $this->apply_breadcrumb_filters( array() );

		$this->assertSame(
			array( 'Catalog' ),
			$this->get_breadcrumb_labels( $result ),
			'Home and current crumbs should remain hidden while WooCommerce compatibility keeps the Shop parent.'
		);
	}

	/**
	 * @testdox Should use WooCommerce labels for regular search breadcrumbs.
	 */
	public function test_core_breadcrumbs_label_regular_search_items(): void {
		$this->go_to( '/?s=breadcrumb' );

		$this->assert_core_breadcrumb_labels(
			array( 'Home', 'Search results for &ldquo;breadcrumb&rdquo;' ),
			'Regular search breadcrumbs should use the WooCommerce search label.',
			$this->get_breadcrumb_item( 'Search results for: "breadcrumb"' )
		);
	}

	/**
	 * @testdox Should prepend taxonomy labels for product attribute breadcrumbs.
	 */
	public function test_core_breadcrumbs_prepend_taxonomy_label_to_product_attribute_items(): void {
		register_taxonomy(
			'pa_test_color',
			array( 'product' ),
			array(
				'labels'       => array(
					'name'          => 'Colors',
					'singular_name' => 'Color',
				),
				'public'       => true,
				'show_in_rest' => true,
				'hierarchical' => true,
			)
		);

		$term_id = $this->create_term( 'Blue', 'pa_test_color', array( 'slug' => 'blue' ) );
		$this->go_to( get_term_link( $term_id, 'pa_test_color' ) );
		$this->set_product_taxonomy_request( $term_id, 'pa_test_color' );

		$this->assert_core_breadcrumb_labels(
			array( 'Home', 'Colors', 'Blue' ),
			'Product attribute breadcrumbs should include the attribute taxonomy label crumb.',
			$this->get_breadcrumb_item( 'Blue' )
		);
	}

	/**
	 * @testdox Should prepend the My Account page to endpoint breadcrumbs.
	 */
	public function test_core_breadcrumbs_prepend_my_account_page_to_endpoint_items(): void {
		$this->set_account_endpoint_request( 'orders' );

		$this->assert_core_breadcrumb_labels(
			array( 'Home', 'My account', 'Orders' ),
			'My Account endpoint breadcrumbs should include the account page crumb.',
			$this->get_breadcrumb_item( 'Orders' )
		);
	}

	/**
	 * @testdox Should replace the account page title with the current endpoint title.
	 */
	public function test_core_breadcrumbs_replace_account_page_title_with_endpoint_title(): void {
		$this->set_account_endpoint_request( 'orders' );

		$this->assert_core_breadcrumb_labels(
			array( 'Home', 'My account', 'Orders' ),
			'My Account endpoint breadcrumbs should use the endpoint title for the current item.',
			$this->get_breadcrumb_item( 'My account' )
		);
	}

	/**
	 * @testdox Should not duplicate the My Account page on endpoint breadcrumbs.
	 */
	public function test_core_breadcrumbs_do_not_duplicate_existing_my_account_page_item(): void {
		$this->set_account_endpoint_request( 'orders' );

		$this->assert_core_breadcrumbs_unchanged(
			'My Account endpoint breadcrumbs should reuse the existing My Account crumb.',
			$this->get_breadcrumb_item( 'My account', get_permalink( $this->my_account_page_id ) ),
			$this->get_breadcrumb_item( 'Orders' )
		);
	}

	/**
	 * @testdox Should run the legacy WooCommerce breadcrumb filter after adjustments.
	 */
	public function test_core_breadcrumbs_apply_legacy_woocommerce_get_breadcrumb_filter_after_adjustments(): void {
		$callback = function ( $crumbs ) {
			$crumbs[1][0] = 'Filtered Catalog';
			return $crumbs;
		};
		add_filter( 'woocommerce_get_breadcrumb', $callback );
		$this->go_to( $this->get_product_archive_url() );
		$this->set_product_archive_request();

		try {
			$result = $this->apply_core_breadcrumb_filters(
				$this->get_breadcrumb_item( 'All Products', $this->get_product_archive_url() ),
				$this->get_breadcrumb_item( 'Logo Tee' )
			);
		} finally {
			remove_filter( 'woocommerce_get_breadcrumb', $callback );
		}

		$this->assertSame(
			array( 'Home', 'Filtered Catalog', 'Logo Tee' ),
			$this->get_breadcrumb_labels( $result ),
			'Legacy breadcrumb filters should receive WooCommerce-adjusted Core breadcrumb items.'
		);
	}

	/**
	 * @testdox Should apply legacy WooCommerce breadcrumb filters outside WooCommerce contexts.
	 */
	public function test_core_breadcrumbs_apply_legacy_woocommerce_get_breadcrumb_filter_outside_woocommerce_contexts(): void {
		$callback = function ( $crumbs ) {
			$crumbs[1][0] = 'Filtered page';
			return $crumbs;
		};
		add_filter( 'woocommerce_get_breadcrumb', $callback );
		$this->go_to( '/?s=breadcrumb' );

		try {
			$result = $this->apply_core_breadcrumb_filters(
				$this->get_breadcrumb_item( 'Search results for: "breadcrumb"' )
			);
		} finally {
			remove_filter( 'woocommerce_get_breadcrumb', $callback );
		}

		$this->assertSame(
			array( 'Home', 'Filtered page' ),
			$this->get_breadcrumb_labels( $result ),
			'Legacy WooCommerce breadcrumb filters should run for regular Core breadcrumbs.'
		);
	}

	/**
	 * @testdox Should preserve Core breadcrumb item metadata when legacy filters run.
	 */
	public function test_core_breadcrumbs_preserve_core_item_metadata_when_legacy_filters_run(): void {
		$callback = function ( $crumbs ) {
			return $crumbs;
		};
		add_filter( 'woocommerce_get_breadcrumb', $callback );
		$this->go_to( $this->get_product_archive_url() );
		$this->set_product_archive_request();

		$product_item = $this->get_breadcrumb_item(
			'<span>Logo Tee</span>',
			null,
			array(
				'allow_html' => true,
			)
		);

		try {
			$result = $this->apply_core_breadcrumb_filters( $product_item );
		} finally {
			remove_filter( 'woocommerce_get_breadcrumb', $callback );
		}

		$this->assertSame(
			$this->get_core_breadcrumb_items( $product_item ),
			$result,
			'Core breadcrumb item metadata should survive WooCommerce breadcrumb filter conversion.'
		);
	}

	/**
	 * Get the Home breadcrumb item.
	 *
	 * @param string|null $url Home URL.
	 * @return array Home breadcrumb item.
	 */
	private function get_home_breadcrumb_item( ?string $url = null ): array {
		return $this->get_breadcrumb_item( 'Home', $url ?? home_url( '/' ) );
	}

	/**
	 * Get a breadcrumb item.
	 *
	 * @param string      $label Breadcrumb label.
	 * @param string|null $url Breadcrumb URL.
	 * @param array       $metadata Extra breadcrumb metadata.
	 * @return array Breadcrumb item.
	 */
	private function get_breadcrumb_item( string $label, ?string $url = null, array $metadata = array() ): array {
		$item = array_merge(
			array(
				'label' => $label,
			),
			$metadata
		);

		if ( null !== $url ) {
			$item['url'] = $url;
		}

		return $item;
	}

	/**
	 * Get Core breadcrumb items with a Home item.
	 *
	 * @param array ...$items Breadcrumb items after Home.
	 * @return array Breadcrumb items.
	 */
	private function get_core_breadcrumb_items( array ...$items ): array {
		array_unshift( $items, $this->get_home_breadcrumb_item() );

		return $items;
	}

	/**
	 * Apply WooCommerce breadcrumb compatibility filters to Core breadcrumb items.
	 *
	 * @param array ...$items Breadcrumb items after Home.
	 * @return array Filtered breadcrumb items.
	 */
	private function apply_core_breadcrumb_filters( array ...$items ): array {
		return $this->apply_breadcrumb_filters( $this->get_core_breadcrumb_items( ...$items ) );
	}

	/**
	 * Apply WooCommerce breadcrumb compatibility filters.
	 *
	 * @param array $items Breadcrumb items.
	 * @return array Filtered breadcrumb items.
	 */
	private function apply_breadcrumb_filters( array $items ): array {
		return $this->sut->apply_woocommerce_breadcrumb_filters( $items );
	}

	/**
	 * Assert breadcrumb labels after applying filters to Core breadcrumb items.
	 *
	 * @param array  $expected_labels Expected breadcrumb labels.
	 * @param string $message Assertion message.
	 * @param array  ...$items Breadcrumb items after Home.
	 */
	private function assert_core_breadcrumb_labels( array $expected_labels, string $message, array ...$items ): void {
		$this->assertSame(
			$expected_labels,
			$this->get_breadcrumb_labels( $this->apply_core_breadcrumb_filters( ...$items ) ),
			$message
		);
	}

	/**
	 * Assert Core breadcrumb items are unchanged after applying filters.
	 *
	 * @param string $message Assertion message.
	 * @param array  ...$items Breadcrumb items after Home.
	 */
	private function assert_core_breadcrumbs_unchanged( string $message, array ...$items ): void {
		$core_items = $this->get_core_breadcrumb_items( ...$items );

		$this->assertSame(
			$core_items,
			$this->apply_breadcrumb_filters( $core_items ),
			$message
		);
	}

	/**
	 * Get labels from breadcrumb items.
	 *
	 * @param array $items Breadcrumb items.
	 * @return array Breadcrumb labels.
	 */
	private function get_breadcrumb_labels( array $items ): array {
		return array_column( $items, 'label' );
	}

	/**
	 * Get the product archive URL.
	 *
	 * @return string Product archive URL.
	 */
	private function get_product_archive_url(): string {
		$url = get_post_type_archive_link( 'product' );

		if ( ! is_string( $url ) ) {
			$this->fail( 'Expected the product archive link to be available.' );
		}

		return $url;
	}

	/**
	 * Get the Shop page URL.
	 *
	 * @return string Shop page URL.
	 */
	private function get_shop_page_url(): string {
		$url = get_permalink( $this->shop_page_id );

		if ( ! is_string( $url ) ) {
			$this->fail( 'Expected the Shop page permalink to be available.' );
		}

		return $url;
	}

	/**
	 * Set the current request as a My Account endpoint.
	 *
	 * @param string $endpoint Endpoint name.
	 */
	private function set_account_endpoint_request( string $endpoint ): void {
		global $wp, $wp_query;

		WC()->query = new \WC_Query();

		if ( ! $wp instanceof \WP ) {
			$wp = new \WP(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( ! $wp_query instanceof \WP_Query ) {
			$wp_query = new \WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( ! is_array( $wp->query_vars ) ) {
			$wp->query_vars = array();
		}

		$wp->query_vars[ $endpoint ] = '';

		add_filter( 'woocommerce_is_account_page', '__return_true' );
	}

	/**
	 * Set the current request as the product archive.
	 */
	private function set_product_archive_request(): void {
		global $wp_query;

		$wp_query->is_post_type_archive = true;
		$wp_query->is_archive           = true;
		$wp_query->is_404               = false;

		set_query_var( 'post_type', 'product' );
	}

	/**
	 * Set the current request as a product taxonomy archive.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $taxonomy Taxonomy name.
	 */
	private function set_product_taxonomy_request( int $term_id, string $taxonomy ): void {
		global $wp_query;

		$term = get_term( $term_id, $taxonomy );

		if ( ! $term instanceof \WP_Term ) {
			$this->fail( 'Expected product taxonomy term to be available.' );
		}

		$wp_query->queried_object    = $term;
		$wp_query->queried_object_id = $term_id;
		$wp_query->is_tax            = true;
		$wp_query->is_archive        = true;
		$wp_query->is_404            = false;

		set_query_var( 'taxonomy', $taxonomy );
		set_query_var( 'term', $term->slug );
	}

	/**
	 * Create a taxonomy term.
	 *
	 * @param string $name Term name.
	 * @param string $taxonomy Taxonomy name.
	 * @param array  $args Term arguments.
	 * @return int Term ID.
	 */
	private function create_term( string $name, string $taxonomy, array $args = array() ): int {
		$term = wp_insert_term( $name, $taxonomy, $args );

		if ( is_wp_error( $term ) ) {
			$this->fail( $term->get_error_message() );
		}

		return (int) $term['term_id'];
	}
}
