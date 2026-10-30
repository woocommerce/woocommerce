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
	 * @testdox On a stock store that can't load its own pages, robots.txt is read as WordPress serves it and every check passes.
	 */
	public function test_stock_store_passes_without_requests(): void {
		$this->serve( array() );

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
		$this->serve( array() );
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
		$this->serve( array() );
		$this->assertNull( $this->results()['sitemap_lists_products'] );
	}
}
