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

		$this->sut = new CoreBreadcrumbsCompatibility();

		$this->original_shop_page_id            = get_option( 'woocommerce_shop_page_id' );
		$this->original_myaccount_page_id       = get_option( 'woocommerce_myaccount_page_id' );
		$this->original_woocommerce_permalinks  = get_option( 'woocommerce_permalinks' );
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

		register_post_type(
			'wc_story',
			array(
				'labels'       => array(
					'name'          => 'Stories',
					'singular_name' => 'Story',
					'archives'      => 'Story Archives',
				),
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => true,
				'rewrite'      => array(
					'slug' => 'stories',
				),
			)
		);

		register_taxonomy(
			'wc_topic',
			array( 'wc_story' ),
			array(
				'labels'       => array(
					'name'          => 'Topics',
					'singular_name' => 'Topic',
				),
				'public'       => true,
				'show_in_rest' => true,
				'hierarchical' => true,
				'rewrite'      => array(
					'slug' => 'topic',
				),
			)
		);
	}

	/**
	 * Tear down test fixtures.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_filter( 'block_core_breadcrumbs_post_type_settings', array( $this->sut, 'set_product_breadcrumbs_preferred_taxonomy' ), 10 );
		remove_filter( 'block_core_breadcrumbs_items', array( $this->sut, 'apply_woocommerce_breadcrumb_filters' ), 10 );
		remove_filter( 'woocommerce_is_account_page', '__return_true' );

		update_option( 'woocommerce_shop_page_id', $this->original_shop_page_id );
		update_option( 'woocommerce_myaccount_page_id', $this->original_myaccount_page_id );
		update_option( 'woocommerce_permalinks', $this->original_woocommerce_permalinks );

		if ( post_type_exists( 'wc_story' ) ) {
			unregister_post_type( 'wc_story' );
		}

		if ( taxonomy_exists( 'wc_topic' ) ) {
			unregister_taxonomy( 'wc_topic' );
		}

		global $wp, $wp_query, $post;
		$wp       = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post     = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

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
	 * @testdox Should use the shop page title for product archive breadcrumbs.
	 */
	public function test_core_breadcrumbs_use_shop_page_title_for_product_archive_item(): void {
		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'All Products',
				'url'   => get_post_type_archive_link( 'product' ),
			),
			array(
				'label' => 'Logo Tee',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Catalog', 'Logo Tee' ), $this->get_breadcrumb_labels( $result ), 'Product archive breadcrumb should use the Shop page title.' );
		$this->assertSame(
			array(
				$this->get_home_breadcrumb_item(),
				array(
					'label' => 'Catalog',
					'url'   => get_post_type_archive_link( 'product' ),
				),
				array(
					'label' => 'Logo Tee',
				),
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

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Shirts',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Catalog', 'Shirts' ), $this->get_breadcrumb_labels( $result ), 'Product category breadcrumbs should include the Shop page crumb.' );
	}

	/**
	 * @testdox Should not duplicate the shop page when Core already includes it.
	 */
	public function test_core_breadcrumbs_do_not_duplicate_existing_shop_page_item(): void {
		$category_id = $this->create_term( 'Shirts', 'product_cat', array( 'slug' => 'shirts' ) );
		$this->go_to( get_term_link( $category_id, 'product_cat' ) );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Existing catalog',
				'url'   => untrailingslashit( get_post_type_archive_link( 'product' ) ),
			),
			array(
				'label' => 'Shirts',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame(
			array(
				$this->get_home_breadcrumb_item(),
				array(
					'label' => 'Catalog',
					'url'   => untrailingslashit( get_post_type_archive_link( 'product' ) ),
				),
				array(
					'label' => 'Shirts',
				),
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

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Shirts',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( $items, $result, 'Product category breadcrumbs should remain unchanged when WooCommerce would not prepend Shop.' );
	}

	/**
	 * @testdox Should prepend the shop page and use WooCommerce labels for product tag breadcrumbs.
	 */
	public function test_core_breadcrumbs_prepend_shop_page_and_label_product_tag_items(): void {
		$tag_id = $this->create_term( 'Sale', 'product_tag', array( 'slug' => 'sale' ) );
		$this->go_to( get_term_link( $tag_id, 'product_tag' ) );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Sale',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Catalog', 'Products tagged &ldquo;Sale&rdquo;' ), $this->get_breadcrumb_labels( $result ), 'Product tag breadcrumbs should match WooCommerce labels.' );
	}

	/**
	 * @testdox Should preserve pagination when labeling product tag breadcrumbs.
	 */
	public function test_core_breadcrumbs_label_paginated_product_tag_items(): void {
		$tag_id = $this->create_term( 'Sale', 'product_tag', array( 'slug' => 'sale' ) );
		$this->go_to( get_term_link( $tag_id, 'product_tag' ) );
		set_query_var( 'paged', 2 );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Sale',
				'url'   => get_term_link( $tag_id, 'product_tag' ),
			),
			array(
				'label' => 'Page 2',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Catalog', 'Products tagged &ldquo;Sale&rdquo;', 'Page 2' ), $this->get_breadcrumb_labels( $result ), 'Paginated product tag breadcrumbs should keep the pagination crumb.' );
	}

	/**
	 * @testdox Should prepend the shop page and use WooCommerce labels for product search breadcrumbs.
	 */
	public function test_core_breadcrumbs_prepend_shop_page_and_label_product_search_items(): void {
		$this->go_to( '/?s=hoodie&post_type=product' );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Search results for: "hoodie"',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Catalog', 'Search results for &ldquo;hoodie&rdquo;' ), $this->get_breadcrumb_labels( $result ), 'Product search breadcrumbs should include the Shop page crumb and WooCommerce search label.' );
	}

	/**
	 * @testdox Should preserve pagination when labeling product search breadcrumbs.
	 */
	public function test_core_breadcrumbs_label_paginated_product_search_items(): void {
		$this->go_to( '/?s=hoodie&post_type=product&paged=2' );
		set_query_var( 'paged', 2 );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Search results for: "hoodie"',
				'url'   => get_pagenum_link( 1 ),
			),
			array(
				'label' => 'Page 2',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Catalog', 'Search results for &ldquo;hoodie&rdquo;', 'Page 2' ), $this->get_breadcrumb_labels( $result ), 'Paginated product search breadcrumbs should keep the pagination crumb.' );
	}

	/**
	 * @testdox Should use WooCommerce labels for post tag breadcrumbs.
	 */
	public function test_core_breadcrumbs_label_post_tag_items(): void {
		$tag_id = $this->create_term( 'Breadcrumb Tag', 'post_tag', array( 'slug' => 'breadcrumb-tag' ) );
		$this->go_to( get_term_link( $tag_id, 'post_tag' ) );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Breadcrumb Tag',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Posts tagged &ldquo;Breadcrumb Tag&rdquo;' ), $this->get_breadcrumb_labels( $result ), 'Post tag breadcrumbs should match WooCommerce labels.' );
	}

	/**
	 * @testdox Should preserve pagination when labeling post tag breadcrumbs.
	 */
	public function test_core_breadcrumbs_label_paginated_post_tag_items(): void {
		$tag_id = $this->create_term( 'Breadcrumb Tag', 'post_tag', array( 'slug' => 'breadcrumb-tag' ) );
		$this->go_to( get_term_link( $tag_id, 'post_tag' ) );
		set_query_var( 'paged', 2 );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Breadcrumb Tag',
				'url'   => get_tag_link( $tag_id ),
			),
			array(
				'label' => 'Page 2',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Posts tagged &ldquo;Breadcrumb Tag&rdquo;', 'Page 2' ), $this->get_breadcrumb_labels( $result ), 'Paginated post tag breadcrumbs should keep the pagination crumb.' );
	}

	/**
	 * @testdox Should use WooCommerce labels for author breadcrumbs.
	 */
	public function test_core_breadcrumbs_label_author_items(): void {
		$user_id = self::factory()->user->create(
			array(
				'display_name' => 'Breadcrumb Author',
			)
		);
		self::factory()->post->create(
			array(
				'post_author' => $user_id,
			)
		);
		$this->go_to( get_author_posts_url( $user_id ) );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Breadcrumb Author',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Author: Breadcrumb Author' ), $this->get_breadcrumb_labels( $result ), 'Author breadcrumbs should match WooCommerce labels.' );
	}

	/**
	 * @testdox Should use WooCommerce labels for day archive breadcrumbs.
	 */
	public function test_core_breadcrumbs_label_day_archive_items(): void {
		self::factory()->post->create(
			array(
				'post_date' => '2026-05-08 10:00:00',
			)
		);
		$this->go_to( get_day_link( 2026, 5, 8 ) );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => '2026',
				'url'   => get_year_link( 2026 ),
			),
			array(
				'label' => 'May',
				'url'   => get_month_link( 2026, 5 ),
			),
			array(
				'label' => '8',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', '2026', 'May', '08' ), $this->get_breadcrumb_labels( $result ), 'Day archive breadcrumbs should use WooCommerce zero-padded day labels.' );
	}

	/**
	 * @testdox Should use WooCommerce labels for custom post type archive breadcrumbs.
	 */
	public function test_core_breadcrumbs_label_custom_post_type_archive_items(): void {
		$this->go_to( get_post_type_archive_link( 'wc_story' ) );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Story Archives',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Stories' ), $this->get_breadcrumb_labels( $result ), 'Custom post type archive breadcrumbs should use the WooCommerce archive label.' );
	}

	/**
	 * @testdox Should use WooCommerce trail for custom post type single breadcrumbs.
	 */
	public function test_core_breadcrumbs_replace_custom_post_type_single_items(): void {
		$parent_topic_id = $this->create_term( 'Topic Parent', 'wc_topic', array( 'slug' => 'topic-parent' ) );
		$child_topic_id  = $this->create_term(
			'Topic Child',
			'wc_topic',
			array(
				'slug'   => 'topic-child',
				'parent' => $parent_topic_id,
			)
		);
		$post_id         = self::factory()->post->create(
			array(
				'post_type'  => 'wc_story',
				'post_title' => 'Breadcrumb Story',
				'post_name'  => 'breadcrumb-story',
			)
		);
		wp_set_post_terms( $post_id, array( $child_topic_id ), 'wc_topic' );
		$this->go_to( get_permalink( $post_id ) );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Story Archives',
				'url'   => get_post_type_archive_link( 'wc_story' ),
			),
			array(
				'label' => 'Topic Parent',
				'url'   => get_term_link( $parent_topic_id, 'wc_topic' ),
			),
			array(
				'label' => 'Topic Child',
				'url'   => get_term_link( $child_topic_id, 'wc_topic' ),
			),
			array(
				'label' => 'Breadcrumb Story',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Story', 'Breadcrumb Story' ), $this->get_breadcrumb_labels( $result ), 'Custom post type single breadcrumbs should use the WooCommerce trail.' );
	}

	/**
	 * @testdox Should prepend taxonomy labels for custom taxonomy breadcrumbs.
	 */
	public function test_core_breadcrumbs_prepend_taxonomy_label_to_custom_taxonomy_items(): void {
		$parent_topic_id = $this->create_term( 'Topic Parent', 'wc_topic', array( 'slug' => 'topic-parent' ) );
		$child_topic_id  = $this->create_term(
			'Topic Child',
			'wc_topic',
			array(
				'slug'   => 'topic-child',
				'parent' => $parent_topic_id,
			)
		);
		$this->go_to( get_term_link( $child_topic_id, 'wc_topic' ) );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Topic Parent',
				'url'   => get_term_link( $parent_topic_id, 'wc_topic' ),
			),
			array(
				'label' => 'Topic Child',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Topics', 'Topic Parent', 'Topic Child' ), $this->get_breadcrumb_labels( $result ), 'Custom taxonomy breadcrumbs should include the taxonomy label crumb.' );
	}

	/**
	 * @testdox Should prepend the My Account page to endpoint breadcrumbs.
	 */
	public function test_core_breadcrumbs_prepend_my_account_page_to_endpoint_items(): void {
		$this->set_account_endpoint_request( 'orders' );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Orders',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'My account', 'Orders' ), $this->get_breadcrumb_labels( $result ), 'My Account endpoint breadcrumbs should include the account page crumb.' );
	}

	/**
	 * @testdox Should not duplicate the My Account page on endpoint breadcrumbs.
	 */
	public function test_core_breadcrumbs_do_not_duplicate_existing_my_account_page_item(): void {
		$this->set_account_endpoint_request( 'orders' );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'My account',
				'url'   => get_permalink( $this->my_account_page_id ),
			),
			array(
				'label' => 'Orders',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( $items, $result, 'My Account endpoint breadcrumbs should reuse the existing My Account crumb.' );
	}

	/**
	 * @testdox Should use the WooCommerce 404 breadcrumb label.
	 */
	public function test_core_breadcrumbs_use_woocommerce_404_label(): void {
		$this->go_to( '/not-a-real-page/' );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'Page not found',
			),
		);

		$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );

		$this->assertSame( array( 'Home', 'Error 404' ), $this->get_breadcrumb_labels( $result ), '404 breadcrumbs should match WooCommerce labels.' );
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

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label' => 'All Products',
				'url'   => get_post_type_archive_link( 'product' ),
			),
			array(
				'label' => 'Logo Tee',
			),
		);

		try {
			$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );
		} finally {
			remove_filter( 'woocommerce_get_breadcrumb', $callback );
		}

		$this->assertSame( array( 'Home', 'Filtered Catalog', 'Logo Tee' ), $this->get_breadcrumb_labels( $result ), 'Legacy breadcrumb filters should receive WooCommerce-adjusted Core breadcrumb items.' );
	}

	/**
	 * @testdox Should preserve Core breadcrumb item metadata when legacy filters run.
	 */
	public function test_core_breadcrumbs_preserve_core_item_metadata_when_legacy_filters_run(): void {
		$callback = function ( $crumbs ) {
			return $crumbs;
		};
		add_filter( 'woocommerce_get_breadcrumb', $callback );

		$items = array(
			$this->get_home_breadcrumb_item(),
			array(
				'label'      => '<span>Logo Tee</span>',
				'allow_html' => true,
			),
		);

		try {
			$result = $this->sut->apply_woocommerce_breadcrumb_filters( $items );
		} finally {
			remove_filter( 'woocommerce_get_breadcrumb', $callback );
		}

		$this->assertSame( $items, $result, 'Core breadcrumb item metadata should survive WooCommerce breadcrumb filter conversion.' );
	}

	/**
	 * Get the Home breadcrumb item.
	 *
	 * @return array Home breadcrumb item.
	 */
	private function get_home_breadcrumb_item(): array {
		return array(
			'label' => 'Home',
			'url'   => home_url( '/' ),
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
	 * Set the current request as a My Account endpoint.
	 *
	 * @param string $endpoint Endpoint name.
	 */
	private function set_account_endpoint_request( string $endpoint ): void {
		global $wp;

		$wp             = new \stdClass(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp->query_vars = array( $endpoint => '' );

		add_filter( 'woocommerce_is_account_page', '__return_true' );
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
