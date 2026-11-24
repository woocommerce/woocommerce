<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\AgentReadiness\JsonLd;
use Automattic\WooCommerce\Internal\AgentReadiness\RemoteRules;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreResponses;
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
	 * @param array<string, array{0:int,1:string,2?:array<string,string>}|null> $responses Status, body and headers by path.
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
					'headers'  => new \WpOrg\Requests\Utility\CaseInsensitiveDictionary( $response[2] ?? array() ),
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
	 * @testdox A product page check, page HTML or JSON-LD, with no product to open is marked no_product; one whose product page didn't load, or a home page check, is not.
	 */
	public function test_product_page_check_without_a_product_is_no_product(): void {
		$this->serve( array( '/' => array( 200, '<html><head><meta name="viewport" content="width=device-width"></head><body></body></html>' ) ) );
		$checks = array_column( $this->sut->all(), null, 'id' );
		$this->assertNull( $checks['canonical_url']['ok'] );
		$this->assertTrue( $checks['canonical_url']['no_product'], 'No product, so the check waits for one' );
		$this->assertNull( $checks['product_jsonld_present']['ok'] );
		$this->assertTrue( $checks['product_jsonld_present']['no_product'], 'A JSON-LD check waits for a product too' );
		$this->assertFalse( $checks['viewport_meta']['no_product'], 'A home page check does not need a product' );

		$product = WC_Helper_Product::create_simple_product();
		$this->serve( array( self::path_of( $product ) => null ) );
		$checks = array_column( $this->sut->all(), null, 'id' );
		$this->assertNull( $checks['canonical_url']['ok'] );
		$this->assertFalse( $checks['canonical_url']['no_product'], 'The product page timed out: that is couldn\'t check' );
		$this->assertNull( $checks['product_jsonld_present']['ok'] );
		$this->assertFalse( $checks['product_jsonld_present']['no_product'], 'A JSON-LD check on a page that timed out is couldn\'t check' );
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
	 * @testdox With plain permalinks, /robots.txt answers with a page and the sitemap is at /?sitemap=index: both checks still run.
	 */
	public function test_plain_permalinks(): void {
		$this->set_permalink_structure( '' );
		// The shop page redirects to the product archive only when the product type has one.
		$product_type = get_post_type_object( 'product' );
		$had_archive  = $product_type ? $product_type->has_archive : false;
		if ( $product_type ) {
			$product_type->has_archive = true;
		}
		try {
			$product = \WC_Helper_Product::create_simple_product();
			$shop    = wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_title'  => 'Shop',
				)
			);
			update_option( 'woocommerce_shop_page_id', $shop );
			$page = array( 200, '<!doctype html><title>Shop</title>' );
			$this->serve(
				array(
					'/robots.txt'             => $page,
					'/'                       => $page,
					'/?post_type=product'     => $page,
					self::path_of( $product ) => $page,
					'/?sitemap=index'         => array( 200, '<sitemapindex><sitemap><loc>' . home_url( '/?sitemap=posts&amp;sitemap-subtype=product_cat&amp;paged=1' ) . '</loc></sitemap></sitemapindex>' ),
					'/?sitemap=posts&sitemap-subtype=product_cat&paged=1' => array( 200, '<urlset><url><loc>' . home_url( '/?product=beanie' ) . '</loc><lastmod>2026-11-01</lastmod></url></urlset>' ),
					"/?page_id=$shop"         => array( 302, '', array( 'location' => home_url( '/?post_type=product' ) ) ),
				)
			);

			$results = $this->results();
			$this->assertTrue( $results['sitemap_lists_products'] );
			$this->assertTrue( $results['sitemap_lastmod'] );
			$this->assertTrue( $results['pages_public'] );

			$this->assertFalse( $this->names_products_in( '<urlset><url><loc>' . home_url( '/?product_cat=hats' ) . '</loc></url></urlset>' ) );
			$this->assertTrue( $this->names_products_in( '<sitemapindex><sitemap><loc>' . home_url( '/?sitemap=posts&amp;sitemap-subtype=product&amp;paged=1' ) . '</loc></sitemap></sitemapindex>' ) );
			$this->assertFalse( $this->names_products_in( '<sitemapindex><sitemap><loc>' . home_url( '/?sitemap=taxonomies&amp;sitemap-subtype=product_brand&amp;paged=1' ) . '</loc></sitemap></sitemapindex>' ) );
			$this->assertTrue( $this->names_products_in( '<urlset><url><loc>' . home_url( '/?product=beanie&amp;lang=fr' ) . '</loc></url></urlset>' ) );
		} finally {
			if ( $product_type ) {
				$product_type->has_archive = $had_archive;
			}
		}
	}

	/**
	 * @testdox With plain permalinks, a page at /robots.txt counts as no robots file, so a missing sitemap fails.
	 */
	public function test_plain_permalinks_html_robots_is_no_file(): void {
		$this->set_permalink_structure( '' );
		$this->serve( array( '/robots.txt' => array( 200, '<!doctype html><title>Home</title>' ) ) );

		$this->assertFalse( $this->results()['sitemap_lists_products'] );
	}

	/**
	 * @testdox With plain permalinks and no product archive, the shop page itself is what gets checked.
	 */
	public function test_plain_permalinks_shop_without_archive(): void {
		$this->set_permalink_structure( '' );
		$product_type = get_post_type_object( 'product' );
		$had_archive  = $product_type ? $product_type->has_archive : false;
		try {
			$shop = wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_title'  => 'Shop',
				)
			);
			update_option( 'woocommerce_shop_page_id', $shop );
			// A block theme registers a product archive. This case is the shop page with that archive off.
			if ( $product_type ) {
				$product_type->has_archive = false;
			}
			$this->serve(
				array(
					'/'                   => array( 200, '<!doctype html><title>Home</title>' ),
					"/?page_id=$shop"     => array( 302, '', array( 'location' => site_url( '/wp-login.php' ) ) ),
					'/wp-login.php'       => array( 200, '<html>Log in</html>' ),
					'/?post_type=product' => array( 200, '<!doctype html><title>Shop</title>' ),
				)
			);

			$this->assertFalse( $this->results()['pages_public'] );
		} finally {
			if ( $product_type ) {
				$product_type->has_archive = $had_archive;
			}
		}
	}

	/**
	 * @testdox With plain permalinks, the shop as the front page is not replaced by the product archive.
	 */
	public function test_plain_permalinks_front_page_shop(): void {
		$this->set_permalink_structure( '' );
		$product_type = get_post_type_object( 'product' );
		$had_archive  = $product_type ? $product_type->has_archive : false;
		if ( $product_type ) {
			$product_type->has_archive = true;
		}
		try {
			$shop = wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_title'  => 'Shop',
				)
			);
			update_option( 'woocommerce_shop_page_id', $shop );
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $shop );
			$this->serve(
				array(
					'/'                   => array( 200, '<!doctype html><title>Home</title>' ),
					'/?post_type=product' => array( 302, '', array( 'location' => site_url( '/wp-login.php' ) ) ),
					'/wp-login.php'       => array( 200, '<html>Log in</html>' ),
				)
			);

			$this->assertTrue( $this->results()['pages_public'] );
		} finally {
			if ( $product_type ) {
				$product_type->has_archive = $had_archive;
			}
		}
	}

	/**
	 * @testdox With plain permalinks, a custom shop permalink is the page that is checked.
	 */
	public function test_plain_permalinks_custom_shop_permalink(): void {
		$this->set_permalink_structure( '' );
		$shop = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Shop',
			)
		);
		update_option( 'woocommerce_shop_page_id', $shop );
		add_filter(
			'woocommerce_get_shop_page_permalink',
			static function () {
				return home_url( '/?custom-shop=1' );
			}
		);
		$this->serve(
			array(
				'/'                   => array( 200, '<!doctype html><title>Home</title>' ),
				'/?custom-shop=1'     => array( 302, '', array( 'location' => site_url( '/wp-login.php' ) ) ),
				'/wp-login.php'       => array( 200, '<html>Log in</html>' ),
				'/?post_type=product' => array( 200, '<!doctype html><title>Shop</title>' ),
			)
		);

		$this->assertFalse( $this->results()['pages_public'] );
	}

	/**
	 * Whether the sitemap check passes when /?sitemap=index serves this XML and nothing else answers.
	 *
	 * @param string $xml Sitemap XML.
	 */
	private function names_products_in( string $xml ): bool {
		remove_all_filters( 'pre_http_request' );
		$this->serve( array( '/?sitemap=index' => array( 200, $xml ) ) );
		return true === $this->results()['sitemap_lists_products'];
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
	 * @testdox The shipping and buy action checks match the JSON-LD WooCommerce prints for each product state.
	 *
	 * Shipping details, then the buy action. A virtual product still gets a buy action. An out-of-stock
	 * product still gets shipping details. A variable product page gets neither.
	 *
	 * @testWith ["shipped", [true, true]]
	 *           ["virtual", [null, true]]
	 *           ["out_of_stock", [true, null]]
	 *           ["variable", [null, null]]
	 *
	 * @param string                $state    Product state.
	 * @param array<int, bool|null> $expected Shipping check, then the buy action check.
	 */
	public function test_core_jsonld_matches_what_woocommerce_prints( string $state, array $expected ): void {
		$zone = new \WC_Shipping_Zone();
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );
		$product = 'variable' === $state ? WC_Helper_Product::create_variation_product() : WC_Helper_Product::create_simple_product();
		if ( 'virtual' === $state ) {
			$product->set_virtual( true );
		} elseif ( 'out_of_stock' === $state ) {
			$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		}
		$product->save();
		if ( $product instanceof \WC_Product_Variable ) {
			\WC_Product_Variable::sync( $product );
		}
		$product = wc_get_product( $product->get_id() );
		$this->serve( array( self::path_of( $product ) => array( 200, self::page_with( array( self::core_product_jsonld( $product ) ) ) ) ) );
		$results = $this->results();

		$this->assertSame(
			$expected,
			array( $results['product_shipping_schema'], $results['product_buy_action_schema'] ),
			'These checks disagree with the JSON-LD WooCommerce printed: their copy of the print conditions has drifted from WC_Structured_Data'
		);
		$zone->delete();
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
	 * @testdox The sampled product is one the scan checks: a search-only product is sampled before an older shop-only one, a newer Hidden product is not.
	 */
	public function test_product_checks_sample_checked_products(): void {
		$shop_only = WC_Helper_Product::create_simple_product();
		$shop_only->set_catalog_visibility( 'catalog' );
		$shop_only->set_image_id( self::factory()->attachment->create_object( array( 'file' => 'shop.jpg' ) ) );
		$shop_only->set_date_created( time() - 2 * HOUR_IN_SECONDS );
		$shop_only->save();
		$search_only = WC_Helper_Product::create_simple_product();
		$search_only->set_catalog_visibility( 'search' );
		$search_only->set_image_id( self::factory()->attachment->create_object( array( 'file' => 'image.jpg' ) ) );
		$search_only->set_date_created( time() - HOUR_IN_SECONDS );
		$search_only->save();
		$search_only = wc_get_product( $search_only->get_id() );
		$hidden      = WC_Helper_Product::create_simple_product();
		$hidden->set_catalog_visibility( 'hidden' );
		$hidden->save();
		$shop_only = wc_get_product( $shop_only->get_id() );
		$this->serve(
			array(
				self::path_of( $shop_only )   => array( 200, self::page_with( array( self::core_product_jsonld( $shop_only ) ) ) ),
				self::path_of( $search_only ) => array( 200, self::page_with( array( self::core_product_jsonld( $search_only ) ) ) ),
				self::path_of( $hidden )      => array( 200, self::page_with( array() ) ),
			)
		);

		$this->assertSame( array( true, true, true ), $this->product_results(), 'The Hidden product\'s page, without JSON-LD, is not read' );
		$sampled = array_map( static fn( \WC_Product $product ): int => $product->get_id(), $this->sut->responses()->products() );
		$this->assertSame( array( $search_only->get_id(), $shop_only->get_id() ), $sampled, 'The newest checked product first' );
	}

	/**
	 * @testdox A page check reads more than the newest product page, and names the product whose page failed.
	 */
	public function test_page_checks_find_a_failure_past_the_newest_product(): void {
		$older = WC_Helper_Product::create_simple_product();
		$older->set_date_created( time() - DAY_IN_SECONDS );
		$older->save();
		$newest = wc_get_product( WC_Helper_Product::create_simple_product()->get_id() );
		$this->serve(
			array(
				self::path_of( $newest ) => array( 200, self::page_with( array( self::core_product_jsonld( $newest ) ) ) ),
				self::path_of( $older )  => array( 200, self::page_with( array() ) ),
			)
		);

		$checks = array_column( $this->sut->all(), null, 'id' );
		$this->assertFalse( $checks['product_jsonld_present']['ok'] );
		$this->assertSame( $older->get_id(), $checks['product_jsonld_present']['product'] );
		$this->assertSame( 0, $checks['llms_txt_present']['product'], 'A check that reads no product page names none' );
	}

	/**
	 * @testdox With sales, the best seller's page is read first, and a check that fails there names it.
	 */
	public function test_page_checks_read_the_best_seller_first_and_stop_at_a_failure(): void {
		$best = WC_Helper_Product::create_simple_product();
		$best->set_date_created( time() - DAY_IN_SECONDS );
		$best->save();
		$newest    = WC_Helper_Product::create_simple_product();
		$requested = array();
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( &$requested ) {
				$requested[] = $url;
				return $pre;
			},
			1,
			3
		);
		$this->serve(
			array(
				self::path_of( $best )   => array( 200, self::page_with( array() ) ),
				self::path_of( $newest ) => array( 200, self::page_with( array() ) ),
			)
		);

		$responses = $this->sut->responses();
		$responses->use_sales(
			array(
				$newest->get_id() => 10.0,
				$best->get_id()   => 250.0,
			)
		);
		$checks = array_column( $this->sut->all_for( $responses ), null, 'id' );

		$this->assertFalse( $checks['product_jsonld_present']['ok'] );
		$this->assertSame( $best->get_id(), $checks['product_jsonld_present']['product'], 'The best seller is read first' );
		$product_pages = array_values( array_intersect( $requested, array( (string) $best->get_permalink(), (string) $newest->get_permalink() ) ) );
		$this->assertSame( (string) $best->get_permalink(), $product_pages[0] ?? '', 'The best seller\'s page is loaded before the newest' );
	}

	/**
	 * @testdox A check of the home and product pages names the product only when its page failed, not when the home page did.
	 */
	public function test_failure_on_the_home_page_names_no_product(): void {
		$older = WC_Helper_Product::create_simple_product();
		$older->set_date_created( time() - DAY_IN_SECONDS );
		$older->save();
		$newest   = WC_Helper_Product::create_simple_product();
		$viewport = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head><body></body></html>';
		$bare     = '<!doctype html><html lang="en"><head><meta charset="utf-8"></head><body></body></html>';
		$this->serve(
			array(
				'/'                      => array( 200, $viewport ),
				self::path_of( $newest ) => array( 200, $viewport ),
				self::path_of( $older )  => array( 200, $bare ),
			)
		);
		$checks = array_column( $this->sut->all(), null, 'id' );
		$this->assertFalse( $checks['viewport_meta']['ok'], 'A product page past the newest is read' );
		$this->assertSame( $older->get_id(), $checks['viewport_meta']['product'] );
		$this->assertTrue( $checks['html_lang_and_charset']['ok'] );

		remove_all_filters( 'pre_http_request' );
		$this->serve(
			array(
				'/'                      => array( 200, $bare ),
				self::path_of( $newest ) => array( 200, $viewport ),
				self::path_of( $older )  => array( 200, $viewport ),
			)
		);
		$checks = array_column( $this->sut->all(), null, 'id' );
		$this->assertFalse( $checks['viewport_meta']['ok'] );
		$this->assertSame( 0, $checks['viewport_meta']['product'], 'The home page failed, so no product is named' );
	}

	/**
	 * @testdox A page check stops at the first product page that fails: the next sampled product is not read.
	 */
	public function test_page_check_stops_at_the_first_failure(): void {
		for ( $i = 0; $i < 3; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_date_created( time() - ( $i + 1 ) * HOUR_IN_SECONDS );
			$product->save();
		}
		$responses = $this->sut->responses();
		$sample    = array_map( static fn( \WC_Product $product ): int => $product->get_id(), $responses->products() );
		$read      = array();
		$test      = static function ( StoreResponses $responses ) use ( &$read, $sample ): ?bool {
			$product = $responses->product();
			$read[]  = null === $product ? 0 : $product->get_id();
			// The second sampled product's page fails; without a product page the check passes.
			return null === $product || $product->get_id() !== $sample[1];
		};
		$on_sample = new \ReflectionMethod( StoreOutputChecks::class, 'on_sample' );
		$on_sample->setAccessible( true );
		$failed = null;
		$ok     = $on_sample->invokeArgs( null, array( $test, $responses, &$failed ) );

		$this->assertFalse( $ok );
		$this->assertSame( $sample[1], $failed->get_id() );
		$this->assertSame( array( $sample[0], $sample[1], 0 ), $read, 'The first two pages, then once without a product page; the third is never read' );
	}

	/**
	 * @testdox On a larger store the sample is the newest product, then products picked the same way on every scan, not only the newest ones.
	 */
	public function test_sample_is_the_newest_product_then_steady_picks(): void {
		$ids = array();
		for ( $i = 0; $i < 30; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_date_created( time() - ( $i + 1 ) * HOUR_IN_SECONDS );
			$product->save();
			$ids[] = $product->get_id();
		}
		$sampled = array_map( static fn( \WC_Product $product ): int => $product->get_id(), $this->sut->responses()->products() );

		$this->assertCount( StoreResponses::PRODUCT_SAMPLE_SIZE, $sampled );
		$this->assertSame( $ids[0], $sampled[0], 'The newest product first' );
		$this->assertSame( $sampled, array_map( static fn( \WC_Product $product ): int => $product->get_id(), $this->sut->responses()->products() ), 'The same picks on the next scan' );
		$this->assertNotSame( array_slice( $ids, 0, StoreResponses::PRODUCT_SAMPLE_SIZE ), $sampled, 'Not only the five newest' );
	}

	/**
	 * @testdox The barcode and brand checks are skipped, not couldn't check, when no sampled product has a barcode or brand; one that has it is checked.
	 */
	public function test_identifier_checks_skip_when_no_sampled_product_has_one(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product = wc_get_product( $product->get_id() );
		$this->serve( array( self::path_of( $product ) => array( 200, self::page_with( array( self::core_product_jsonld( $product ) ) ) ) ) );

		$checks = array_column( $this->sut->all(), null, 'id' );
		$this->assertNull( $checks['product_gtin_jsonld']['ok'] );
		$this->assertTrue( $checks['product_gtin_jsonld']['skipped'], 'No barcode to look for' );
		$this->assertTrue( $checks['product_brand_jsonld']['skipped'], 'No brand to look for' );
		$this->assertFalse( $checks['product_jsonld_present']['skipped'], 'Other checks are never skipped' );

		$product->set_global_unique_id( '4006381333931' );
		$product->save();
		$plugin = array(
			'@type' => 'Product',
			'name'  => $product->get_name(),
		);
		remove_all_filters( 'pre_http_request' );
		$this->serve( array( self::path_of( $product ) => array( 200, self::page_with( array( $plugin ) ) ) ) );

		$checks = array_column( $this->sut->all(), null, 'id' );
		$this->assertFalse( $checks['product_gtin_jsonld']['ok'], 'The barcode is missing from the page' );
		$this->assertFalse( $checks['product_gtin_jsonld']['skipped'] );
	}

	/**
	 * @testdox The reviews check reads the sampled products that have ratings, the best seller first, and names the one whose page leaves its rating out.
	 */
	public function test_reviews_check_reads_the_rated_products_of_the_sample(): void {
		update_option( 'woocommerce_enable_reviews', 'yes' );
		update_option( 'woocommerce_enable_review_rating', 'yes' );
		$best = WC_Helper_Product::create_simple_product();
		$best->set_date_created( time() - DAY_IN_SECONDS );
		$best->save();
		update_post_meta( $best->get_id(), '_wc_average_rating', '4.50' );
		update_post_meta( $best->get_id(), '_wc_review_count', 2 );
		$newest = WC_Helper_Product::create_simple_product();
		$this->serve(
			array(
				self::path_of( $best )   => array(
					200,
					self::page_with(
						array(
							array(
								'@type' => 'Product',
								'name'  => $best->get_name(),
							),
						)
					),
				),
				self::path_of( $newest ) => array(
					200,
					self::page_with(
						array(
							array(
								'@type' => 'Product',
								'name'  => $newest->get_name(),
							),
						)
					),
				),
			)
		);
		$responses = $this->sut->responses();
		$responses->use_sales( array( $best->get_id() => 90.0 ) );
		$checks = array_column( $this->sut->all_for( $responses ), null, 'id' );

		$this->assertFalse( $checks['product_review_markup']['ok'] );
		$this->assertSame( $best->get_id(), $checks['product_review_markup']['product'] );
		$this->assertTrue( $responses->starts_with_best_seller() );
	}

	/**
	 * @testdox WooCommerce's own output passes the shipping and buy action checks. Products it gives no link or shipping details are skipped, not failed.
	 */
	public function test_shipping_and_buy_action_follow_woocommerce_output(): void {
		update_option( 'woocommerce_allowed_countries', 'all' );
		update_option( 'woocommerce_ship_to_countries', '' );
		$zone = new \WC_Shipping_Zone();
		$zone->set_zone_name( 'United States' );
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );
		$product = wc_get_product( WC_Helper_Product::create_simple_product()->get_id() );
		$result  = function () use ( $product ): array {
			remove_all_filters( 'pre_http_request' );
			$this->serve( array( self::path_of( $product ) => array( 200, self::page_with( array( self::core_product_jsonld( $product ) ) ) ) ) );
			$checks = array_column( $this->sut->all(), 'ok', 'id' );
			return array( $checks['product_shipping_schema'], $checks['product_buy_action_schema'] );
		};

		$this->assertSame( array( true, true ), $result() );

		remove_all_filters( 'pre_http_request' );
		$this->serve(
			array(
				self::path_of( $product ) => array(
					200,
					self::page_with(
						array(
							array(
								'@type'  => 'Product',
								'name'   => $product->get_name(),
								'offers' => array(
									'@type'        => 'Offer',
									'price'        => '10',
									'availability' => 'https://schema.org/InStock',
								),
							),
						)
					),
				),
			)
		);
		$checks = array_column( $this->sut->all(), null, 'id' );
		$this->assertFalse( $checks['product_shipping_schema']['ok'], 'Product details from elsewhere, without shipping' );
		$this->assertFalse( $checks['product_buy_action_schema']['ok'] );
		$this->assertSame( $product->get_id(), $checks['product_buy_action_schema']['product'] );

		$product->set_stock_status( 'outofstock' );
		$product->save();
		$this->assertNull( $result()[1], 'WooCommerce gives an out of stock product no link' );

		update_option( 'woocommerce_ship_to_countries', 'disabled' );
		$this->assertNull( $result()[0], 'Shipping off: there are no shipping details to give' );
		$zone->delete();
	}

	/**
	 * @testdox The shipping check skips a variable product, which WooCommerce gives shipping details only for a selected variation.
	 */
	public function test_shipping_check_skips_variable_products(): void {
		$variable = wc_get_product( WC_Helper_Product::create_variation_product()->get_id() );
		$this->serve( array( self::path_of( $variable ) => array( 200, self::page_with( array( self::core_product_jsonld( $variable ) ) ) ) ) );

		$this->assertNull( array_column( $this->sut->all(), 'ok', 'id' )['product_shipping_schema'] );
	}

	/**
	 * @testdox The sample holds at most five products, each once, with priced products first.
	 */
	public function test_product_sample_is_small_and_prefers_priced_products(): void {
		$unpriced = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '' ) );
		$unpriced->set_price( '' );
		$unpriced->save();
		for ( $i = 0; $i < 7; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_date_created( time() - ( $i + 1 ) * HOUR_IN_SECONDS );
			$product->save();
		}
		$responses = $this->sut->responses();
		$responses->use_sales( array( $unpriced->get_id() => 99.0 ) );
		$ids = array_map( static fn( \WC_Product $product ): int => $product->get_id(), $responses->products() );

		$this->assertCount( StoreResponses::PRODUCT_SAMPLE_SIZE, $ids );
		$this->assertSame( $ids, array_values( array_unique( $ids ) ) );
		$this->assertNotContains( $unpriced->get_id(), $ids, 'Priced products fill the sample before one without a price, even a best seller' );
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
	 * Results and conflict flags of the GTIN and brand checks after serving one product page.
	 *
	 * @param \WC_Product              $product Product whose page is served.
	 * @param array<int, array|string> $blocks  JSON-LD blocks on the page.
	 * @return array<string, bool|string|null>
	 */
	private function served_identifiers( \WC_Product $product, array $blocks ): array {
		remove_all_filters( 'pre_http_request' );
		$this->serve( array( self::path_of( $product ) => array( 200, self::page_with( $blocks ) ) ) );
		$checks = array_column( $this->sut->all(), null, 'id' );
		return array(
			'gtin'           => $checks['product_gtin_jsonld']['ok'],
			'gtin_conflict'  => $checks['product_gtin_jsonld']['conflict'],
			'brand'          => $checks['product_brand_jsonld']['ok'],
			'brand_conflict' => $checks['product_brand_jsonld']['conflict'],
			'brand_seen_at'  => $checks['product_brand_jsonld']['seen_at'],
		);
	}

	/**
	 * @testdox The GTIN check passes when the page gives a valid GTIN from any source, and flags a likely conflict only when the product's own valid GTIN is missing or replaced.
	 */
	public function test_served_gtin(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_global_unique_id( '4006381333931' );
		$product->save();
		$plugin = array(
			'@type' => 'Product',
			'name'  => $product->get_name(),
		);

		$this->assertTrue( $this->served_identifiers( $product, array( self::core_product_jsonld( $product ) ) )['gtin'], 'WooCommerce prints the GTIN' );

		$result = $this->served_identifiers( $product, array( $plugin ) );
		$this->assertFalse( $result['gtin'], 'A plugin replaced the JSON-LD without the GTIN' );
		$this->assertTrue( $result['gtin_conflict'] );

		$this->assertFalse( $this->served_identifiers( $product, array( $plugin + array( 'gtin13' => '4006381333932' ) ) )['gtin'], 'Another, invalid GTIN' );
		$this->assertTrue( $this->served_identifiers( $product, array( $plugin + array( 'gtin13' => '4006381-333931' ) ) )['gtin'], 'Formatting alone is no conflict' );
		$this->assertTrue( $this->served_identifiers( $product, array( $plugin + array( 'offers' => array( 'gtin' => 4006381333931 ) ) ) )['gtin'], 'A GTIN on the offer, as a number' );
		$this->assertTrue( $this->served_identifiers( $product, array( $plugin + array( 'gtin13' => array( '4006381333931' ) ) ) )['gtin'], 'A GTIN given as a list' );
		$upsell = array(
			'@type'  => 'Product',
			'name'   => 'Another product',
			'gtin13' => '9780306406157',
		);
		$this->assertFalse( $this->served_identifiers( $product, array( $plugin, $upsell ) )['gtin'], 'Another product\'s GTIN on the page doesn\'t count' );
		$this->assertFalse( $this->served_identifiers( $product, array( $upsell ) )['gtin'], 'An upsell alone is not this product' );
		$permalink = (string) $product->get_permalink();
		$flipped   = str_starts_with( $permalink, 'https:' ) ? 'http:' . substr( $permalink, strlen( 'https:' ) ) : 'https:' . substr( $permalink, strlen( 'http:' ) );
		$this->assertTrue(
			$this->served_identifiers(
				$product,
				array(
					array(
						'@type'  => 'Product',
						'name'   => 'Sold as something else',
						'url'    => $flipped . '#product',
						'gtin13' => '4006381333931',
					),
				)
			)['gtin'],
			'A different name still counts when the url is this product'
		);
		$this->assertTrue(
			$this->served_identifiers(
				$product,
				array(
					array(
						'@type'  => 'Product',
						'name'   => 'Sold as something else',
						'@id'    => $permalink . '#product',
						'gtin13' => '4006381333931',
					),
				)
			)['gtin'],
			'An @id of this product counts too'
		);
		$group = $product->get_permalink() . '#group';
		$this->assertTrue(
			$this->served_identifiers(
				$product,
				array(
					array(
						'@type' => 'ProductGroup',
						'name'  => $product->get_name(),
						'@id'   => $group,
					),
					array(
						'@type'       => 'Product',
						'name'        => 'Red',
						'isVariantOf' => array( '@id' => $group ),
						'gtin13'      => '4006381333931',
					),
				)
			)['gtin'],
			'A variant linked with isVariantOf counts'
		);
		$this->assertTrue(
			$this->served_identifiers(
				$product,
				array(
					array(
						'@type'      => 'ProductGroup',
						'name'       => $product->get_name(),
						'hasVariant' => array( array( '@id' => '#red' ) ),
					),
					array(
						'@type'  => 'Product',
						'name'   => 'Mug - Red',
						'@id'    => '#red',
						'gtin13' => '4006381333931',
					),
				)
			)['gtin'],
			'A variant named only by hasVariant @id counts'
		);

		$product->set_global_unique_id( '' );
		$product->save();
		$this->assertTrue( $this->served_identifiers( $product, array( $plugin + array( 'gtin' => '4006381333931' ) ) )['gtin'], 'A plugin provides the GTIN the product lacks' );
		$this->assertNull( $this->served_identifiers( $product, array( $plugin ) )['gtin'], 'Neither has one: missing_gtin reports it' );
		$this->assertNull( $this->served_identifiers( $product, array( $plugin + array( 'gtin' => '123' ) ) )['gtin'], 'An invalid GTIN from elsewhere isn\'t a conflict when WooCommerce has none to print' );

		$product->set_global_unique_id( '4006381333932' );
		$product->save();
		$this->assertNull( $this->served_identifiers( $product, array( self::core_product_jsonld( $product ) ) )['gtin'], 'The product\'s own invalid GTIN: invalid_gtin reports it' );

		$product->set_virtual( true );
		$product->save();
		$this->assertNull( $this->served_identifiers( $product, array( $plugin ) )['gtin'], 'A virtual product needs no GTIN' );
	}

	/**
	 * @testdox The brand check passes when the page gives a brand from any source, and fails only when the product has a WooCommerce brand the page doesn't give.
	 */
	public function test_served_brand(): void {
		$product = WC_Helper_Product::create_simple_product();
		$plugin  = array(
			'@type' => 'Product',
			'name'  => $product->get_name(),
		);

		$this->assertNull( $this->served_identifiers( $product, array( $plugin ) )['brand'], 'No brand anywhere: missing_brand reports it' );
		$this->assertTrue( $this->served_identifiers( $product, array( $plugin + array( 'brand' => 'Acme' ) ) )['brand'], 'A plugin provides the brand, as text' );

		$attribute = new \WC_Product_Attribute();
		$attribute->set_name( 'Brand' );
		$attribute->set_options( array( 'Acme' ) );
		$product->set_attributes( array( $attribute ) );
		$product->save();
		$this->assertNull( $this->served_identifiers( $product, array( $plugin ) )['brand'], 'WooCommerce doesn\'t print a brand attribute, so it isn\'t a conflict' );

		wp_set_object_terms( $product->get_id(), 'Acme', 'product_brand' );
		$result = $this->served_identifiers( $product, array( $plugin ) );
		$this->assertFalse( $result['brand'], 'The WooCommerce brand is missing from the page' );
		$this->assertTrue( $result['brand_conflict'] );
		$this->assertSame( (string) $product->get_permalink(), $result['brand_seen_at'] );
		$upsell = array(
			'@type' => 'Product',
			'name'  => 'Another product',
			'brand' => 'Other',
		);
		$this->assertFalse( $this->served_identifiers( $product, array( $plugin, $upsell ) )['brand'], 'Another product\'s brand on the page doesn\'t count' );
		$this->assertTrue( $this->served_identifiers( $product, array( self::core_product_jsonld( wc_get_product( $product->get_id() ) ) ) )['brand'], 'WooCommerce prints its brand' );
		$this->assertTrue(
			$this->served_identifiers(
				$product,
				array(
					$plugin + array(
						'brand' => array(
							array(
								'@type' => 'Brand',
								'name'  => 'Acme',
							),
						),
					),
				)
			)['brand'],
			'A list of Brand items'
		);
		$this->assertFalse( $this->served_identifiers( $product, array( $plugin + array( 'brand' => array( 'name' => ' ' ) ) ) )['brand'], 'An empty brand name' );
	}

	/**
	 * @testdox A ProductGroup whose offers sit on its variants passes the product checks. One whose variants have no offer fails the required fields and offer checks.
	 */
	public function test_product_group_offers_on_variants(): void {
		$product = WC_Helper_Product::create_simple_product();
		$path    = self::path_of( $product );
		$group   = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'ProductGroup',
			'name'       => 'Dummy Product',
			'image'      => 'https://example.org/image.jpg',
			'variesBy'   => 'https://schema.org/color',
			'hasVariant' => array(
				array(
					'@type'  => 'Product',
					'name'   => 'Dummy Product - Red',
					'offers' => array(
						'@type'        => 'Offer',
						'price'        => '10.00',
						'availability' => 'https://schema.org/InStock',
					),
				),
			),
		);
		$this->serve( array( $path => array( 200, self::page_with( array( $group ) ) ) ) );
		$this->assertSame( array( true, true, true ), $this->product_results() );

		remove_all_filters( 'pre_http_request' );
		unset( $group['hasVariant'][0]['offers'] );
		$this->serve( array( $path => array( 200, self::page_with( array( $group ) ) ) ) );
		$this->assertSame( array( true, true, false ), $this->product_results(), 'A missing offer fails only the price and stock check' );
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

		foreach ( array( '$10.00', '€10', '10,00 kr.', "10\u{202F}000.00", 'R$ 10,00', 'US$10', 'S/. 10', 'EUR 10,-' ) as $text ) {
			remove_all_filters( 'pre_http_request' );
			$no_price['offers']['price'] = $text;
			$this->serve( array( $path => array( 200, self::page_with( array( $no_price ) ) ) ) );
			$this->assertSame( array( true, true, true ), $this->product_results(), "A price with a symbol or spaces counts: $text" );
		}

		foreach ( array( 'call 555', 'Free 2 day' ) as $text ) {
			remove_all_filters( 'pre_http_request' );
			$no_price['offers']['price'] = $text;
			$this->serve( array( $path => array( 200, self::page_with( array( $no_price ) ) ) ) );
			$this->assertSame( array( true, true, false ), $this->product_results(), "Text with a number in it is not a price: $text" );
		}
	}

	/**
	 * @testdox JSON-LD is read from a script whose type attribute names it, also when another attribute holds a >; a data-type attribute does not count.
	 */
	public function test_jsonld_script_tag(): void {
		$block = '{"@type":"Product","name":"Mug"}';
		$this->assertCount( 1, JsonLd::items( '<script data-x="a>b" type="application/ld+json">' . $block . '</script>', 'Product' ) );
		$this->assertCount( 0, JsonLd::items( '<script data-type="application/ld+json">' . $block . '</script>', 'Product' ) );
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
		$responses = array( self::path_of( $open ) => array( 200, self::page_with( array( self::core_product_jsonld( $open ) ) ) ) );
		// More locked products than the five newest that are looked at.
		for ( $i = 0; $i < 6; $i++ ) {
			$locked = WC_Helper_Product::create_simple_product();
			$locked->set_post_password( 'secret' );
			$locked->save();
			$responses[ self::path_of( $locked ) ] = array( 200, self::page_with( array() ) );
		}
		$this->serve( $responses );

		$this->assertSame( array( true, true, true ), $this->product_results() );
	}

	/**
	 * @testdox A check the rules file turns off doesn't run and is left out of the results.
	 */
	public function test_a_check_the_rules_file_turns_off_is_left_out(): void {
		update_option(
			RemoteRules::OPTION,
			array(
				'version'      => '2026-11-15',
				'checks'       => array(),
				'store_checks' => array( 'llms_txt_present' => array( 'off' => true ) ),
			),
			false
		);
		$this->serve( array() );

		$results = $this->results();

		$this->assertArrayNotHasKey( 'llms_txt_present', $results );
		$this->assertArrayHasKey( 'llms_txt_valid', $results, 'Only the named check is off' );
		$this->assertArrayHasKey( 'canonical_url', $results );
	}
}
