<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Controller;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Collection block controller.
 */
class ControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Controller
	 */
	private $sut;

	/**
	 * Template post created by a test, removed on tear down.
	 *
	 * @var int
	 */
	private $created_template_id = 0;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$asset_api            = Package::container()->get( Api::class );
		$asset_data_registry  = new AssetDataRegistryMock( $asset_api );
		$integration_registry = new IntegrationRegistry();

		$this->sut = new class( $asset_api, $asset_data_registry, $integration_registry ) extends Controller {
			/**
			 * Skip normal hook registration for unit tests.
			 */
			protected function initialize() {
				$this->renderer = new class() {
					/**
					 * Accept parsed block data from the controller under test.
					 *
					 * @param array $parsed_block The parsed block.
					 */
					public function set_parsed_block( array $parsed_block ): void {}
				};
			}
		};

		wp_interactivity_config( 'woocommerce/product-filters', array( 'forcePageReload' => false ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_interactivity_config( 'woocommerce/product-filters', array( 'forcePageReload' => false ) );

		parent::tearDown();
	}

	/**
	 * @testdox Should configure product filters full page reload for inherited product collections.
	 */
	public function test_configures_product_filters_full_page_reload_for_inherited_product_collections(): void {
		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['forcePageReload']  = true;
		$parsed_block['attrs']['query']['inherit'] = true;

		$this->sut->add_support_for_filter_blocks( null, $parsed_block );

		$config = wp_interactivity_config( 'woocommerce/product-filters' );

		$this->assertTrue(
			$config['forcePageReload'] ?? false,
			'Product Filters should be configured to reload when the inherited Product Collection forces page reload.'
		);
	}

	/**
	 * @testdox Should leave the store's products per page alone outside a block-theme product archive.
	 */
	public function test_loop_shop_per_page_passes_through_outside_block_theme_archives(): void {
		$original_theme = get_stylesheet();
		switch_theme( 'storefront' );
		$this->assertFalse( wp_is_block_theme(), 'The test must run with a classic theme.' );

		try {
			$this->assertSame(
				16,
				$this->sut->handle_loop_shop_per_page( 16 ),
				'A classic theme has no block template to size the archive, so the store setting stands.'
			);
		} finally {
			switch_theme( $original_theme );
		}
	}

	/**
	 * @testdox Should size the archive main query from the template's inherited Product Collection.
	 */
	public function test_archive_template_sizes_the_main_query(): void {
		$original_theme = get_stylesheet();
		switch_theme( 'twentytwentytwo' );
		$this->assertTrue( wp_is_block_theme(), 'The test must run with a block theme.' );

		$fixture = $this->set_up_product_archive();
		$this->create_archive_product_template(
			$this->get_inherited_collection_markup( array( 'archivePerPage' => 7 ) )
		);
		add_filter( 'loop_shop_per_page', array( $this->sut, 'handle_loop_shop_per_page' ), 20, 1 );

		try {
			$this->go_to( $fixture['archive_url'] );

			$this->assertTrue( is_post_type_archive( 'product' ), 'The request should be the product archive.' );
			$this->assertSame(
				7,
				(int) $GLOBALS['wp_query']->get( 'posts_per_page' ),
				'The archive main query should use the products per page set on the template.'
			);
		} finally {
			remove_filter( 'loop_shop_per_page', array( $this->sut, 'handle_loop_shop_per_page' ), 20 );
			$this->tear_down_product_archive( $fixture );
			switch_theme( $original_theme );
		}
	}

	/**
	 * @testdox Should keep the store's products per page when the template's collection sets none.
	 */
	public function test_archive_template_without_a_page_size_keeps_the_store_default(): void {
		$original_theme = get_stylesheet();
		switch_theme( 'twentytwentytwo' );
		$this->assertTrue( wp_is_block_theme(), 'The test must run with a block theme.' );

		$fixture = $this->set_up_product_archive();
		$this->create_archive_product_template( $this->get_inherited_collection_markup( array() ) );
		add_filter( 'loop_shop_per_page', array( $this->sut, 'handle_loop_shop_per_page' ), 20, 1 );

		try {
			$this->go_to( $fixture['archive_url'] );

			$this->assertSame(
				wc_get_default_products_per_row() * wc_get_default_product_rows_per_page(),
				(int) $GLOBALS['wp_query']->get( 'posts_per_page' ),
				'Without a template page size the store setting must decide, exactly as before.'
			);
		} finally {
			remove_filter( 'loop_shop_per_page', array( $this->sut, 'handle_loop_shop_per_page' ), 20 );
			$this->tear_down_product_archive( $fixture );
			switch_theme( $original_theme );
		}
	}

	/**
	 * Give the store a shop page so WooCommerce treats the product archive as its own.
	 *
	 * @return array<string, mixed> Fixture state for tear_down_product_archive().
	 */
	private function set_up_product_archive(): array {
		$product_post_type = get_post_type_object( 'product' );
		$fixture           = array(
			'shop_page_id'         => get_option( 'woocommerce_shop_page_id' ),
			'permalinks'           => get_option( 'woocommerce_permalinks' ),
			'has_archive'          => $product_post_type ? $product_post_type->has_archive : null,
			'created_shop_page_id' => 0,
			'created_template_id'  => 0,
			'archive_url'          => '',
		);

		$fixture['created_shop_page_id'] = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Shop',
				'post_name'   => 'shop',
			)
		);

		$permalinks                 = is_array( $fixture['permalinks'] ) ? $fixture['permalinks'] : array();
		$permalinks['product_base'] = '/shop';

		update_option( 'woocommerce_shop_page_id', $fixture['created_shop_page_id'] );
		update_option( 'woocommerce_permalinks', $permalinks );

		if ( $product_post_type ) {
			$product_post_type->has_archive = get_page_uri( $fixture['created_shop_page_id'] );
		}

		$archive_url = get_post_type_archive_link( 'product' );
		$this->assertIsString( $archive_url, 'The product archive link should be available.' );
		$fixture['archive_url'] = $archive_url;

		return $fixture;
	}

	/**
	 * Undo set_up_product_archive() and remove the template created for the test.
	 *
	 * @param array<string, mixed> $fixture State returned by set_up_product_archive().
	 */
	private function tear_down_product_archive( array $fixture ): void {
		update_option( 'woocommerce_shop_page_id', $fixture['shop_page_id'] );
		update_option( 'woocommerce_permalinks', $fixture['permalinks'] );

		$product_post_type = get_post_type_object( 'product' );
		if ( $product_post_type ) {
			$product_post_type->has_archive = $fixture['has_archive'];
		}

		if ( $fixture['created_shop_page_id'] ) {
			wp_delete_post( $fixture['created_shop_page_id'], true );
		}
		if ( $this->created_template_id ) {
			wp_delete_post( $this->created_template_id, true );
			$this->created_template_id = 0;
		}
		$this->flush_block_template_caches();
	}

	/**
	 * Save a customised Product Catalog template for the active theme.
	 *
	 * @param string $content Serialized block content.
	 */
	private function create_archive_product_template( string $content ): void {
		$theme = get_stylesheet();
		$term  = get_term_by( 'name', $theme, 'wp_theme', ARRAY_A );
		if ( ! $term ) {
			$term = wp_insert_term( $theme, 'wp_theme' );
		}

		$this->created_template_id = wp_insert_post(
			array(
				'post_name'    => 'archive-product',
				'post_type'    => 'wp_template',
				'post_title'   => 'Product Catalog',
				'post_status'  => 'publish',
				'post_content' => $content,
			)
		);
		wp_set_post_terms( $this->created_template_id, array( (int) $term['term_id'] ), 'wp_theme' );
		$this->flush_block_template_caches();
	}

	/**
	 * Serialized markup of a Product Collection that inherits the archive query.
	 *
	 * @param array $query Query attributes to merge in, such as archivePerPage.
	 * @return string
	 */
	private function get_inherited_collection_markup( array $query ): string {
		$block                   = Utils::get_base_parsed_block();
		$block['attrs']['query'] = array_merge( $block['attrs']['query'], array( 'inherit' => true ), $query );

		return sprintf(
			'<!-- wp:woocommerce/product-collection %s --><div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template /--></div><!-- /wp:woocommerce/product-collection -->',
			wp_json_encode( $block['attrs'] )
		);
	}

	/**
	 * Drop WooCommerce's cached template ids so a template saved by a test is found.
	 */
	private function flush_block_template_caches(): void {
		wp_cache_delete( 'wp_template-ids', 'woocommerce_blocks' );
		wp_cache_delete( 'wp_template_part-ids', 'woocommerce_blocks' );
	}

	/**
	 * @testdox Should add the viewed-product action to a linked Product Collection title.
	 */
	public function test_adds_view_product_directive_to_linked_product_title(): void {
		$block_content = '<h2 class="wp-block-post-title"><a href="https://example.com/product">Product</a></h2>';
		$instance      = $this->create_post_title_block(
			array(
				'__woocommerceNamespace' => 'woocommerce/product-collection/product-title',
				'isLink'                 => true,
			)
		);

		$rendered = $this->sut->add_product_title_click_event_directives( $block_content, array(), $instance );

		$this->assertSame(
			1,
			substr_count( $rendered, 'data-wp-on--click="woocommerce/product-collection::actions.viewProduct"' ),
			'The linked Product Collection title should receive exactly one viewed-product action.'
		);
	}

	/**
	 * @testdox Should leave unrelated or unlinked Product Title markup byte-identical.
	 * @dataProvider provide_uninstrumented_product_title_cases
	 *
	 * @param array  $attributes    Block attributes.
	 * @param string $block_content Rendered block content.
	 */
	public function test_does_not_add_view_product_directive_to_unrelated_titles( array $attributes, string $block_content ): void {
		$instance = $this->create_post_title_block( $attributes );

		$this->assertSame(
			$block_content,
			$this->sut->add_product_title_click_event_directives( $block_content, array(), $instance )
		);
	}

	/**
	 * Cases that must not receive the viewed-product action.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: string}>
	 */
	public function provide_uninstrumented_product_title_cases(): array {
		$linked_title = '<h2 class="wp-block-post-title"><a href="https://example.com/product">Product</a></h2>';

		return array(
			'wrong namespace' => array(
				array(
					'__woocommerceNamespace' => 'woocommerce/single-product/product-title',
					'isLink'                 => true,
				),
				$linked_title,
			),
			'link disabled'   => array(
				array(
					'__woocommerceNamespace' => 'woocommerce/product-collection/product-title',
					'isLink'                 => false,
				),
				$linked_title,
			),
			'missing anchor'  => array(
				array(
					'__woocommerceNamespace' => 'woocommerce/product-collection/product-title',
					'isLink'                 => true,
				),
				'<h2 class="wp-block-post-title">Product</h2>',
			),
		);
	}

	/**
	 * Create a real Post Title block instance for the public render filter.
	 *
	 * @param array $attributes Block attributes.
	 * @return \WP_Block
	 */
	private function create_post_title_block( array $attributes ): \WP_Block {
		return new \WP_Block(
			array(
				'blockName'    => 'core/post-title',
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}
}
