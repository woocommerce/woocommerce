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
	 * @testdox On a stock store that can't load its own pages, robots.txt is read as WordPress serves it and every check passes.
	 */
	public function test_stock_store_passes_without_requests(): void {
		self::block_requests();

		$results = $this->results();

		$this->assertTrue( $results['robots_txt_present'] );
		$this->assertTrue( $results['robots_allows_ai_search_bots'] );
		$this->assertTrue( $results['robots_not_blocking_llms_txt'] );
		$this->assertTrue( $results['sitemap_lists_products'], 'The WordPress sitemap includes products' );
	}

	/**
	 * @testdox A served robots.txt that blocks an AI search crawler and llms.txt fails both checks.
	 */
	public function test_served_robots_txt_blocks(): void {
		$this->serve( array( '/robots.txt' => array( 200, "User-agent: PerplexityBot\nDisallow: /\n\nUser-agent: *\nDisallow: /llms.txt\n" ) ) );

		$results = $this->results();

		$this->assertTrue( $results['robots_txt_present'] );
		$this->assertFalse( $results['robots_allows_ai_search_bots'] );
		$this->assertFalse( $results['robots_not_blocking_llms_txt'] );
	}

	/**
	 * @testdox A robots_txt filter that blocks crawlers counts when the store can't load robots.txt.
	 */
	public function test_robots_txt_filter(): void {
		self::block_requests();
		add_filter( 'robots_txt', static fn( $output ) => $output . "User-agent: OAI-SearchBot\nDisallow: /\n" );

		$this->assertFalse( $this->results()['robots_allows_ai_search_bots'] );
	}

	/**
	 * @testdox With Plain permalinks and no file, or a page served for every URL, there is no robots.txt.
	 */
	public function test_no_robots_txt(): void {
		$this->set_permalink_structure( '' );
		$this->serve( array() );
		$this->assertFalse( $this->results()['robots_txt_present'] );

		$this->set_permalink_structure( '/%postname%/' );
		remove_all_filters( 'pre_http_request' );
		$this->serve( array( '/robots.txt' => array( 200, '<!doctype html><title>Shop</title>' ) ) );
		$results = $this->results();
		$this->assertFalse( $results['robots_txt_present'] );
		$this->assertTrue( $results['robots_allows_ai_search_bots'], 'No robots.txt blocks nothing' );
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
	 * @testdox Product JSON-LD from an SEO plugin, in a @graph with its own types and offer list, passes in place of core's.
	 */
	public function test_seo_plugin_product_jsonld_passes(): void {
		$product = WC_Helper_Product::create_simple_product();
		$graph   = array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				array(
					'@type' => 'WebPage',
					'name'  => 'Dummy Product',
				),
				array(
					'@type'  => array( 'schema:Product' ),
					'name'   => 'Dummy Product',
					'image'  => array( 'https://example.org/image.jpg' ),
					'offers' => array(
						'@type'  => 'AggregateOffer',
						'offers' => array(
							array(
								'@type'              => 'Offer',
								'priceSpecification' => array( 'price' => '10.00' ),
								'availability'       => 'https://schema.org/InStock',
							),
						),
					),
				),
			),
		);
		$this->serve( array( self::path_of( $product ) => array( 200, self::page_with( array( '{ not json', $graph ) ) ) ) );

		$this->assertSame( array( true, true, true ), $this->product_results() );
	}

	/**
	 * @testdox A product page without Product JSON-LD fails all three checks; one without a price fails only the offer check.
	 */
	public function test_missing_product_jsonld(): void {
		$product = WC_Helper_Product::create_simple_product();
		$path    = self::path_of( $product );
		$this->serve( array( $path => array( 200, self::page_with( array( array( '@type' => 'BreadcrumbList' ) ) ) ) ) );
		$this->assertSame( array( false, false, false ), $this->product_results() );

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
