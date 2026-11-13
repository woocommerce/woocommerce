<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\DeclaredToAgents;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
use WC_Unit_Test_Case;

/**
 * Tests for the declared-to-agents list.
 */
class DeclaredToAgentsTest extends WC_Unit_Test_Case {

	/**
	 * Run after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		delete_option( 'woocommerce_coming_soon' );
		parent::tearDown();
	}

	/**
	 * Results by id, with each path answering as given and any other path a 404.
	 *
	 * @param array<string, array{0:int,1:string,2?:array<string,string>}> $responses Status, body and headers by path; 'markdown /path' for a Markdown request.
	 * @return array<string, bool|null>
	 */
	private function results( array $responses ): array {
		update_option( 'woocommerce_coming_soon', 'no' );
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( $responses ) {
				unset( $pre );
				$path     = substr( $url, strlen( untrailingslashit( home_url() ) ) );
				$markdown = 'text/markdown' === ( $args['headers']['Accept'] ?? '' );
				$response = $responses[ ( $markdown ? 'markdown ' : '' ) . $path ] ?? array( 404, '' );
				return array(
					'headers'  => $response[2] ?? array(),
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
		return array_column( $this->scan(), 'found', 'id' );
	}

	/**
	 * The list from one scan.
	 *
	 * @return array<int, array{id:string,name:string,found:bool|null}>
	 */
	private function scan(): array {
		return wc_get_container()->get( DeclaredToAgents::class )->all( wc_get_container()->get( StoreOutputChecks::class )->responses() );
	}

	/**
	 * @testdox A 401 or 403 is not checked: a firewall can refuse a file that exists.
	 */
	public function test_blocked_response_is_not_checked(): void {
		$results = $this->results(
			array(
				'/ai.txt'                      => array( 403, 'Forbidden' ),
				'/.well-known/agent-card.json' => array( 401, '' ),
				'/feed/'                       => array( 403, '' ),
				'markdown /'                   => array( 403, '' ),
				'/sitemap.xml'                 => array( 403, '' ),
			)
		);

		$this->assertNull( $results['ai-txt'] );
		$this->assertNull( $results['a2a-agent-card'] );
		$this->assertNull( $results['feed-present'] );
		$this->assertNull( $results['markdown-pages'] );
		$this->assertNull( $results['sitemap-lastmod'] );
	}

	/**
	 * @testdox A file counts as found; a 404, a page a theme answers with, or a 200 error a host answers with does not.
	 */
	public function test_files(): void {
		$results = $this->results(
			array(
				'/ai.txt'                  => array( 200, 'User-Agent: *' ),
				'/.well-known/agents.json' => array( 200, '<!doctype html><html></html>' ),
				'/AGENTS.md'               => array( 200, '{"code":"not_found","message":"No route"}' ),
				'/auth.md'                 => array( 200, 'Not found', array( 'content-type' => 'text/html; charset=UTF-8' ) ),
				'/sitemap.md'              => array( 200, "\xEF\xBB\xBF<!doctype html><html></html>" ),
			)
		);

		$this->assertTrue( $results['ai-txt'] );
		$this->assertFalse( $results['agents-json'], 'A page is not a JSON document' );
		$this->assertFalse( $results['security-txt'], 'A 404' );
		$this->assertFalse( $results['agents-md'], 'A 200 JSON error is not a file' );
		$this->assertFalse( $results['auth-md'], 'A 200 HTML answer is not a file' );
		$this->assertFalse( $results['sitemap-md'], 'A page behind a byte order mark is not a file' );
	}

	/**
	 * @testdox A JSON document counts only when it has what its spec requires, so a JSON error on any path is not found.
	 */
	public function test_documents(): void {
		$error   = array( 200, '{"code":"not_found","message":"No route"}' );
		$results = $this->results(
			array(
				'/.well-known/agent-card.json'            => $error,
				'/openapi.json'                           => $error,
				'/.well-known/oauth-authorization-server' => $error,
				'/.well-known/oauth-protected-resource'   => array( 200, '{"resource":"https://example.org"}' ),
				'/.well-known/agent-skills/index.json'    => array( 200, '{"skills":[{"name":"a"}]}' ),
				'/.well-known/http-message-signatures-directory' => array( 200, '{"keys":[{"kty":"OKP"}]}' ),
				'/.well-known/tdmrep.json'                => array( 200, '[{"location":"/","tdm-reservation":1}]' ),
				'/.well-known/agents.json'                => array( 503, '' ),
			)
		);

		$this->assertFalse( $results['a2a-agent-card'] );
		$this->assertFalse( $results['openapi-spec'] );
		$this->assertFalse( $results['oauth-discovery'] );
		$this->assertTrue( $results['oauth-protected-resource'] );
		$this->assertTrue( $results['agent-skills-index'] );
		$this->assertTrue( $results['web-bot-auth'] );
		$this->assertTrue( $results['tdmrep'] );
		$this->assertNull( $results['agents-json'], 'A server error is not checked' );

		remove_all_filters( 'pre_http_request' );
		$this->assertFalse( $this->results( array( '/.well-known/agents.json' => $error ) )['agents-json'] );
		remove_all_filters( 'pre_http_request' );
		$this->assertTrue( $this->results( array( '/.well-known/agents.json' => array( 200, '{"agentsJson":"0.1.0","flows":[{"id":"a"}]}' ) ) )['agents-json'] );
	}

	/**
	 * @testdox Protocol documents count only with the fields their spec requires.
	 */
	public function test_protocol_documents(): void {
		$host    = wp_parse_url( home_url(), PHP_URL_HOST );
		$results = $this->results(
			array(
				'/.well-known/poppy.json'      => array(
					200,
					wp_json_encode(
						array(
							'protocol_version' => '0.3',
							'organization'     => array( 'domain' => $host ),
						)
					),
				),
				'/.well-known/acp.json'        => array( 200, '{"protocol":{"name":"other"}}' ),
				'/.well-known/ard.json'        => array( 200, '{"entries":[]}' ),
				'/.well-known/ai-catalog.json' => array( 200, '{"entries":[]}' ),
				'/openapi.json'                => array( 200, '{"openapi":"3.1.0","paths":{"/a":{"get":{"x-payment-info":{}}}}}' ),
			)
		);

		$this->assertTrue( $results['poppy-json'] );
		$this->assertFalse( $results['acp-support'] );
		$this->assertTrue( $results['ard-manifest'] );
		$this->assertFalse( $results['ai-catalog'], 'specVersion is required' );
		$this->assertTrue( $results['mpp'] );
		$this->assertTrue( $results['openapi-spec'] );

		remove_all_filters( 'pre_http_request' );
		$results = $this->results(
			array(
				'/.well-known/poppy.json' => array( 200, '{"protocol_version":"0.3","organization":{"domain":"other.example"}}' ),
				'/openapi.json'           => array( 200, '{"openapi":"3.1.0","paths":{"/a":{"get":{}}}}' ),
			)
		);
		$this->assertFalse( $results['poppy-json'], 'Another domain' );
		$this->assertFalse( $results['mpp'] );
	}

	/**
	 * @testdox WebMCP needs a form with both attributes, and a page that fails to load is not checked.
	 */
	public function test_webmcp_forms(): void {
		$this->assertTrue( $this->results( array( '/' => array( 200, '<form id="a" toolname="search" tooldescription="Find"></form>' ) ) )['webmcp-forms-schema'] );
		remove_all_filters( 'pre_http_request' );
		$this->assertFalse( $this->results( array( '/' => array( 200, '<form toolname="search"></form>' ) ) )['webmcp-forms-schema'] );
		remove_all_filters( 'pre_http_request' );
		$this->assertNull( $this->results( array( '/' => array( 503, '' ) ) )['webmcp-forms-schema'] );
		remove_all_filters( 'pre_http_request' );
		$product = \WC_Helper_Product::create_simple_product();
		$path    = substr( (string) $product->get_permalink(), strlen( untrailingslashit( home_url() ) ) );
		$this->assertTrue(
			$this->results(
				array(
					'/'   => array( 200, '<html></html>' ),
					$path => array( 200, '<form toolname="buy" tooldescription="Buy"></form>' ),
				)
			)['webmcp-forms-schema'],
			'On the product page only'
		);
	}

	/**
	 * @testdox Declarations inside an HTML comment or a script are not found: only real tags count.
	 */
	public function test_commented_out_declarations(): void {
		$results = $this->results(
			array(
				'/' => array(
					200,
					'<html><head><!-- <link rel="alternate" type="text/markdown" href="/index.md"> --></head><body>'
					. '<!-- <form toolname="search" tooldescription="Find"></form> -->'
					. '<script>var a = \'<pre class="language-php">\';</script>'
					. '<!-- <input type="search" name="s" role="combobox"> --></body></html>',
				),
			)
		);
		$this->assertFalse( $results['webmcp-forms-schema'] );
		$this->assertFalse( $results['markdown-alternate-link'] );
		$this->assertFalse( $results['code-block-language'] );
		$this->assertFalse( $results['site-search-autocomplete'] );

		remove_all_filters( 'pre_http_request' );
		$results = $this->results( array( '/' => array( 200, '<form><input type="search" name="s" aria-autocomplete="none"></form>' ) ) );
		$this->assertFalse( $results['site-search-autocomplete'], 'aria-autocomplete="none" turns it off' );
	}

	/**
	 * @testdox lastmod is read from a sitemap or from the first sitemap an index lists.
	 */
	public function test_sitemap_lastmod(): void {
		$child = home_url( '/products.xml' );
		$this->assertTrue( $this->results( array( '/sitemap.xml' => array( 200, '<urlset><url><loc>a</loc><lastmod>2026-01-01</lastmod></url></urlset>' ) ) )['sitemap-lastmod'] );
		remove_all_filters( 'pre_http_request' );
		$this->assertFalse( $this->results( array( '/sitemap.xml' => array( 200, '<urlset><url><loc>a</loc></url></urlset>' ) ) )['sitemap-lastmod'] );
		remove_all_filters( 'pre_http_request' );
		$this->assertTrue(
			$this->results(
				array(
					'/sitemap.xml'  => array( 200, "<sitemapindex><sitemap><loc>$child</loc></sitemap></sitemapindex>" ),
					'/products.xml' => array( 200, '<urlset><url><loc>a</loc><lastmod>2026-01-01</lastmod></url></urlset>' ),
				)
			)['sitemap-lastmod']
		);
	}

	/**
	 * @testdox lastmod is not found on a sitemap without dates, and not checked when a sitemap or robots.txt fails.
	 */
	public function test_sitemap_lastmod_unknown(): void {
		$child = home_url( '/products.xml' );
		$index = array( 200, "<sitemapindex><sitemap><loc>$child</loc></sitemap></sitemapindex>" );
		$this->assertFalse(
			$this->results(
				array(
					'/sitemap.xml'  => $index,
					'/products.xml' => array( 200, '<urlset><url><loc>a</loc></url></urlset>' ),
				)
			)['sitemap-lastmod']
		);
		remove_all_filters( 'pre_http_request' );
		$this->assertNull(
			$this->results(
				array(
					'/sitemap.xml'  => $index,
					'/products.xml' => array( 503, '' ),
				)
			)['sitemap-lastmod']
		);
		remove_all_filters( 'pre_http_request' );
		$this->assertNull( $this->results( array( '/robots.txt' => array( 403, '' ) ) )['sitemap-lastmod'] );
	}

	/**
	 * @testdox A Markdown version is found by Accept and from a link, frontmatter is read from it, and an HTML answer doesn't count.
	 */
	public function test_markdown_versions(): void {
		$markdown = array( 'content-type' => 'text/markdown; charset=utf-8' );
		$results  = $this->results(
			array(
				'markdown /' => array( 200, "---\ntitle: Store\n---\n# Store", $markdown ),
				'/'          => array( 200, '<html><head><link rel="alternate" type="text/markdown" href="/index.md"></head></html>' ),
			)
		);
		$this->assertTrue( $results['markdown-pages'] );
		$this->assertTrue( $results['markdown-alternate-link'] );
		$this->assertTrue( $results['markdown-frontmatter'] );
		$this->assertArrayNotHasKey( 'markdown-twin', $results );

		remove_all_filters( 'pre_http_request' );
		$results = $this->results(
			array(
				'markdown /' => array( 200, '# Store', array( 'content-type' => 'text/plain' ) ),
				'/'          => array( 200, '<html><head></head></html>' ),
			)
		);
		$this->assertTrue( $results['markdown-pages'], 'Plain text Markdown counts' );
		$this->assertFalse( $results['markdown-alternate-link'] );
		$this->assertFalse( $results['markdown-frontmatter'], 'Markdown without frontmatter' );

		remove_all_filters( 'pre_http_request' );
		$results = $this->results( array( 'markdown /' => array( 200, '<html></html>', array( 'content-type' => 'text/html' ) ) ) );
		$this->assertFalse( $results['markdown-pages'], 'An HTML answer to Accept: text/markdown' );
		$this->assertFalse( $results['markdown-frontmatter'] );

		remove_all_filters( 'pre_http_request' );
		$results = $this->results( array( 'markdown /' => array( 503, '' ) ) );
		$this->assertNull( $results['markdown-pages'] );
		$this->assertNull( $results['markdown-frontmatter'] );
	}

	/**
	 * @testdox The feed and home page structured data are read from what the store serves.
	 */
	public function test_feed_and_home_page(): void {
		$results = $this->results(
			array(
				'/feed/' => array( 200, '<?xml version="1.0"?><rss version="2.0"></rss>' ),
				'/'      => array( 200, '<html><head><script type="application/ld+json">{"@type":"WebSite","potentialAction":{"@type":"SearchAction"}}</script></head></html>' ),
			)
		);

		$this->assertTrue( $results['feed-present'] );
		$this->assertTrue( $results['site-search-action'] );
		$this->assertFalse( $results['sameas-links'] );
	}

	/**
	 * @testdox A failing feed is not checked, and only a SearchAction counts as on-site search.
	 */
	public function test_failing_feed_and_other_actions(): void {
		$results = $this->results(
			array(
				'/feed/' => array( 503, '' ),
				'/'      => array( 200, '<html><script type="application/ld+json">{"@type":"WebSite","potentialAction":{"@type":"ReadAction"}}</script></html>' ),
			)
		);
		$this->assertNull( $results['feed-present'] );
		$this->assertFalse( $results['site-search-action'] );

		remove_all_filters( 'pre_http_request' );
		$results = $this->results(
			array(
				'/' => array( 200, '<html><script type="application/ld+json">{"@type":"WebSite","potentialAction":[{"@type":"ReadAction"},{"@type":"SearchAction"}]}</script></html>' ),
			)
		);
		$this->assertTrue( $results['site-search-action'] );

		remove_all_filters( 'pre_http_request' );
		$results = $this->results(
			array(
				'/' => array( 200, '<html><script type="application/ld+json">{"@type":"WebSite","potentialAction":{"@type":"https://schema.org/SearchAction"}}</script></html>' ),
			)
		);
		$this->assertTrue( $results['site-search-action'], 'A type written as a URL' );
	}

	/**
	 * @testdox Code blocks with a language class, an author or publish date, and a search box with autocomplete are found on the home page.
	 */
	public function test_page_markup_items(): void {
		$home    = '<html><head><meta property="article:published_time" content="2026-11-01"></head><body>'
			. '<pre><code class="language-php">echo 1;</code></pre>'
			. '<form><input type="search" name="s" role="combobox" aria-autocomplete="list"></form></body></html>';
		$results = $this->results( array( '/' => array( 200, $home ) ) );

		$this->assertTrue( $results['code-block-language'] );
		$this->assertTrue( $results['editorial-metadata'] );
		$this->assertTrue( $results['site-search-autocomplete'] );

		remove_all_filters( 'pre_http_request' );
		$plain   = '<html><body><pre><code>echo 1;</code></pre><form><input type="search" name="s"></form></body></html>';
		$results = $this->results( array( '/' => array( 200, $plain ) ) );
		$this->assertFalse( $results['code-block-language'], 'A code block without a language' );
		$this->assertFalse( $results['editorial-metadata'] );
		$this->assertFalse( $results['site-search-autocomplete'], 'A search box without autocomplete' );

		remove_all_filters( 'pre_http_request' );
		$results = $this->results( array( '/' => array( 200, '<html><script type="application/ld+json">{"@type":"WebPage","datePublished":"2026-11-01"}</script></html>' ) ) );
		$this->assertTrue( $results['editorial-metadata'], 'A publish date in JSON-LD' );
	}

	/**
	 * @testdox An MCP server card counts at either well-known path when it names the server and where to connect.
	 */
	public function test_mcp_server_card(): void {
		$results = $this->results( array( '/.well-known/mcp.json' => array( 200, '{"name":"Shop","endpoint":"https://example.org/mcp"}' ) ) );
		$this->assertTrue( $results['mcp-server-card'] );

		remove_all_filters( 'pre_http_request' );
		$results = $this->results( array( '/.well-known/mcp/server-card.json' => array( 200, '{"serverInfo":{"name":"Shop"}}' ) ) );
		$this->assertFalse( $results['mcp-server-card'], 'A card that does not say where to connect' );

		remove_all_filters( 'pre_http_request' );
		$results = $this->results( array( '/.well-known/mcp/server-card.json' => array( 503, '' ) ) );
		$this->assertNull( $results['mcp-server-card'], 'A server error is not checked' );
	}

	/**
	 * @testdox DNS-AID and an automated product feed are listed as not checked yet.
	 */
	public function test_not_read_yet(): void {
		$results = $this->results( array() );

		$this->assertArrayHasKey( 'dns-aid', $results );
		$this->assertNull( $results['dns-aid'] );
		$this->assertNull( $results['product-feed-automation'] );
	}

	/**
	 * @testdox One scan requests the pages the store checks and the list share only once.
	 */
	public function test_scan_shares_responses(): void {
		$requests = array();
		update_option( 'woocommerce_coming_soon', 'no' );
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( &$requests ) {
				unset( $pre );
				// The Markdown request is a different request to a URL already loaded.
				if ( ! isset( $args['headers']['Accept'] ) ) {
					$requests[] = $url;
				}
				return array(
					'headers'  => array(),
					'body'     => '',
					'response' => array(
						'code'    => 404,
						'message' => '',
					),
					'cookies'  => array(),
				);
			},
			10,
			3
		);

		wc_get_container()->get( AgentReadiness::class )->get_report();

		$this->assertSame( array_values( array_unique( $requests ) ), $requests );
	}

	/**
	 * @testdox In coming soon mode nothing is requested and every item is not checked.
	 */
	public function test_coming_soon(): void {
		update_option( 'woocommerce_coming_soon', 'yes' );
		add_filter(
			'pre_http_request',
			function () {
				$this->fail( 'No request while in coming soon mode' );
			}
		);

		foreach ( $this->scan() as $item ) {
			$this->assertNull( $item['found'], $item['id'] );
		}
	}
}
