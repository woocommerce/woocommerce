<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use WC_Helper_Product;
use WC_Product;
use WC_Unit_Test_Case;
use WP_Query;

/**
 * Tests Product Collection parity with WooCommerce's classic product loop.
 */
class RouteContextParityTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should render the same ordered products as the classic loop for $route_label.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider route_context_provider
	 *
	 * @param string   $route_label       Route label.
	 * @param string   $legacy_template   Legacy Template block template attribute.
	 * @param string[] $expected_products Expected ordered product names.
	 */
	public function test_product_collection_and_product_query_match_classic_route( string $route_label, string $legacy_template, array $expected_products ): void {
		$global_presence = array(
			'post'             => array_key_exists( 'post', $GLOBALS ),
			'product'          => array_key_exists( 'product', $GLOBALS ),
			'woocommerce_loop' => array_key_exists( 'woocommerce_loop', $GLOBALS ),
			'wp_query'         => array_key_exists( 'wp_query', $GLOBALS ),
			'wp_the_query'     => array_key_exists( 'wp_the_query', $GLOBALS ),
		);

		global $post, $product, $woocommerce_loop, $wp_query, $wp_the_query;

		$original_globals = array(
			'post'             => $post ?? null,
			'product'          => $product ?? null,
			'woocommerce_loop' => $woocommerce_loop ?? null,
			'wp_query'         => $wp_query ?? null,
			'wp_the_query'     => $wp_the_query ?? null,
		);
		$original_options = array(
			'posts_per_page'                      => get_option( 'posts_per_page' ),
			'woocommerce_default_catalog_orderby' => get_option( 'woocommerce_default_catalog_orderby' ),
		);
		$product_ids      = array();
		$term_ids         = array();

		try {
			update_option( 'posts_per_page', 20 );
			update_option( 'woocommerce_default_catalog_orderby', 'menu_order' );

			$category_id = self::factory()->term->create(
				array(
					'taxonomy' => 'product_cat',
					'name'     => 'Slice 028 category',
					'slug'     => 'slice-028-category',
				)
			);
			$tag_id      = self::factory()->term->create(
				array(
					'taxonomy' => 'product_tag',
					'name'     => 'Slice 028 tag',
					'slug'     => 'slice-028-tag',
				)
			);
			$term_ids    = array(
				'product_cat' => $category_id,
				'product_tag' => $tag_id,
			);

			$product_ids[] = $this->create_product( 'Slice 028 Shirt A', 30, $category_id, $tag_id );
			$product_ids[] = $this->create_product( 'Slice 028 Shirt B', 10, $category_id );
			$product_ids[] = $this->create_product( 'Slice 028 Shirt C', 20, null, $tag_id );
			$product_ids[] = $this->create_product( 'Slice 028 Catalog D', 40 );

			$route = $this->get_route_url( $route_label, $term_ids );
			$this->go_to( $route );
			$wp_the_query = $wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WP_UnitTestCase::go_to() does not identify its query as main for WooCommerce's pre_get_posts hook.
			$this->prepare_route_product_query( $route_label, $wp_query );
			wc_reset_loop();
			wc_setup_loop(
				array(
					'total'        => $wp_query->post_count,
					'total_pages'  => 1,
					'per_page'     => 20,
					'current_page' => 1,
				)
			);

			$this->assertInstanceOf( WP_Query::class, $wp_query, 'The route should establish a real main query.' );
			$this->assertGreaterThan( 0, $wp_query->post_count, 'The prepared WooCommerce route query should contain products.' );
			$this->assertGreaterThan( 0, wc_get_loop_prop( 'total' ), 'The prepared WooCommerce route loop should contain products.' );
			$wp_query->rewind_posts();

			$classic_products = $this->extract_product_names(
				$this->render_legacy_template( $legacy_template ),
				'woocommerce-loop-product__title'
			);
			$wp_query->rewind_posts();
			$product_query_products = $this->extract_product_names(
				$this->render_product_query(),
				'wp-block-post-title'
			);
			$wp_query->rewind_posts();
			$product_collection_products = $this->extract_product_names(
				$this->render_product_collection(),
				'wp-block-post-title'
			);

			$this->assertNotEmpty( $product_query_products, 'The Products route result should not be empty.' );
			$this->assertNotEmpty( $product_collection_products, 'The Product Collection route result should not be empty.' );
			$this->assertNotEmpty( $classic_products, 'The classic route query result should not be empty.' );
			$this->assertSame( $expected_products, $product_query_products, 'Products should render the expected ordered identities.' );
			$this->assertSame( $expected_products, $product_collection_products, 'Product Collection should render the expected ordered identities.' );
			$this->assertSame( $expected_products, $classic_products, 'The classic route should expose the expected ordered identities.' );
			$this->assertSame( $classic_products, $product_query_products, 'Products and the classic route should have strict ordered parity.' );
			$this->assertSame( $classic_products, $product_collection_products, 'Product Collection and the classic route should have strict ordered parity.' );
		} finally {
			wp_reset_postdata();
			wc_reset_loop();

			foreach ( $product_ids as $product_id ) {
				WC_Helper_Product::delete_product( $product_id );
			}
			foreach ( $term_ids as $taxonomy => $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
			foreach ( $original_options as $option_name => $option_value ) {
				update_option( $option_name, $option_value );
			}

			foreach ( $original_globals as $global_name => $global_value ) {
				if ( $global_presence[ $global_name ] ) {
					$GLOBALS[ $global_name ] = $global_value; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact route global captured before the provider row.
				} else {
					unset( $GLOBALS[ $global_name ] );
				}
			}
		}
	}

	/**
	 * Run WooCommerce's real main-product-query preparation for a test route.
	 *
	 * @param string   $route_label Route label.
	 * @param WP_Query $query       Main route query.
	 */
	private function prepare_route_product_query( string $route_label, WP_Query $query ): void {
		if ( 'search' === $route_label ) {
			$query->is_search            = true;
			$query->is_post_type_archive = true;
			$query->is_archive           = true;
			$query->is_404               = false;
		}

		$query->set( 'post_type', 'product' );
		$query->set( 'posts_per_page', 20 );
		WC()->query->product_query( $query );
		$query->get_posts();
		$query->rewind_posts();
	}

	/**
	 * Route contexts and their deterministic identities.
	 *
	 * @return array<string, array{string, string, string[]}>
	 */
	public function route_context_provider(): array {
		return array(
			'product category' => array(
				'category',
				'taxonomy-product_cat',
				array( 'Slice 028 Shirt B', 'Slice 028 Shirt A' ),
			),
			'product tag'      => array(
				'tag',
				'taxonomy-product_tag',
				array( 'Slice 028 Shirt C', 'Slice 028 Shirt A' ),
			),
			'product search'   => array(
				'search',
				'product-search-results',
				array( 'Slice 028 Shirt B', 'Slice 028 Shirt C', 'Slice 028 Shirt A' ),
			),
		);
	}

	/**
	 * Create a visible product with deterministic catalog ordering.
	 *
	 * @param string   $name        Product name.
	 * @param int      $menu_order  Menu order.
	 * @param int|null $category_id Product category ID.
	 * @param int|null $tag_id      Product tag ID.
	 * @return int Product ID.
	 */
	private function create_product( string $name, int $menu_order, ?int $category_id = null, ?int $tag_id = null ): int {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => $name,
				'regular_price' => '10',
			)
		);
		$this->assertInstanceOf( WC_Product::class, $product, 'The route fixture should create a WooCommerce product.' );
		$product->set_name( $name );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_menu_order( $menu_order );
		$product->save();

		if ( null !== $category_id ) {
			wp_set_object_terms( $product->get_id(), array( $category_id ), 'product_cat' );
		}
		if ( null !== $tag_id ) {
			wp_set_object_terms( $product->get_id(), array( $tag_id ), 'product_tag' );
		}

		return $product->get_id();
	}

	/**
	 * Resolve the provider row to a real frontend route.
	 *
	 * @param string             $route_label  Route label.
	 * @param array<string, int> $term_ids    Product term IDs.
	 * @return string Route URL.
	 */
	private function get_route_url( string $route_label, array $term_ids ): string {
		switch ( $route_label ) {
			case 'category':
				return (string) get_term_link( $term_ids['product_cat'], 'product_cat' );
			case 'tag':
				return (string) get_term_link( $term_ids['product_tag'], 'product_tag' );
			case 'search':
				return home_url( '/?s=Slice+028+Shirt&post_type=product&orderby=menu_order&order=ASC' );
			default:
				$this->fail( 'Unknown route provider row.' );
		}
	}

	/**
	 * Render a minimal inherited Product Collection through the registered blocks.
	 *
	 * @return string Rendered block markup.
	 */
	private function render_product_collection(): string {
		$attributes = array(
			'queryId'       => 28,
			'query'         => array(
				'inherit'                  => true,
				'isProductCollectionBlock' => true,
			),
			'displayLayout' => array(
				'type'          => 'flex',
				'columns'       => 3,
				'shrinkColumns' => true,
			),
		);

		return do_blocks(
			sprintf(
				'<!-- wp:woocommerce/product-collection %1$s --><div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template --><!-- wp:post-title /--><!-- /wp:woocommerce/product-template --></div><!-- /wp:woocommerce/product-collection -->',
				wp_json_encode( $attributes )
			)
		);
	}

	/**
	 * Render a minimal inherited Products block through the registered blocks.
	 *
	 * @return string Rendered block markup.
	 */
	private function render_product_query(): string {
		$attributes = array(
			'namespace' => 'woocommerce/product-query',
			'queryId'   => 69,
			'query'     => array(
				'inherit'  => true,
				'postType' => 'product',
			),
		);

		return do_blocks(
			sprintf(
				'<!-- wp:query %1$s --><div class="wp-block-query"><!-- wp:post-template %2$s --><!-- wp:post-title /--><!-- /wp:post-template --></div><!-- /wp:query -->',
				wp_json_encode( $attributes ),
				wp_json_encode( array( '__woocommerceNamespace' => 'woocommerce/product-query/product-template' ) )
			)
		);
	}

	/**
	 * Render the registered Legacy Template block.
	 *
	 * @param string $template Template attribute.
	 * @return string Rendered block markup.
	 */
	private function render_legacy_template( string $template ): string {
		return do_blocks(
			sprintf(
				'<!-- wp:woocommerce/legacy-template %s /-->',
				wp_json_encode( array( 'template' => $template ) )
			)
		);
	}

	/**
	 * Extract normalized product names from a rendered block.
	 *
	 * @param string $markup     Rendered markup.
	 * @param string $class_name Product-title class.
	 * @return string[] Product names.
	 */
	private function extract_product_names( string $markup, string $class_name ): array {
		$document                = new \DOMDocument();
		$previous_libxml_setting = libxml_use_internal_errors( true );
		$loaded                  = $document->loadHTML( '<!DOCTYPE html><html><body>' . $markup . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_libxml_setting );
		$this->assertTrue( $loaded, 'Rendered product markup should be parseable HTML.' );

		$xpath = new \DOMXPath( $document );
		$nodes = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' {$class_name} ')]" );
		$this->assertNotFalse( $nodes, 'The rendered product-title query should be valid.' );

		$names = array();
		foreach ( $nodes as $node ) {
			$names[] = trim( html_entity_decode( $node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOMNode defines this public property name.
		}

		return $names;
	}
}
