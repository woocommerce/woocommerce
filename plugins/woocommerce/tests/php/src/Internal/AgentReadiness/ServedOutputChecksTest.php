<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreResponses;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils as UcpUtils;
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
		return array_intersect_key( array_column( $this->sut->all(), 'ok', 'id' ), array_flip( array_merge( self::SERVED_CHECKS, array( 'ucp_profile', 'ucp_profile_version', 'ucp_endpoints_live' ) ) ) );
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
	 * @testdox A failing Open Graph check records which page lacks the tags: the home page, the product page, which it names, or both.
	 */
	public function test_open_graph_names_the_page_without_tags(): void {
		$store = $this->good_store();
		$cases = array(
			'home'    => array( self::page( '', '', false ), $store[ $this->product_path() ][1] ),
			'product' => array( $store['/'][1], self::page( '', '', false ) ),
			'both'    => array( self::page( '', '', false ), self::page( '', '', false ) ),
		);
		foreach ( $cases as $source => $pages ) {
			$store['/']                     = array( 200, $pages[0] );
			$store[ $this->product_path() ] = array( 200, $pages[1] );
			$this->serve( $store );
			$check = array_column( $this->sut->all(), null, 'id' )['open_graph_tags'];
			$this->assertFalse( $check['ok'], $source );
			$this->assertSame( $source, $check['source'], $source );
			$this->assertSame( 'home' === $source ? 0 : $this->product->get_id(), $check['product'], 'The product is named when its page lacks the tags: ' . $source );
		}
	}

	/**
	 * @testdox llms.txt without a heading fails the format check; a broken store link fails, an unreachable or blocked one is couldn't check, and a blocked llms.txt is too.
	 */
	public function test_llms_txt(): void {
		$responses              = $this->good_store();
		$responses['/llms.txt'] = array( 200, "Shop\n- [About](" . home_url( '/about/' ) . ")\n- [Old](" . home_url( '/old/' ) . ")\n" );
		$responses['/old/']     = array( 404, '' );
		$this->serve( $responses );
		$results = $this->results();
		$this->assertFalse( $results['llms_txt_valid'] );
		$this->assertFalse( $results['llms_txt_links_resolve'] );
		$this->assertSame( home_url( '/old/' ), array_column( $this->sut->all(), 'seen_at', 'id' )['llms_txt_links_resolve'], 'The broken link is kept to name it' );

		$responses['/old/'] = null;
		$this->serve( $responses );
		$this->assertNull( $this->results()['llms_txt_links_resolve'] );
		$this->assertContains( '/llms.txt', $this->requested );
		$this->assertTrue( $this->results()['open_graph_tags'], 'A slow link does not stop the other checks' );

		$responses['/llms.txt'] = array( 200, "# Shop\n" . implode( '', array_map( static fn( int $i ): string => "- [Part $i](" . home_url( "/about/#part-$i" ) . ")\n", range( 1, 5 ) ) ) . '- [Old](' . home_url( '/old/' ) . ")\n" );
		$responses['/old/']     = array( 404, '' );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['llms_txt_links_resolve'], 'Links to anchors on one page are one link' );

		$responses['/old/'] = array( 403, 'Blocked' );
		$this->serve( $responses );
		$this->assertNull( $this->results()['llms_txt_links_resolve'], 'A firewall refusing the store its own page is not a broken link' );

		$responses['/llms.txt'] = array( 403, 'Blocked' );
		$this->serve( $responses );
		$results = $this->results();
		$this->assertNull( $results['llms_txt_present'], 'A firewall refusing llms.txt is not a missing llms.txt' );
		$this->assertNull( $results['llms_txt_valid'] );
		$this->assertNull( $results['llms_txt_links_resolve'] );
	}

	/**
	 * Store output checks that see the given WooCommerce llms.txt status, as woocommerce #70741 reports it.
	 *
	 * @param string|null $status  Status, or null without the fallback.
	 * @param string      $section Guidance WooCommerce adds.
	 */
	private static function with_llms_status( ?string $status, string $section = '' ): StoreOutputChecks {
		$sut         = new class() extends StoreOutputChecks {
			/**
			 * The status to report.
			 *
			 * @var array{status:string,section:string}|null
			 */
			public $status;

			/**
			 * The status set by the test.
			 */
			protected function llms_txt_status(): ?array {
				return $this->status;
			}
		};
		$sut->status = null === $status ? null : array(
			'status'  => $status,
			'section' => $section,
		);
		$sut->init( new \Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper() );
		return $sut;
	}

	/**
	 * The llms_txt_present result.
	 *
	 * @param StoreOutputChecks $sut Checks.
	 * @return array<string, mixed>
	 */
	private static function llms_present( StoreOutputChecks $sut ): array {
		return array_column( $sut->all(), null, 'id' )['llms_txt_present'];
	}

	/**
	 * @testdox llms.txt names its source from the response header, a file in the site root, or another provider.
	 */
	public function test_llms_txt_source_when_served(): void {
		$section                = "## Agentic capabilities\n\n- [WooCommerce Store API](" . home_url( '/wp-json/wc/store/v1' ) . ')';
		$responses              = $this->good_store();
		$responses['/llms.txt'] = array( 200, "# Shop\n\nA customized guide.\n", array( 'X-WooCommerce-LLMs-Txt' => '1' ) );
		$this->serve( $responses );

		$this->assertSame( 'woocommerce', self::llms_present( self::with_llms_status( 'unknown', $section . "\n" ) )['source'] );
		$this->assertSame( 'woocommerce', self::llms_present( self::with_llms_status( 'physical_file', $section ) )['source'], 'The served response takes precedence over a local file' );

		$responses['/llms.txt'] = array( 200, "# Shop\n\n" . $section . "\n" );
		$this->serve( $responses );
		$this->assertSame( 'other', self::llms_present( self::with_llms_status( 'unknown', $section ) )['source'], 'Copied guidance does not identify the provider' );
		$this->assertSame( 'file', self::llms_present( self::with_llms_status( 'physical_file', $section ) )['source'] );

		$responses['/llms.txt'] = array( 200, "# Shop\n\n- [About](" . home_url( '/about/' ) . ")\n" );
		$this->serve( $responses );
		$this->assertSame( 'file', self::llms_present( self::with_llms_status( 'physical_file', $section ) )['source'] );
		$this->assertSame( 'other', self::llms_present( self::with_llms_status( 'unknown', $section ) )['source'] );
		$this->assertSame( 'other', self::llms_present( self::with_llms_status( 'disabled' ) )['source'], 'Served while WooCommerce\'s is off' );
		$this->assertSame( '', self::llms_present( self::with_llms_status( null ) )['source'], 'Without the fallback, no source' );

		$subdirectory = static fn( string $url ): string => (string) preg_replace( '#^(https?://[^/]+)#', '$1/shop', $url );
		add_filter( 'home_url', $subdirectory );
		remove_all_filters( 'pre_http_request' );
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) {
				unset( $pre, $args );
				$is_llms = '/llms.txt' === wp_parse_url( $url, PHP_URL_PATH );
				return array(
					'headers'  => new \WpOrg\Requests\Utility\CaseInsensitiveDictionary( $is_llms ? array( 'X-WooCommerce-LLMs-Txt' => '1' ) : array() ),
					'body'     => $is_llms ? "# Shop\n" : self::page(),
					'response' => array(
						'code'    => 200,
						'message' => '',
					),
					'cookies'  => array(),
				);
			},
			10,
			3
		);
		try {
			$result = self::llms_present( self::with_llms_status( 'unknown', $section ) );
			$this->assertTrue( $result['ok'] );
			$this->assertSame( '', $result['source'], 'A host-root response does not identify the subdirectory store\'s provider' );
		} finally {
			remove_filter( 'home_url', $subdirectory );
		}
	}

	/**
	 * @testdox A missing llms.txt says whether WooCommerce's is turned off or doesn't reach the store, and its fix follows; couldn't check has no source.
	 */
	public function test_llms_txt_source_when_missing(): void {
		$responses = $this->good_store();
		unset( $responses['/llms.txt'] );
		$this->serve( $responses );
		$default = self::llms_present( self::with_llms_status( null ) );
		$this->assertSame( '', $default['source'] );

		$disabled = self::llms_present( self::with_llms_status( 'disabled' ) );
		$this->assertFalse( $disabled['ok'] );
		$this->assertSame( 'disabled', $disabled['source'] );
		$this->assertStringContainsString( 'woocommerce_enable_llms_txt', $disabled['fix'] );

		$not_reached = self::llms_present( self::with_llms_status( 'unknown', '## Agentic capabilities' ) );
		$this->assertSame( 'not_reached', $not_reached['source'] );
		$this->assertStringContainsString( 'Ask your host', $not_reached['fix'] );
		$this->assertNotSame( $default['fix'], $not_reached['fix'] );
		$this->assertSame( '', self::llms_present( self::with_llms_status( 'no_rewrite', '## Agentic capabilities' ) )['source'], 'A status it doesn\'t know claims no cause' );

		// A root file that isn't reached still blocks WooCommerce's fallback, so keep the default SEO fix.
		$root_file = self::llms_present( self::with_llms_status( 'physical_file', '## Agentic capabilities' ) );
		$this->assertSame( '', $root_file['source'], 'A root file that isn\'t served is not "WooCommerce will serve one"' );
		$this->assertSame( $default['fix'], $root_file['fix'] );

		$subdirectory = static fn( string $url ): string => (string) preg_replace( '#^(https?://[^/]+)#', '$1/shop', $url );
		add_filter( 'home_url', $subdirectory );
		try {
			$this->assertSame( '', self::llms_present( self::with_llms_status( 'unknown', '## Agentic capabilities' ) )['source'], 'In a subdirectory WooCommerce serves it elsewhere' );
			$subdir_off = self::llms_present( self::with_llms_status( 'disabled' ) );
			$this->assertSame( '', $subdir_off['source'], 'Turned off in a subdirectory still is not this URL' );
			$this->assertSame( $default['fix'], $subdir_off['fix'] );
		} finally {
			remove_filter( 'home_url', $subdirectory );
		}

		$responses              = $this->good_store();
		$responses['/llms.txt'] = array( 403, 'Blocked' );
		$this->serve( $responses );
		$this->assertSame( '', self::llms_present( self::with_llms_status( 'disabled' ) )['source'] );

		$responses['/llms.txt'] = array( 200, self::page( '', '', false ) );
		$this->serve( $responses );
		$page = self::llms_present( self::with_llms_status( 'unknown', '## Agentic capabilities' ) );
		$this->assertSame( '', $page['source'], 'A page at /llms.txt reached the store' );
		$this->assertSame( $default['fix'], $page['fix'] );
		$page_off = self::llms_present( self::with_llms_status( 'disabled' ) );
		$this->assertSame( '', $page_off['source'] );
		$this->assertSame( $default['fix'], $page_off['fix'] );
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
	 * @testdox A price with a currency symbol after it that ends in a period (10,00 kr.) is found in the page.
	 */
	public function test_price_with_symbol_ending_in_period(): void {
		update_option( 'woocommerce_currency', 'DKK' );
		update_option( 'woocommerce_currency_pos', 'right_space' );
		update_option( 'woocommerce_price_decimal_sep', ',' );
		update_option( 'woocommerce_price_thousand_sep', '.' );
		$responses                          = $this->good_store();
		$responses[ $this->product_path() ] = array( 200, self::page( '', '<h1>Blue &amp; Gold Mug</h1><p class="price">10,00&nbsp;kr.</p>' ) );
		$this->serve( $responses );

		$this->assertTrue( $this->results()['content_in_raw_html'] );
	}

	/**
	 * @testdox When the home page times out, also after a redirect, nothing else is loaded and every served check is couldn't check. A product page that doesn't load leaves the page checks couldn't check; with no product, they read the home page alone.
	 */
	public function test_couldnt_check(): void {
		$this->serve( array( '/' => null ) );
		$this->assertSame( array_fill_keys( self::SERVED_CHECKS, null ), $this->results() );
		$this->assertSame( array( '/' ), $this->requested, 'Nothing else is requested when the home page times out' );

		$this->serve(
			array(
				'/'    => array( 301, '', array( 'location' => home_url( '/en/' ) ) ),
				'/en/' => null,
			)
		);
		$this->assertSame( array_fill_keys( self::SERVED_CHECKS, null ), $this->results() );
		$this->assertSame( array( '/', '/en/' ), $this->requested, 'Nothing else is requested when the redirected home page times out' );

		$this->serve( array( '/' => $this->good_store()['/'] ) );
		$results = $this->results();
		$this->assertNull( $results['open_graph_tags'], 'The product page did not load' );
		$this->assertNull( $results['canonical_url'] );
		$this->assertNull( $results['content_in_raw_html'] );

		$this->product->delete( true );
		$this->assertTrue( $this->results()['open_graph_tags'], 'With no product, the home page alone' );
	}

	/**
	 * @testdox With UCP on, the profile check reads the served profile, and the endpoint check POSTs to catalog search, once more when it fails, and keeps what a failed one answered.
	 */
	public function test_ucp_checks(): void {
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );
		$endpoint = home_url( '/wp-json/wc/ucp/v1' );
		$path     = substr( $endpoint, strlen( untrailingslashit( home_url() ) ) );
		$service  = array(
			'transport' => 'rest',
			'endpoint'  => $endpoint,
		);
		$profile  = wp_json_encode(
			array(
				'ucp' => array(
					'version'  => UcpUtils::VERSION,
					'services' => array( 'dev.ucp.shopping' => array( $service ) ),
				),
			)
		);

		$search                         = $path . '/catalog/search';
		$responses                      = $this->good_store();
		$responses['/.well-known/ucp']  = array( 200, (string) $profile );
		$responses[ $path ]             = array( 404, '' );
		$responses[ 'POST ' . $search ] = array( 200, '{"ucp":{"status":"success"},"products":[]}' );
		$this->serve( $responses );
		$results = $this->results();
		$this->assertTrue( $results['ucp_profile'] );
		$this->assertTrue( $results['ucp_profile_version'] );
		$this->assertTrue( $results['ucp_endpoints_live'], 'Catalog search answers even when a GET of the endpoint base does not' );
		$this->assertSame( '', array_column( $this->sut->all(), 'source', 'id' )['ucp_endpoints_live'], 'A passing check names nothing' );
		$this->assertContains( 'POST ' . $search, $this->requested );

		$responses[ 'POST ' . $search ] = array( 400, '{"ucp":{"status":"error"},"messages":[{"code":"VALIDATION_ERROR","content":"A query or at least one supported filter is required."}]}' );
		$this->serve( $responses );
		$this->assertTrue( $this->results()['ucp_endpoints_live'], 'An empty search is a JSON 400, which means the route answered' );

		$responses[ 'POST ' . $search ] = array( 400, '<html>Bad request</html>' );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ucp_endpoints_live'], 'A 400 page is not the endpoint' );
		$this->assertSame( '', array_column( $this->sut->all(), 'source', 'id' )['ucp_profile_version'], 'A matching version names none' );

		$responses['/.well-known/ucp'] = array( 200, (string) str_replace( UcpUtils::VERSION, '2025-01-01', (string) $profile ) );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ucp_profile_version'], 'A profile for another UCP version is likely another plugin\'s' );
		$this->assertSame( '2025-01-01', array_column( $this->sut->all(), 'source', 'id' )['ucp_profile_version'], 'The served version is kept for the message' );

		$responses['/.well-known/ucp'] = array( 200, (string) str_replace( '"' . UcpUtils::VERSION . '"', '"<b>x</b>"', (string) $profile ) );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ucp_profile_version'] );
		$this->assertSame( '', array_column( $this->sut->all(), 'source', 'id' )['ucp_profile_version'], 'A version that isn\'t a date is not shown' );

		$responses['/.well-known/ucp'] = array( 200, (string) str_replace( UcpUtils::VERSION, '2099-01-01', (string) $profile ) );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ucp_profile_version'] );
		$this->assertSame( '', array_column( $this->sut->all(), 'source', 'id' )['ucp_profile_version'], 'A newer version is not called older' );

		$responses['/.well-known/ucp']  = array( 404, '' );
		$responses[ 'POST ' . $search ] = array( 403, '<html>Blocked</html>' );
		$this->serve( $responses );
		$results = $this->results();
		$this->assertFalse( $results['ucp_profile'] );
		$this->assertNull( $results['ucp_profile_version'], 'No profile is reported by ucp_profile alone' );
		$this->assertFalse( $results['ucp_endpoints_live'], 'Without a profile, WooCommerce\'s catalog search is tried' );
		$this->assertContains( 'POST ' . $search, $this->requested );

		$responses[ 'POST ' . $search ] = array( 403, '<html>Blocked</html>', array( 'content-type' => 'text/HTML; charset=UTF-8' ) );
		$this->serve( $responses );
		$this->assertSame( '403 text/html', array_column( $this->sut->all(), 'source', 'id' )['ucp_endpoints_live'], 'A failed check keeps what came back' );
		$responses[ 'POST ' . $search ] = null;
		$this->serve( $responses );
		$this->assertSame( '', array_column( $this->sut->all(), 'source', 'id' )['ucp_endpoints_live'], 'No answer leaves nothing to show' );

		$responses[ $path ]             = array( 200, '{"namespace":"wc/ucp/v1","routes":{}}' );
		$responses[ 'POST ' . $search ] = array( 404, '' );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ucp_endpoints_live'], 'A JSON index is not catalog search' );

		$responses[ 'POST ' . $search ] = array( 200, '<html>Checking your browser</html>' );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ucp_endpoints_live'], 'A challenge page is not the endpoint' );
		$this->assertSame( 2, count( array_keys( $this->requested, 'POST ' . $search, true ) ), 'A failed search is asked once more' );

		$this->serve( $responses );
		$answers = array( array( 403, '<html>Blocked</html>' ), array( 200, '{"ucp":{"status":"success"},"products":[]}' ) );
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( $search, &$answers ) {
				if ( 'POST' !== $args['method'] || substr( $url, strlen( untrailingslashit( home_url() ) ) ) !== $search ) {
					return $pre;
				}
				$answer = array_shift( $answers );
				return array(
					'headers'  => new \WpOrg\Requests\Utility\CaseInsensitiveDictionary( array() ),
					'body'     => $answer[1],
					'response' => array(
						'code'    => $answer[0],
						'message' => '',
					),
					'cookies'  => array(),
				);
			},
			20,
			3
		);
		$this->assertTrue( $this->results()['ucp_endpoints_live'], 'A refusal followed by the endpoint passes' );
	}

	/**
	 * @testdox Only the UCP checkout check waits as pending, until this WooCommerce serves UCP checkout; the product structured data checks run.
	 */
	public function test_pending_checks(): void {
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );
		$this->serve( $this->good_store() );

		$checks = array_column( $this->sut->all(), null, 'id' );

		$this->assertFalse( $checks['product_shipping_schema']['pending'] );
		$this->assertFalse( $checks['product_buy_action_schema']['pending'] );
		$this->assertSame(
			StoreConditions::serves_ucp_checkout() ? array() : array( 'ucp_checkout' ),
			array_keys( array_filter( array_column( $checks, 'pending', 'id' ) ) ),
			'The UCP checkout check runs once this WooCommerce serves UCP checkout'
		);
	}

	/**
	 * @testdox The shipping and buy action checks read the product page, and couldn't check a product that can't ship or can't be bought now.
	 */
	public function test_shipping_and_buy_action_on_the_page(): void {
		$ld        = static fn( string $offer ): string => '<script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"Product","name":"Mug","offers":' . $offer . '}]}</script>';
		$responses = $this->good_store();
		$result    = function ( string $offer, string $id ) use ( $responses, $ld ): ?bool {
			$responses[ $this->product_path() ] = array( 200, self::page( $ld( $offer ) ) );
			$this->serve( $responses );
			return array_column( $this->sut->all(), 'ok', 'id' )[ $id ];
		};
		$bare      = '{"@type":"Offer","price":"10"}';
		$zone      = new \WC_Shipping_Zone();
		$zone->set_zone_name( 'US' );
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );

		$this->assertTrue( $result( '{"@type":"Offer","price":"10","shippingDetails":{"@type":"OfferShippingDetails"}}', 'product_shipping_schema' ) );
		$this->assertFalse( $result( $bare, 'product_shipping_schema' ) );
		$this->assertFalse( $result( $bare, 'product_buy_action_schema' ), 'An in-stock simple product gets a BuyAction from WooCommerce' );
		$responses[ $this->product_path() ] = array( 200, self::page( '' ) );
		$this->serve( $responses );
		$no_ld = array_column( $this->sut->all(), 'ok', 'id' );
		$this->assertNull( $no_ld['product_shipping_schema'], 'Without product details it couldn\'t check, as the other product details checks' );
		$this->assertNull( $no_ld['product_buy_action_schema'] );

		update_option( 'woocommerce_ship_to_countries', 'disabled' );
		$this->assertNull( $result( $bare, 'product_shipping_schema' ), 'Shipping turned off: no shipping details to give' );
		delete_option( 'woocommerce_ship_to_countries' );
		add_filter( 'woocommerce_get_zone_criteria', '__return_empty_array' );
		$this->assertNull( $result( $bare, 'product_shipping_schema' ), 'A zone criteria filter: WooCommerce prints no shipping details' );
		remove_filter( 'woocommerce_get_zone_criteria', '__return_empty_array' );

		add_filter( 'woocommerce_countries_shipping_countries', '__return_empty_array' );
		$this->assertNull( $result( $bare, 'product_shipping_schema' ), 'A zone WooCommerce can\'t describe: it prints no shipping details' );
		remove_filter( 'woocommerce_countries_shipping_countries', '__return_empty_array' );
		$zone->delete();
		$this->assertFalse( $result( $bare, 'product_shipping_schema' ), 'No zone with a delivery method: the merchant can add one' );
		$this->assertTrue( $result( '{"@type":"Offer","price":"10","shippingDetails":{"@type":"OfferShippingDetails"}}', 'product_shipping_schema' ), 'Printed by another plugin, it still counts' );

		add_filter( 'woocommerce_product_is_visible', '__return_false' );
		$this->assertNull( $result( $bare, 'product_buy_action_schema' ), 'Hidden by a visibility filter: WooCommerce prints no BuyAction' );
		remove_filter( 'woocommerce_product_is_visible', '__return_false' );

		$this->product->set_stock_status( 'outofstock' );
		$this->product->save();
		$this->assertNull( $result( $bare, 'product_buy_action_schema' ), 'WooCommerce links only a product in stock to checkout' );
	}

	/**
	 * @testdox The returns policy check passes for a policy on an offer or on the store's item, as WooCommerce adds it, fails without one, and couldn't check without product details.
	 */
	public function test_return_policy_on_offer_or_store(): void {
		$returns   = '{"@type":"MerchantReturnPolicy","merchantReturnLink":"' . home_url( '/refund_returns/' ) . '"}';
		$ld        = static fn( string $graph ): string => '<script type="application/ld+json">{"@context":"https://schema.org","@graph":[' . $graph . ']}</script>';
		$responses = $this->good_store();
		$result    = function ( string $graph ) use ( $responses, $ld ): ?bool {
			$responses[ $this->product_path() ] = array( 200, self::page( $ld( $graph ) ) );
			$this->serve( $responses );
			return array_column( $this->sut->all(), 'ok', 'id' )['product_return_policy_schema'];
		};

		$this->assertTrue( $result( '{"@type":"Product","name":"Mug","offers":{"@type":"Offer","price":"10"}},{"@type":"OnlineStore","name":"Shop","hasMerchantReturnPolicy":' . $returns . '}' ), 'WooCommerce adds it to the OnlineStore item' );
		$this->assertTrue( $result( '{"@type":"Product","name":"Mug","offers":{"@type":"Offer","price":"10","hasMerchantReturnPolicy":' . $returns . '}}' ), 'On the offer' );
		$this->assertFalse( $result( '{"@type":"Product","name":"Mug","offers":{"@type":"Offer","price":"10"}},{"@type":"OnlineStore","name":"Shop"}' ) );
		$this->assertNull( $result( '{"@type":"OnlineStore","name":"Shop"}' ), 'Without product details it couldn\'t check, as the other product details checks' );
	}

	/**
	 * @testdox The product checks find a returns policy and shipping details on an offer, and a BuyAction on the product or an offer.
	 */
	public function test_pending_product_detectors(): void {
		$offer_has  = new \ReflectionMethod( StoreOutputChecks::class, 'offer_has' );
		$buy_action = new \ReflectionMethod( StoreOutputChecks::class, 'has_buy_action' );
		$offer_has->setAccessible( true );
		$buy_action->setAccessible( true );
		$product = array(
			'@type'  => 'Product',
			'offers' => array(
				array(
					'@type'                   => 'Offer',
					'hasMerchantReturnPolicy' => array( '@type' => 'MerchantReturnPolicy' ),
					'potentialAction'         => array( '@type' => 'BuyAction' ),
				),
			),
		);

		$this->assertTrue( $offer_has->invoke( null, array( $product ), 'hasMerchantReturnPolicy' ) );
		$this->assertFalse( $offer_has->invoke( null, array( $product ), 'shippingDetails' ) );
		$this->assertTrue( $buy_action->invoke( null, array( $product ) ) );
		$this->assertTrue( $buy_action->invoke( null, array( array( 'potentialAction' => array( array( '@type' => array( 'BuyAction' ) ) ) ) ) ) );
		$this->assertFalse( $buy_action->invoke( null, array( array( 'potentialAction' => array( '@type' => 'ViewAction' ) ) ) ) );

		$nested = array(
			'@type'  => 'Product',
			'offers' => array(
				'@type'  => 'AggregateOffer',
				'offers' => array(
					'@type'                   => 'Offer',
					'hasMerchantReturnPolicy' => array( '@type' => 'MerchantReturnPolicy' ),
				),
			),
		);
		$this->assertTrue( $offer_has->invoke( null, array( $nested ), 'hasMerchantReturnPolicy' ), 'Policy on a nested Offer inside AggregateOffer' );
	}

	/**
	 * @testdox Every store output check, UCP ones included, names the question it answers on the settings page.
	 */
	public function test_every_check_has_a_group(): void {
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );
		$this->serve( array() );
		$checks = $this->sut->all();
		$this->assertContains( 'ucp_profile', array_column( $checks, 'id' ) );
		foreach ( $checks as $check ) {
			$this->assertContains( $check['group'], array( 'access', 'find', 'understand', 'buy' ), $check['id'] );
		}
	}

	/**
	 * @testdox The pending product checks read the variants of a ProductGroup, and don't fail a variable product, which WooCommerce gives no buy action, or a product that doesn't ship.
	 */
	public function test_pending_product_detectors_read_variants(): void {
		$offer_has  = new \ReflectionMethod( StoreOutputChecks::class, 'offer_has' );
		$buy_action = new \ReflectionMethod( StoreOutputChecks::class, 'has_buy_action' );
		$ships      = new \ReflectionMethod( StoreOutputChecks::class, 'ships' );
		$offer_has->setAccessible( true );
		$buy_action->setAccessible( true );
		$ships->setAccessible( true );
		$group       = array(
			'@type'      => 'ProductGroup',
			'hasVariant' => array(
				array(
					'@type'           => 'Product',
					'offers'          => array(
						'@type'           => 'Offer',
						'shippingDetails' => array( '@type' => 'OfferShippingDetails' ),
					),
					'potentialAction' => array( '@type' => 'BuyAction' ),
				),
			),
		);
		$empty_group = array(
			'@type'      => 'ProductGroup',
			'hasVariant' => array( array( '@type' => 'Product' ) ),
		);
		$variable    = WC_Helper_Product::create_variation_product();

		$this->assertTrue( $offer_has->invoke( null, array( $group ), 'shippingDetails' ), 'Shipping details on a variant\'s offer' );
		$this->assertFalse( $offer_has->invoke( null, array( $group ), 'hasMerchantReturnPolicy' ) );
		$this->assertTrue( $buy_action->invoke( null, array( $group ), $variable ), 'A BuyAction on a variant' );
		$this->assertNull( $buy_action->invoke( null, array( $empty_group ), $variable ), 'A variable product without one' );
		$this->assertFalse( $buy_action->invoke( null, array( $empty_group ), $this->product ), 'A simple product without one' );

		$variable->delete( true );
		$this->assertFalse( $ships->invoke( null, new StoreResponses( true ) ), 'No zone with a delivery method' );
		$zone = new \WC_Shipping_Zone();
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$zone->add_shipping_method( 'local_pickup' );
		$this->assertFalse( $ships->invoke( null, new StoreResponses( true ) ), 'Local pickup only: WooCommerce describes no delivery' );
		$zone->add_shipping_method( 'flat_rate' );
		$this->assertTrue( $ships->invoke( null, new StoreResponses( true ) ) );
		$this->product->set_regular_price( '' );
		$this->product->save();
		$this->assertNull( $ships->invoke( null, new StoreResponses( true ) ), 'Without a price WooCommerce prints no offer' );
		$this->product->set_regular_price( '10' );
		$this->product->set_virtual( true );
		$this->product->save();
		$this->assertNull( $ships->invoke( null, new StoreResponses( true ) ), 'A virtual product has no shipping details to give' );

		$this->product->delete( true );
		WC_Helper_Product::create_variation_product();
		$this->assertNull( $ships->invoke( null, new StoreResponses( true ) ), 'A variable product: WooCommerce prints shipping details only for a chosen variation' );
	}

	/**
	 * @testdox When something strips WooCommerce's Open Graph tags from the product page, the check flags a likely conflict, names the page but no plugin, even with an SEO plugin active; with WooCommerce's tags turned off, the check isn't run.
	 */
	public function test_open_graph_conflict(): void {
		$responses                          = $this->good_store();
		$responses[ $this->product_path() ] = array( 200, self::page( '', '<h1>Blue &amp; Gold Mug</h1>', false ) );
		$this->serve( $responses );
		update_option( 'active_plugins', array( 'wordpress-seo/wp-seo.php', 'hello.php' ) );

		$checks = array_column( $this->sut->all(), null, 'id' );

		$this->assertFalse( $checks['open_graph_from_core']['ok'] );
		$this->assertTrue( $checks['open_graph_from_core']['conflict'] );
		$this->assertSame( (string) $this->product->get_permalink(), $checks['open_graph_from_core']['seen_at'] );
		$this->assertArrayNotHasKey( 'plugins', $checks['open_graph_from_core'], 'No plugin is named: a list of plugins goes stale' );
		$this->assertSame( '', $checks['open_graph_tags']['seen_at'], 'Checks that are not conflicts carry no address' );

		$this->serve( $this->good_store() );
		$this->assertTrue( array_column( $this->sut->all(), 'ok', 'id' )['open_graph_from_core'] );

		add_filter( 'woocommerce_enable_open_graph', '__return_false' );
		$this->assertArrayNotHasKey( 'open_graph_from_core', array_column( $this->sut->all(), 'ok', 'id' ) );
		remove_filter( 'woocommerce_enable_open_graph', '__return_false' );
	}
}
