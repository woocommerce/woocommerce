<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
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
	 * Answer requests by path from the home URL; anything else fails like a timeout.
	 *
	 * @param array<string, array{0:int,1:string}> $responses Status and body by path.
	 */
	private function serve( array $responses ): void {
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( $responses ) {
				unset( $pre, $args );
				$path = substr( $url, strlen( untrailingslashit( home_url() ) ) );
				if ( ! isset( $responses[ $path ] ) ) {
					return new \WP_Error( 'http_request_failed', 'Timed out' );
				}
				return array(
					'headers'  => array(),
					'body'     => $responses[ $path ][1],
					'response' => array(
						'code'    => $responses[ $path ][0],
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
		$this->serve( array() );

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
		$this->serve( array( '/robots.txt' => array( 200, $robots ) ) );
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
		$this->serve( array() );
		$this->assertNull( $this->results()['sitemap_lists_products'] );
	}
}
