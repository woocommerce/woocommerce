<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
use WC_Helper_Product;
use WC_Structured_Data;
use WC_Unit_Test_Case;

/**
 * Tests for the robots.txt and sitemap store checks.
 */
class StoreOutputChecksTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StoreOutputChecks
	 */
	private $sut;

	/**
	 * Pretty permalinks, as on a live store; no HTTP response unless a test serves one.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->set_permalink_structure( '/%postname%/' );
		$this->sut = wc_get_container()->get( StoreOutputChecks::class );
	}

	/**
	 * Restore plain permalinks, which live on the global rewrite object outside the rollback.
	 */
	public function tearDown(): void {
		try {
			$this->set_permalink_structure( '' );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Fail every HTTP request, as if the store couldn't reach itself.
	 */
	public static function block_requests(): void {
		add_filter( 'pre_http_request', static fn() => new \WP_Error( 'http_request_failed', 'Blocked in tests' ) );
	}

	/**
	 * Answer requests by path from the home URL; a null response fails like a timeout, and any other path is a 404.
	 *
	 * @param array<string, array{0:int,1:string}|null> $responses Status and body by path.
	 */
	private function serve( array $responses ): void {
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( $responses ) {
				unset( $pre, $args );
				$path = substr( $url, strlen( untrailingslashit( home_url() ) ) );
				if ( array_key_exists( $path, $responses ) && null === $responses[ $path ] ) {
					return new \WP_Error( 'http_request_failed', 'Timed out' );
				}
				$response = $responses[ $path ] ?? array( 404, '' );
				return array(
					'headers'  => array(),
					'body'     => $response[1],
					'response' => array(
						'code'    => $response[0],
						'message' => '',
					),
					'cookies'  => array(),
				);
			},
			10,
			3
		);
	}

	/**
	 * Results by check id.
	 *
	 * @return array<string, bool|null>
	 */
	private function results(): array {
		return array_column( $this->sut->all(), 'ok', 'id' );
	}

	/**
	 * @testdox When the store cannot load its own pages, the sitemap check is couldn't check.
	 */
	public function test_unreachable_store_couldnt_check(): void {
		self::block_requests();

		$this->assertNull( $this->results()['sitemap_lists_products'] );
	}

	/**
	 * @testdox When robots.txt is a page rather than a file, the sitemap check is couldn't check unless a default sitemap lists products.
	 */
	public function test_robots_txt_page(): void {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		$this->serve( array( '/robots.txt' => array( 200, '<!doctype html><title>Shop</title>' ) ) );
		$this->assertNull( $this->results()['sitemap_lists_products'] );

		remove_all_filters( 'pre_http_request' );
		$this->serve(
			array(
				'/robots.txt'  => array( 200, '<!doctype html><title>Shop</title>' ),
				'/sitemap.xml' => array( 200, '<sitemapindex><sitemap><loc>' . home_url( '/product-sitemap.xml' ) . '</loc></sitemap></sitemapindex>' ),
			)
		);
		$this->assertTrue( $this->results()['sitemap_lists_products'] );
	}

	/**
	 * @testdox With the WordPress sitemap off, a product sitemap listed in robots.txt or at /sitemap_index.xml passes.
	 */
	public function test_seo_plugin_sitemap(): void {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		$this->serve(
			array(
				'/robots.txt'            => array( 200, "User-agent: *\nDisallow:\nSitemap: " . home_url( '/seo/sitemap_index.xml' ) . "\n" ),
				'/seo/sitemap_index.xml' => array( 200, '<sitemapindex><sitemap><loc>' . home_url( '/product-sitemap.xml' ) . '</loc></sitemap></sitemapindex>' ),
			)
		);

		$this->assertTrue( $this->results()['sitemap_lists_products'] );
	}

	/**
	 * @testdox Only the first five sitemaps robots.txt lists are loaded.
	 */
	public function test_listed_sitemaps_capped(): void {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		$requested = array();
		$robots    = "User-agent: *\nDisallow:\n";
		for ( $i = 1; $i <= 50; $i++ ) {
			$robots .= 'Sitemap: ' . home_url( "/s$i.xml" ) . "\n";
		}
		$served = array_fill_keys( array_map( static fn( int $i ): string => "/s$i.xml", range( 1, 50 ) ), array( 404, '' ) );
		$this->serve( $served + array( '/robots.txt' => array( 200, $robots ) ) );
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( &$requested ) {
				unset( $args );
				$requested[] = $url;
				return $pre;
			},
			5,
			3
		);

		$this->results();

		$requested = array_filter( $requested, static fn( string $url ): bool => (bool) preg_match( '#(robots\.txt|\.xml)$#', $url ) );
		$this->assertCount( 8, array_unique( $requested ), 'robots.txt, five listed sitemaps, /sitemap_index.xml and /sitemap.xml' );
	}

	/**
	 * @testdox Jetpack's sitemap index passes when the child file lists a product, and a child with no product does not.
	 */
	public function test_jetpack_sitemap_index(): void {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		$index = '<sitemapindex><sitemap><loc>' . home_url( '/sitemap-1.xml' ) . '</loc></sitemap></sitemapindex>';
		$this->serve(
			array(
				'/robots.txt'    => array( 200, "User-agent: *\nDisallow:\nSitemap: " . home_url( '/sitemap.xml' ) . "\n" ),
				'/sitemap.xml'   => array( 200, $index ),
				'/sitemap-1.xml' => array( 200, '<urlset><url><loc>' . home_url( '/product/beanie/' ) . '</loc></url></urlset>' ),
			)
		);
		$this->assertTrue( $this->results()['sitemap_lists_products'] );

		remove_all_filters( 'pre_http_request' );
		$this->serve(
			array(
				'/robots.txt'        => array( 200, "User-agent: *\nDisallow:\nSitemap: " . home_url( '/sitemap.xml' ) . "\n" ),
				'/sitemap.xml'       => array( 200, '<sitemapindex><sitemap><loc>https://cdn.example/sitemap.xml</loc></sitemap><sitemap><loc>' . home_url( '/sitemap-1.xml' ) . '</loc></sitemap></sitemapindex>' ),
				'/sitemap-1.xml'     => array( 200, '<urlset><url><loc>' . home_url( '/about/' ) . '</loc></url></urlset>' ),
				'/sitemap_index.xml' => array( 404, '' ),
			)
		);
		$this->assertFalse( $this->results()['sitemap_lists_products'] );
	}

	/**
	 * @testdox A nested index is opened, as Jetpack serves past 1,000 URLs, and only five child sitemaps are loaded in all.
	 */
	public function test_child_sitemaps_capped_by_requests(): void {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		$product = '<urlset><url><loc>' . home_url( '/product/beanie/' ) . '</loc></url></urlset>';
		$this->serve(
			array(
				'/robots.txt'          => array( 404, '' ),
				'/sitemap.xml'         => array( 200, '<sitemapindex><sitemap><loc>' . home_url( '/sitemap-index-1.xml' ) . '</loc></sitemap></sitemapindex>' ),
				'/sitemap-index-1.xml' => array( 200, '<sitemapindex><sitemap><loc>' . home_url( '/sitemap-1.xml' ) . '</loc></sitemap></sitemapindex>' ),
				'/sitemap-1.xml'       => array( 200, $product ),
				'/sitemap_index.xml'   => array( 404, '' ),
			)
		);
		$this->assertTrue( $this->results()['sitemap_lists_products'], 'A nested index is opened' );

		remove_all_filters( 'pre_http_request' );
		$locs   = '';
		$about  = '<urlset><url><loc>' . home_url( '/about/' ) . '</loc></url></urlset>';
		$served = array(
			'/robots.txt'        => array( 404, '' ),
			'/sitemap_index.xml' => array( 404, '' ),
		);
		for ( $i = 1; $i <= 6; $i++ ) {
			$path            = '/child-' . $i . '.xml';
			$locs           .= '<sitemap><loc>' . home_url( $path ) . '</loc></sitemap>';
			$served[ $path ] = array( 200, 6 === $i ? $product : $about );
		}
		$served['/sitemap.xml'] = array( 200, '<sitemapindex>' . $locs . '</sitemapindex>' );
		$requested              = array();
		$this->serve( $served );
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( &$requested ) {
				unset( $args );
				$requested[] = $url;
				return $pre;
			},
			5,
			3
		);

		$this->assertFalse( $this->results()['sitemap_lists_products'] );
		$this->assertContains( home_url( '/child-5.xml' ), $requested, 'Five child sitemaps are opened' );
		$this->assertNotContains( home_url( '/child-6.xml' ), $requested, 'The sixth is past the cap' );
	}

	/**
	 * @testdox A sitemap robots.txt lists on another host is skipped, not couldn't check.
	 */
	public function test_off_host_listed_sitemap_is_skipped(): void {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		$this->serve(
			array(
				'/robots.txt'        => array( 200, "User-agent: *\nDisallow:\nSitemap: https://cdn.example/sitemap.xml\n" ),
				'/sitemap_index.xml' => array( 404, '' ),
				'/sitemap.xml'       => array( 404, '' ),
			)
		);

		$this->assertFalse( $this->results()['sitemap_lists_products'] );
	}

	/**
	 * @testdox Sitemaps robots.txt lists on another host, or as //host references, do not use up the five slots.
	 */
	public function test_off_host_listed_sitemaps_do_not_use_the_cap(): void {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		$robots = "User-agent: *\nDisallow:\n";
		for ( $i = 1; $i <= 5; $i++ ) {
			$robots .= ( 1 === $i % 2 ? "Sitemap: https://cdn.example/s$i.xml\n" : "Sitemap: //cdn.example/s$i.xml\n" );
		}
		$robots .= 'Sitemap: ' . home_url( '/seo/product-list.xml' ) . "\n";
		$this->serve(
			array(
				'/robots.txt'           => array( 200, $robots ),
				'/seo/product-list.xml' => array( 200, '<sitemapindex><sitemap><loc>' . home_url( '/product-sitemap.xml' ) . '</loc></sitemap></sitemapindex>' ),
				'/sitemap_index.xml'    => array( 404, '' ),
				'/sitemap.xml'          => array( 404, '' ),
			)
		);

		$this->assertTrue( $this->results()['sitemap_lists_products'] );
	}

	/**
	 * @testdox A sitemap that answers 403 or 5xx is couldn't check, not a missing sitemap.
	 */
	public function test_blocked_sitemap_is_unknown(): void {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		foreach ( array( 403, 503 ) as $status ) {
			remove_all_filters( 'pre_http_request' );
			$this->serve(
				array(
					'/robots.txt'        => array( 404, '' ),
					'/sitemap_index.xml' => array( 404, '' ),
					'/sitemap.xml'       => array( $status, '<html>Blocked</html>' ),
				)
			);
			$this->assertNull( $this->results()['sitemap_lists_products'], "Status $status" );
		}
	}

	/**
	 * @testdox The shop page URL is not a product page when the product base is the shop slug.
	 */
	public function test_shop_page_url_is_not_a_product_in_the_sitemap(): void {
		$names = new \ReflectionMethod( StoreOutputChecks::class, 'names_products' );
		$names->setAccessible( true );
		add_filter(
			'pre_option_woocommerce_permalinks',
			static fn() => array( 'product_base' => 'shop' )
		);
		$shop = '<urlset><url><loc>' . home_url( '/shop/' ) . '</loc></url></urlset>';
		$item = '<urlset><url><loc>' . home_url( '/shop/beanie/' ) . '</loc></url></urlset>';

		$this->assertFalse( $names->invoke( null, $shop ) );
		$this->assertTrue( $names->invoke( null, $item ) );
	}

	/**
	 * @testdox With the WordPress sitemap off, no sitemap is missing, and an unreachable one is couldn't check.
	 */
	public function test_sitemap_off(): void {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		$this->serve(
			array(
				'/robots.txt'        => array( 404, '' ),
				'/sitemap_index.xml' => array( 404, '' ),
				'/sitemap.xml'       => array( 200, '<urlset><url><loc>' . home_url( '/about/' ) . '</loc></url></urlset>' ),
			)
		);
		$this->assertFalse( $this->results()['sitemap_lists_products'] );

		remove_all_filters( 'pre_http_request' );
		$this->serve(
			array(
				'/robots.txt'        => array( 404, '' ),
				'/sitemap_index.xml' => array( 200, '<!doctype html><a href="' . home_url( '/product/beanie/' ) . '">Beanie</a>' ),
				'/sitemap.xml'       => array( 404, '' ),
			)
		);
		$this->assertFalse( $this->results()['sitemap_lists_products'], 'A page that is not a sitemap does not count' );

		remove_all_filters( 'pre_http_request' );
		$category_sitemap = home_url( '/wp-sitemap-taxonomies-product_cat-1.xml' );
		$this->serve(
			array(
				'/robots.txt'                              => array( 200, "User-agent: *\nDisallow:\nSitemap: $category_sitemap\n" ),
				'/wp-sitemap-taxonomies-product_cat-1.xml' => array( 200, '<sitemapindex><sitemap><loc>' . $category_sitemap . '</loc></sitemap></sitemapindex>' ),
				'/sitemap_index.xml'                       => array( 404, '' ),
				'/sitemap.xml'                             => array( 404, '' ),
			)
		);
		$this->assertFalse( $this->results()['sitemap_lists_products'], 'A product category sitemap does not list products' );

		remove_all_filters( 'pre_http_request' );
		self::block_requests();
		$this->assertNull( $this->results()['sitemap_lists_products'] );
	}

	/**
	 * Path of a product's page from the home URL.
	 *
	 * @param \WC_Product $product Product.
	 */
	private static function path_of( \WC_Product $product ): string {
		return substr( (string) $product->get_permalink(), strlen( untrailingslashit( home_url() ) ) );
	}

	/**
	 * A page with a JSON-LD block for each item.
	 *
	 * @param array<int, array|string> $blocks Decoded JSON-LD, or a raw block body.
	 */
	private static function page_with( array $blocks ): string {
		$html = '<!doctype html><html><head><title>Product</title>';
		foreach ( $blocks as $block ) {
			$html .= '<script type="application/ld+json">' . ( is_string( $block ) ? $block : wp_json_encode( $block ) ) . '</script>';
		}
		return $html . '</head><body></body></html>';
	}

	/**
	 * The JSON-LD WooCommerce prints on a product's page.
	 *
	 * @param \WC_Product $product Product.
	 */
	private static function core_product_jsonld( \WC_Product $product ): string {
		$structured_data = new WC_Structured_Data();
		$structured_data->generate_product_data( $product );
		return wc_esc_json( (string) wp_json_encode( $structured_data->get_structured_data( array() ), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ), true );
	}

	/**
	 * Results of the three product structured data checks.
	 *
	 * @return array<int, bool|null>
	 */
	private function product_results(): array {
		$results = $this->results();
		return array( $results['product_jsonld_present'], $results['product_jsonld_required_fields'], $results['product_offer_jsonld'] );
	}

	/**
	 * @testdox The JSON-LD WooCommerce prints for a simple and a variable product passes all three product checks.
	 *
	 * @testWith ["simple"]
	 *           ["variable"]
	 *
	 * @param string $type Product type.
	 */
	public function test_core_product_jsonld_passes( string $type ): void {
		$product = 'simple' === $type ? WC_Helper_Product::create_simple_product() : WC_Helper_Product::create_variation_product();
		$product->set_image_id( self::factory()->attachment->create_object( array( 'file' => 'image.jpg' ) ) );
		$product->save();
		if ( $product instanceof \WC_Product_Variable ) {
			\WC_Product_Variable::sync( $product );
		}
		$product = wc_get_product( $product->get_id() );
		$this->serve( array( self::path_of( $product ) => array( 200, self::page_with( array( self::core_product_jsonld( $product ) ) ) ) ) );

		$this->assertSame( array( true, true, true ), $this->product_results() );
	}

	/**
	 * @testdox The product page checks skip a newer product of a type the report doesn't check.
	 */
	public function test_product_checks_skip_unchecked_types(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( self::factory()->attachment->create_object( array( 'file' => 'image.jpg' ) ) );
		$product->set_date_created( time() - HOUR_IN_SECONDS );
		$product->save();
		$product = wc_get_product( $product->get_id() );
		$this->serve( array( self::path_of( $product ) => array( 200, self::page_with( array( self::core_product_jsonld( $product ) ) ) ) ) );
		$grouped = new \WC_Product_Grouped();
		$grouped->set_name( 'Newer grouped product' );
		$grouped->save();

		$this->assertSame( array( true, true, true ), $this->product_results() );
	}

	/**
	 * @testdox The product checks read the newest product with a price, so an unfinished newer product doesn't decide them.
	 */
	public function test_product_checks_prefer_a_priced_product(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( self::factory()->attachment->create_object( array( 'file' => 'image.jpg' ) ) );
		$product->set_date_created( time() - HOUR_IN_SECONDS );
		$product->save();
		$product = wc_get_product( $product->get_id() );
		$this->serve( array( self::path_of( $product ) => array( 200, self::page_with( array( self::core_product_jsonld( $product ) ) ) ) ) );
		$unpriced = WC_Helper_Product::create_simple_product();
		$unpriced->set_regular_price( '' );
		$unpriced->save();

		$this->assertSame( array( true, true, true ), $this->product_results() );
	}

	/**
	 * @testdox A shop-only product is sampled. A newer search-only product is not.
	 */
	public function test_product_checks_include_shop_only_products(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_catalog_visibility( 'catalog' );
		$product->set_image_id( self::factory()->attachment->create_object( array( 'file' => 'image.jpg' ) ) );
		$product->set_date_created( time() - HOUR_IN_SECONDS );
		$product->save();
		$product     = wc_get_product( $product->get_id() );
		$search_only = WC_Helper_Product::create_simple_product();
		$search_only->set_catalog_visibility( 'search' );
		$search_only->save();
		$this->serve(
			array(
				self::path_of( $product )     => array( 200, self::page_with( array( self::core_product_jsonld( $product ) ) ) ),
				self::path_of( $search_only ) => array( 200, self::page_with( array() ) ),
			)
		);

		$this->assertSame( array( true, true, true ), $this->product_results() );
	}

	/**
	 * @testdox A store with only grouped and external products could not be checked, rather than failed.
	 */
	public function test_unsupported_product_types_are_not_a_failure(): void {
		$grouped = new \WC_Product_Grouped();
		$grouped->set_name( 'Grouped product' );
		$grouped->save();
		$external = WC_Helper_Product::create_external_product();
		$this->serve(
			array(
				self::path_of( $grouped )  => array( 200, self::page_with( array() ) ),
				self::path_of( $external ) => array( 200, self::page_with( array() ) ),
			)
		);

		$this->assertSame( array( null, null, null ), $this->product_results() );
	}

	/**
	 * @testdox WooCommerce's own product JSON-LD is no conflict, nor is none at all. One from another source, or a second one beside it, is, and the first failure says so.
	 */
	public function test_product_jsonld_conflict(): void {
		$product = WC_Helper_Product::create_simple_product();
		$path    = self::path_of( $product );
		$core    = self::core_product_jsonld( $product );
		$other   = array(
			'@type' => 'Product',
			'name'  => 'Dummy Product',
		);

		$this->serve( array( $path => array( 200, self::page_with( array( $core ) ) ) ) );
		$this->assertTrue( $this->results()['product_jsonld_from_core'] );
		$this->assertFalse( array_column( $this->sut->all(), 'conflict', 'id' )['product_jsonld_from_core'], 'A passing check is no conflict' );

		remove_all_filters( 'pre_http_request' );
		$this->serve( array( $path => array( 200, self::page_with( array() ) ) ) );
		$this->assertFalse( array_column( $this->sut->all(), 'conflict', 'id' )['product_jsonld_present'], 'Missing is not replaced' );

		remove_all_filters( 'pre_http_request' );
		$https = str_replace( 'http:', 'https:', $core );
		$this->serve( array( $path => array( 200, self::page_with( array( $https ) ) ) ) );
		$this->assertTrue( $this->results()['product_jsonld_from_core'], 'Only the scheme differs' );

		remove_all_filters( 'pre_http_request' );
		$this->serve( array( $path => array( 200, self::page_with( array( $other ) ) ) ) );
		$this->assertFalse( $this->results()['product_jsonld_from_core'], 'Replaced' );

		remove_all_filters( 'pre_http_request' );
		$this->serve( array( $path => array( 200, self::page_with( array( $core, $other ) ) ) ) );
		$this->assertFalse( $this->results()['product_jsonld_from_core'], 'Repeated' );

		$conflicts = array_column( $this->sut->all(), 'conflict', 'id' );
		$this->assertTrue( $conflicts['product_jsonld_from_core'] );
		$this->assertFalse( $conflicts['sitemap_lists_products'] );
	}

	/**
	 * @testdox A product page without Product JSON-LD fails only the presence check. One without a price fails only the offer check. A price written with a currency still counts.
	 */
	public function test_missing_product_jsonld(): void {
		$product = WC_Helper_Product::create_simple_product();
		$path    = self::path_of( $product );
		$this->serve( array( $path => array( 200, self::page_with( array( array( '@type' => 'BreadcrumbList' ) ) ) ) ) );
		$this->assertSame( array( false, null, null ), $this->product_results() );

		remove_all_filters( 'pre_http_request' );
		$no_price = array(
			'@type'  => 'Product',
			'name'   => 'Dummy Product',
			'image'  => 'https://example.org/image.jpg',
			'offers' => array(
				'@type'        => 'Offer',
				'availability' => 'https://schema.org/InStock',
			),
		);
		$this->serve( array( $path => array( 200, self::page_with( array( $no_price ) ) ) ) );
		$this->assertSame( array( true, true, false ), $this->product_results() );

		remove_all_filters( 'pre_http_request' );
		$no_price['offers']['price'] = '10.00 USD';
		$this->serve( array( $path => array( 200, self::page_with( array( $no_price ) ) ) ) );
		$this->assertSame( array( true, true, true ), $this->product_results(), 'A price with a currency code counts' );
	}

	/**
	 * @testdox With no product, an unreachable product page or an error page, the product checks are couldn't check.
	 */
	public function test_product_checks_couldnt_check(): void {
		$this->serve( array() );
		$this->assertSame( array( null, null, null ), $this->product_results(), 'No product' );

		$product = WC_Helper_Product::create_simple_product();
		remove_all_filters( 'pre_http_request' );
		$this->serve( array( self::path_of( $product ) => null ) );
		$this->assertSame( array( null, null, null ), $this->product_results(), 'Unreachable' );

		remove_all_filters( 'pre_http_request' );
		$this->serve( array( self::path_of( $product ) => array( 503, self::page_with( array( self::core_product_jsonld( $product ) ) ) ) ) );
		$this->assertSame( array( null, null, null ), $this->product_results(), 'Error page' );

		remove_all_filters( 'pre_http_request' );
		add_filter(
			'pre_http_request',
			static function () {
				return array(
					'headers'  => array( 'content-length' => '99999999' ),
					'body'     => '<html><body>No product data here</body></html>',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
				);
			}
		);
		$this->assertSame( array( null, null, null ), $this->product_results(), 'Page cut off before the footer' );
	}

	/**
	 * @testdox A password protected product is skipped for the newest product shoppers can open.
	 */
	public function test_password_protected_product_skipped(): void {
		$open = WC_Helper_Product::create_simple_product();
		$open->set_date_created( time() - HOUR_IN_SECONDS );
		$open->set_image_id( self::factory()->attachment->create_object( array( 'file' => 'image.jpg' ) ) );
		$open->save();
		$locked = WC_Helper_Product::create_simple_product();
		$locked->set_post_password( 'secret' );
		$locked->save();
		$this->serve(
			array(
				self::path_of( $open )   => array( 200, self::page_with( array( self::core_product_jsonld( $open ) ) ) ),
				self::path_of( $locked ) => array( 200, self::page_with( array() ) ),
			)
		);

		$this->assertSame( array( true, true, true ), $this->product_results() );
	}
}
