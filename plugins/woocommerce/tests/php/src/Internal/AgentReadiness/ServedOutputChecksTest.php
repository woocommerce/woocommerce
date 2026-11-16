<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

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
	 * @testdox With UCP on, the profile check reads the served profile, and the endpoint check POSTs to catalog search.
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
		$this->assertContains( 'POST ' . $search, $this->requested );

		$responses[ 'POST ' . $search ] = array( 400, '{"ucp":{"status":"error"},"messages":[{"code":"VALIDATION_ERROR","content":"A query or at least one supported filter is required."}]}' );
		$this->serve( $responses );
		$this->assertTrue( $this->results()['ucp_endpoints_live'], 'An empty search is a JSON 400, which means the route answered' );

		$responses[ 'POST ' . $search ] = array( 400, '<html>Bad request</html>' );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ucp_endpoints_live'], 'A 400 page is not the endpoint' );

		$responses['/.well-known/ucp'] = array( 200, (string) str_replace( UcpUtils::VERSION, '2025-01-01', (string) $profile ) );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ucp_profile_version'], 'A profile for another UCP version is likely another plugin\'s' );

		$responses['/.well-known/ucp']  = array( 404, '' );
		$responses[ 'POST ' . $search ] = array( 403, '<html>Blocked</html>' );
		$this->serve( $responses );
		$results = $this->results();
		$this->assertFalse( $results['ucp_profile'] );
		$this->assertNull( $results['ucp_profile_version'], 'No profile is reported by ucp_profile alone' );
		$this->assertFalse( $results['ucp_endpoints_live'], 'Without a profile, WooCommerce\'s catalog search is tried' );
		$this->assertContains( 'POST ' . $search, $this->requested );

		$responses[ $path ]             = array( 200, '{"namespace":"wc/ucp/v1","routes":{}}' );
		$responses[ 'POST ' . $search ] = array( 404, '' );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ucp_endpoints_live'], 'A JSON index is not catalog search' );

		$responses[ 'POST ' . $search ] = array( 200, '<html>Checking your browser</html>' );
		$this->serve( $responses );
		$this->assertFalse( $this->results()['ucp_endpoints_live'], 'A challenge page is not the endpoint' );
	}

	/**
	 * @testdox Checks for output WooCommerce doesn't build yet don't run, and are reported as pending.
	 */
	public function test_pending_checks(): void {
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );
		$this->serve( $this->good_store() );

		$checks = array_column( $this->sut->all(), null, 'id' );

		foreach ( array( 'product_return_policy_schema', 'product_shipping_schema', 'product_buy_action_schema', 'ucp_checkout' ) as $id ) {
			$this->assertNull( $checks[ $id ]['ok'], $id );
			$this->assertTrue( $checks[ $id ]['pending'], $id );
		}
		$this->assertFalse( $checks['open_graph_tags']['pending'] );
	}

	/**
	 * @testdox The pending product checks find a returns policy and shipping details on an offer, and a BuyAction on the product or an offer.
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

		$this->assertTrue( $ships->invoke( null, new StoreResponses( true ) ) );
		$this->product->set_virtual( true );
		$this->product->save();
		$variable->delete( true );
		$this->assertFalse( $ships->invoke( null, new StoreResponses( true ) ), 'A virtual product has no shipping details to give' );
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
