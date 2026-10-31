<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the store checks that read what the store serves to agents: llms.txt,
 * Open Graph and meta tags, organization schema, snippet rules, raw HTML and UCP.
 */
class ServedOutputChecksTest extends WC_Unit_Test_Case {

	private const SERVED_CHECKS = array( 'llms_txt_present', 'llms_txt_valid', 'llms_txt_links_resolve', 'open_graph_tags', 'meta_description', 'canonical_url', 'organization_schema', 'ai_snippet_directives', 'content_in_raw_html' );

	/**
	 * The System Under Test.
	 *
	 * @var StoreOutputChecks
	 */
	private $sut;

	/**
	 * The product whose page the checks read.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Requests made in the current test, as "METHOD path".
	 *
	 * @var array<int, string>
	 */
	private array $requested = array();

	/**
	 * Pretty permalinks, UCP off and one product, as on a live store.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->set_permalink_structure( '/%postname%/' );
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		$this->product = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Blue & Gold Mug' ) );
		$this->sut     = wc_get_container()->get( StoreOutputChecks::class );
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
	 * Answer requests by path from the home URL, or by "POST path". A null response fails like a timeout; any other path is a 404.
	 *
	 * @param array<string, array{0:int,1:string,2?:array<string,string>}|null> $responses Status, body and headers by path.
	 */
	private function serve( array $responses ): void {
		remove_all_filters( 'pre_http_request' );
		$this->requested = array();
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( $responses ) {
				unset( $pre );
				$path              = substr( $url, strlen( untrailingslashit( home_url() ) ) );
				$key               = 'POST' === $args['method'] ? 'POST ' . $path : $path;
				$this->requested[] = $key;
				if ( array_key_exists( $key, $responses ) && null === $responses[ $key ] ) {
					return new \WP_Error( 'http_request_failed', 'Timed out' );
				}
				$response = $responses[ $key ] ?? array( 404, '' );
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
	 * Path of the product's page from the home URL.
	 */
	private function product_path(): string {
		return substr( (string) $this->product->get_permalink(), strlen( untrailingslashit( home_url() ) ) );
	}

	/**
	 * A page as an SEO plugin and WooCommerce print it, with extra head markup.
	 *
	 * @param string $head Extra head markup, after the defaults.
	 * @param string $body Body markup.
	 * @param bool   $tags Whether to print the default Open Graph, description and canonical tags.
	 */
	private static function page( string $head = '', string $body = '', bool $tags = true ): string {
		$defaults = $tags ? '<meta property="og:title" content="Shop"><meta property="og:image" content="https://example.org/a.jpg"><meta name="description" content="Mugs and more"><link rel="canonical" href="https://example.org/">' : '';
		return '<!doctype html><html><head><title>Shop</title>' . $defaults . $head . '</head><body>' . $body . '</body></html>';
	}

	/**
	 * Every response a store that does everything right serves.
	 *
	 * @return array<string, array{0:int,1:string,2?:array<string,string>}>
	 */
	private function good_store(): array {
		$organization = '<script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"WebSite","name":"Shop"},{"@type":"OnlineStore","name":"Shop"}]}</script>';
		return array(
			'/'                   => array( 200, self::page( $organization . '<meta name="robots" content="index, follow, max-snippet:-1">' ) ),
			$this->product_path() => array( 200, self::page( '', '<h1>Blue &amp; Gold Mug</h1><p class="price"><span><bdi><span>&#36;</span>10.00</bdi></span></p>' ) ),
			'/llms.txt'           => array( 200, "# Shop\n\n> Mugs.\n\n- [About](" . home_url( '/about/' ) . ")\n- [Elsewhere](https://example.com/x)\n- [Buy](" . home_url( '/?add-to-cart=1' ) . ")\n" ),
			'/about/'             => array( 200, self::page() ),
		);
	}

	/**
	 * Results of the served checks by id.
	 *
	 * @return array<string, bool|null>
	 */
	private function results(): array {
		return array_intersect_key( array_column( $this->sut->all(), 'ok', 'id' ), array_flip( array_merge( self::SERVED_CHECKS, array( 'ucp_profile', 'ucp_endpoints_live' ) ) ) );
	}

	/**
	 * @testdox On a store that serves everything, every served check passes, links to other sites or with a query string aren't loaded, and UCP checks are left out while UCP is off.
	 */
	public function test_good_store_passes(): void {
		$this->serve( $this->good_store() );

		$results = $this->results();

		$this->assertSame( array_fill_keys( self::SERVED_CHECKS, true ), $results );
		$this->assertNotContains( 'https://example.com/x', $this->requested, 'Links to other sites are not loaded' );
		$this->assertNotContains( '/?add-to-cart=1', $this->requested, 'Links with a query string are not loaded' );
		$this->assertArrayNotHasKey( 'ucp_profile', $results );
	}

	/**
	 * @testdox A store whose pages carry none of the tags fails each check, and a page served for every URL is no llms.txt.
	 */
	public function test_bare_store_fails(): void {
		$this->serve(
			array(
				'/'                   => array( 200, self::page( '', '', false ) ),
				$this->product_path() => array( 200, self::page( '<script>document.write("Blue & Gold Mug")</script>', '<div id="app"></div>', false ) ),
				'/llms.txt'           => array( 200, self::page( '', '', false ) ),
			)
		);

		$this->assertSame(
			array(
				'llms_txt_present'       => false,
				'llms_txt_valid'         => false,
				'llms_txt_links_resolve' => false,
				'open_graph_tags'        => false,
				'meta_description'       => false,
				'canonical_url'          => false,
				'organization_schema'    => false,
				'ai_snippet_directives'  => true,
				'content_in_raw_html'    => false,
			),
			$this->results()
		);
	}

	/**
	 * @testdox llms.txt without a heading fails the format check; a broken store link fails, an unreachable one is couldn't check.
	 */
	public function test_llms_txt(): void {
		$responses              = $this->good_store();
		$responses['/llms.txt'] = array( 200, "Shop\n- [About](" . home_url( '/about/' ) . ")\n- [Old](" . home_url( '/old/' ) . ")\n" );
		$responses['/old/']     = array( 404, '' );
		$this->serve( $responses );
		$results = $this->results();
		$this->assertFalse( $results['llms_txt_valid'] );
		$this->assertFalse( $results['llms_txt_links_resolve'] );

		$responses['/old/'] = null;
		$this->serve( $responses );
		$this->assertNull( $this->results()['llms_txt_links_resolve'] );
		$this->assertContains( '/llms.txt', $this->requested );
		$this->assertTrue( $this->results()['open_graph_tags'], 'A slow link does not stop the other checks' );
	}

	/**
	 * @testdox A nosnippet rule for one crawler, or a max-snippet limit in the X-Robots-Tag header, fails the snippet check.
	 */
	public function test_snippet_directives(): void {
		$responses      = $this->good_store();
		$responses['/'] = array( 200, self::page( '<meta name="googlebot" content="nosnippet">' ) );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ai_snippet_directives'] );

		$responses['/'] = array( 200, self::page(), array( 'X-Robots-Tag' => 'googlebot: max-snippet:50' ) );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ai_snippet_directives'] );
	}

	/**
	 * @testdox With a price only in JSON-LD, the raw HTML check fails.
	 */
	public function test_price_only_in_script(): void {
		$responses                          = $this->good_store();
		$responses[ $this->product_path() ] = array( 200, self::page( '<script type="application/ld+json">{"@type":"Product","offers":{"price":"10.00"}}</script>', '<h1>Blue &amp; Gold Mug</h1>' ) );
		$this->serve( $responses );

		$this->assertFalse( $this->results()['content_in_raw_html'] );

		$responses[ $this->product_path() ] = array( 200, self::page( '', '<h1>Blue &amp; Gold Mug</h1><!-- 10.00 --><p>100</p>' ) );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['content_in_raw_html'], 'A price in a comment, or a longer number, does not count' );
	}

	/**
	 * @testdox The price is matched as the store formats it, here with trailing zeros trimmed.
	 */
	public function test_price_in_store_format(): void {
		add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
		$responses                          = $this->good_store();
		$responses[ $this->product_path() ] = array( 200, self::page( '', '<h1>Blue &amp; Gold Mug</h1><p class="price">&#36;10</p>' ) );
		$this->serve( $responses );

		$this->assertTrue( $this->results()['content_in_raw_html'] );
	}

	/**
	 * @testdox When the home page times out, nothing else is loaded and every served check is couldn't check; with only the home page, the page checks read it alone.
	 */
	public function test_couldnt_check(): void {
		$this->serve( array( '/' => null ) );
		$this->assertSame( array_fill_keys( self::SERVED_CHECKS, null ), $this->results() );
		$this->assertSame( array( '/' ), $this->requested, 'Nothing else is requested when the home page times out' );

		$this->serve( array( '/' => $this->good_store()['/'] ) );
		$results = $this->results();
		$this->assertTrue( $results['open_graph_tags'] );
		$this->assertNull( $results['canonical_url'] );
		$this->assertNull( $results['content_in_raw_html'] );
	}

	/**
	 * @testdox With UCP on, the profile and catalog search checks read the served profile and POST to the endpoint it names.
	 */
	public function test_ucp_checks(): void {
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );
		$endpoint = home_url( '/wp-json/wc/ucp/v1' );
		$path     = substr( $endpoint, strlen( untrailingslashit( home_url() ) ) );
		$service  = array(
			'transport' => 'rest',
			'endpoint'  => $endpoint,
		);
		$profile  = wp_json_encode( array( 'ucp' => array( 'services' => array( 'dev.ucp.shopping' => array( $service ) ) ) ) );
		$search   = 'POST ' . $path . '/catalog/search';

		$responses                     = $this->good_store();
		$responses['/.well-known/ucp'] = array( 200, (string) $profile );
		$responses[ $search ]          = array( 200, '{"ucp":{},"products":[]}' );
		$this->serve( $responses );
		$results = $this->results();
		$this->assertTrue( $results['ucp_profile'] );
		$this->assertTrue( $results['ucp_endpoints_live'] );

		$responses['/.well-known/ucp'] = array( 404, '' );
		$responses[ $search ]          = array( 403, '<html>Blocked</html>' );
		$this->serve( $responses );
		$results = $this->results();
		$this->assertFalse( $results['ucp_profile'] );
		$this->assertFalse( $results['ucp_endpoints_live'], 'Without a profile, WooCommerce\'s endpoint is tried' );
	}
}
