<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettings;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreResponses;
use WC_Helper_Product;
use WC_Structured_Data;
use WC_Unit_Test_Case;

/**
 * Tests for the store checks that a stock store passes today, kept so a merchant hears when a host, theme or plugin breaks one.
 */
class RegressionGuardChecksTest extends WC_Unit_Test_Case {

	private const GUARDS = array(
		'no_noindex_directives',
		'pages_public',
		'sitemap_lastmod',
		'rss_feed',
		'viewport_meta',
		'html_lang_and_charset',
		'images_have_alt',
		'single_h1',
		'schema_matches_product',
		'product_review_markup',
		'policy_pages_linked',
	);

	/**
	 * The product whose page the checks read.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Pretty permalinks, a product, and published privacy and returns pages.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->set_permalink_structure( '/%postname%/' );
		update_option( 'woocommerce_coming_soon', 'no' );
		$this->product = WC_Helper_Product::create_simple_product();
		foreach ( array( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION ) as $option ) {
			update_option(
				$option,
				self::factory()->post->create(
					array(
						'post_type'   => 'page',
						'post_status' => 'publish',
					)
				)
			);
		}
	}

	/**
	 * Restore plain permalinks, which live on the global rewrite object outside the rollback.
	 */
	public function tearDown(): void {
		try {
			remove_all_filters( 'pre_http_request' );
			$this->set_permalink_structure( '' );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Path of a URL from the home URL.
	 *
	 * @param string $url URL.
	 */
	private static function path( string $url ): string {
		return substr( $url, strlen( untrailingslashit( home_url() ) ) );
	}

	/**
	 * The responses of a healthy store, by path: status, body and headers.
	 *
	 * @return array<string, array{0:int,1:string,2?:array<string,string>}>
	 */
	private function healthy_store(): array {
		$structured_data = new WC_Structured_Data();
		$structured_data->generate_product_data( $this->product );
		$jsonld  = wc_esc_json( (string) wp_json_encode( $structured_data->get_structured_data( array() ), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ), true );
		$head    = '<!doctype html><html lang="en-US"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width">';
		$privacy = (string) get_permalink( (int) get_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION ) );
		$returns = (string) get_permalink( (int) get_option( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION ) );
		return array(
			'/robots.txt'                                 => array( 200, "User-agent: *\nDisallow: /wp-admin/\n\nSitemap: " . home_url( '/wp-sitemap.xml' ) . "\n" ),
			'/wp-sitemap.xml'                             => array( 200, '<urlset><url><loc>' . $this->product->get_permalink() . '</loc><lastmod>2026-11-01</lastmod></url></urlset>' ),
			'/'                                           => array( 200, $head . '<link rel="alternate" type="application/rss+xml" href="/feed/"></head><body><h1>Store</h1><img src="a.jpg" alt="Mug"><img src="line.png" alt=""><footer><a href="' . $privacy . '#details">Privacy</a> <a href="' . $returns . '">Returns</a></footer></body></html>' ),
			self::path( $this->product->get_permalink() ) => array( 200, $head . '<script type="application/ld+json">' . $jsonld . '</script></head><body><h1>' . $this->product->get_name() . '</h1><p>' . wc_price( (float) $this->product->get_price() ) . '</p><img src="p.jpg" alt="Front"></body></html>' ),
		);
	}

	/**
	 * Serve responses by path. Any other path is a 404.
	 *
	 * @param array<string, array{0:int,1:string,2?:array<string,string>}> $responses Responses by path.
	 * @return array<string, bool|null> Results by check id.
	 */
	private function results( array $responses ): array {
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( $responses ) {
				unset( $pre, $args ); // Avoid parameter not used PHPCS errors.
				$response = $responses[ self::path( $url ) ] ?? array( 404, '' );
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
		return array_column( wc_get_container()->get( StoreOutputChecks::class )->all(), 'ok', 'id' );
	}

	/**
	 * @testdox A store that serves what a stock store serves passes every regression guard.
	 */
	public function test_healthy_store_passes(): void {
		$results = $this->results( $this->healthy_store() );

		foreach ( self::GUARDS as $id ) {
			$this->assertTrue( $results[ $id ], $id );
		}
	}

	/**
	 * @testdox When the store can't reach itself, the guards that read pages are couldn't check.
	 */
	public function test_unreachable_store(): void {
		add_filter( 'pre_http_request', static fn() => new \WP_Error( 'http_request_failed', 'Blocked in tests' ) );

		$results = array_column( wc_get_container()->get( StoreOutputChecks::class )->all(), 'ok', 'id' );

		foreach ( array_diff( self::GUARDS, array( 'product_review_markup' ) ) as $id ) {
			$this->assertNull( $results[ $id ], $id );
		}
		$this->assertTrue( $results['product_review_markup'], 'No product has reviews, so there is nothing to mark up and no page is loaded' );
	}

	/**
	 * @testdox noindex, noai and none fail in a robots meta tag or an X-Robots-Tag header; noarchive and a preview size of none don't.
	 */
	public function test_noindex_directives(): void {
		$store      = $this->healthy_store();
		$store['/'] = array( 200, '<html><head><meta name="robots" content="max-image-preview:large, noarchive"></head></html>' );
		$this->assertTrue( $this->results( $store )['no_noindex_directives'] );

		remove_all_filters( 'pre_http_request' );
		$store['/'] = array( 200, '<html><head><meta name="robots" content="index, follow, max-image-preview:none, max-video-preview:none"></head></html>' );
		$this->assertTrue( $this->results( $store )['no_noindex_directives'], 'none as a preview size is not the none directive' );

		remove_all_filters( 'pre_http_request' );
		$store['/'] = array( 200, '<html><head><meta name="robots" content="index, follow, max-image-preview: none, max-video-preview: none"></head></html>' );
		$this->assertTrue( $this->results( $store )['no_noindex_directives'], 'none as a preview size, with a space, is not the none directive' );

		foreach ( array( '<meta name="robots" content="noindex, follow">', '<meta name="robots" content="noindex nofollow">', '<meta name="googlebot" content="none">', '<meta name="googlebot-news" content="noindex">', '<meta name="robots" content="noai, noimageai">' ) as $tag ) {
			remove_all_filters( 'pre_http_request' );
			$store['/'] = array( 200, '<html><head>' . $tag . '</head></html>' );
			$this->assertFalse( $this->results( $store )['no_noindex_directives'], $tag );
		}

		remove_all_filters( 'pre_http_request' );
		$store             = $this->healthy_store();
		$path              = self::path( $this->product->get_permalink() );
		$store[ $path ][2] = array( 'x-robots-tag' => 'noindex' );
		$this->assertFalse( $this->results( $store )['no_noindex_directives'], 'Header' );

		remove_all_filters( 'pre_http_request' );
		$store          = $this->healthy_store();
		$store['/']     = array( 200, '<html><head><meta name="robots" content="noindex"></head></html>' );
		$store[ $path ] = array( 404, '' );
		$this->assertFalse( $this->results( $store )['no_noindex_directives'], 'A noindex home page fails even when the product page did not load' );
	}

	/**
	 * @testdox A redirect to the sign-in page is a login wall. A 403 or another failure is couldn't check.
	 */
	public function test_login_wall(): void {
		$path           = self::path( $this->product->get_permalink() );
		$store          = $this->healthy_store();
		$store[ $path ] = array( 403, 'Members only' );
		$this->assertNull( $this->results( $store )['pages_public'], 'A 403 can be a firewall refusing the store its own page' );

		remove_all_filters( 'pre_http_request' );
		$store[ $path ] = array( 302, '', array( 'location' => site_url( '/wp-login.php?redirect_to=x' ) ) );
		$this->assertFalse( $this->results( $store )['pages_public'] );

		remove_all_filters( 'pre_http_request' );
		update_option(
			'woocommerce_myaccount_page_id',
			self::factory()->post->create(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_name'   => 'my-account',
					'post_title'  => 'My account',
				)
			)
		);
		$account                         = wc_get_page_permalink( 'myaccount' );
		$store[ $path ]                  = array( 302, '', array( 'location' => $account ) );
		$store[ self::path( $account ) ] = array( 200, '<html>Sign in</html>' );
		$this->assertFalse( $this->results( $store )['pages_public'], 'A followed redirect to the account page is a login wall' );

		remove_all_filters( 'pre_http_request' );
		unset( $store[ self::path( $account ) ] );
		$store[ $path ]         = array( 302, '', array( 'location' => site_url( '/wp-login.php' ) ) );
		$store['/wp-login.php'] = array( 200, '<html>Log in</html>' );
		$this->assertFalse( $this->results( $store )['pages_public'], 'A followed redirect to wp-login.php is a login wall' );

		remove_all_filters( 'pre_http_request' );
		unset( $store['/wp-login.php'] );
		$store[ $path ]  = array( 302, '', array( 'location' => home_url( '/hop-1' ) ) );
		$store['/hop-1'] = array( 302, '', array( 'location' => home_url( '/hop-2' ) ) );
		$store['/hop-2'] = array( 302, '', array( 'location' => site_url( '/wp-login.php' ) ) );
		$this->assertFalse( $this->results( $store )['pages_public'], 'A login redirect past the follow limit is still a login wall' );

		remove_all_filters( 'pre_http_request' );
		$store[ $path ] = array( 503, '' );
		$this->assertNull( $this->results( $store )['pages_public'], 'A server error is couldn\'t check' );
	}

	/**
	 * @testdox Missing markup fails each page guard.
	 */
	public function test_missing_markup(): void {
		$path           = self::path( $this->product->get_permalink() );
		$store          = $this->healthy_store();
		$store['/']     = array( 200, '<html><head></head><body><img src="a.jpg"></body></html>' );
		$store[ $path ] = array( 200, str_replace( '</h1>', '</h1><h1>Related</h1>', $store[ $path ][1] ) );

		$results = $this->results( $store );

		foreach ( array( 'rss_feed', 'viewport_meta', 'html_lang_and_charset', 'images_have_alt', 'single_h1', 'policy_pages_linked' ) as $id ) {
			$this->assertFalse( $results[ $id ], $id );
		}
	}

	/**
	 * @testdox A charset in the Content-Type header counts; an image hidden from assistive technology, a noscript fallback or a tracking pixel needs no alt.
	 */
	public function test_header_charset_and_hidden_images(): void {
		$store      = $this->healthy_store();
		$store['/'] = array( 200, '<html lang="de"><body><img src="x.gif" aria-hidden="true"><noscript><img src="pixel" /></noscript><img src="px.gif" width="1" height="1" style="display:none" /></body></html>', array( 'content-type' => 'text/html; charset=UTF-8' ) );

		$results = $this->results( $store );

		$this->assertTrue( $results['html_lang_and_charset'] );
		$this->assertTrue( $results['images_have_alt'], 'A noscript fallback and a tracking pixel need no alt' );

		remove_all_filters( 'pre_http_request' );
		$store['/'] = array( 200, '<html lang="de"><body><noscript><img src="pixel" /></noscript><img src="hero.jpg"></body></html>', array( 'content-type' => 'text/html; charset=UTF-8' ) );
		$this->assertFalse( $this->results( $store )['images_have_alt'], 'A visible image after the noscript block still needs alt' );
	}

	/**
	 * @testdox Structured data with another name or price than the product fails.
	 */
	public function test_schema_mismatch(): void {
		$path           = self::path( $this->product->get_permalink() );
		$jsonld         = array(
			'@type'  => 'Product',
			'@id'    => $this->product->get_permalink() . '#product',
			'name'   => $this->product->get_name(),
			'offers' => array(
				'@type'         => 'Offer',
				'price'         => '99.00',
				'priceCurrency' => 'USD',
				'availability'  => 'https://schema.org/InStock',
			),
		);
		$store          = $this->healthy_store();
		$store[ $path ] = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
		$this->assertFalse( $this->results( $store )['schema_matches_product'], 'Price' );

		remove_all_filters( 'pre_http_request' );
		$jsonld['offers']['price'] = '99.00 USD';
		$store[ $path ]            = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
		$this->assertFalse( $this->results( $store )['schema_matches_product'], 'A price with a currency code' );

		foreach ( array( 'R$ 10,00', 'US$10', 'S/. 10', 'EUR 10,-' ) as $price ) {
			remove_all_filters( 'pre_http_request' );
			$jsonld['name']            = $this->product->get_name();
			$jsonld['offers']['price'] = $price;
			$store[ $path ]            = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
			$this->assertTrue( $this->results( $store )['schema_matches_product'], $price );
		}

		remove_all_filters( 'pre_http_request' );
		$jsonld['offers']['price'] = $this->product->get_price();
		$jsonld['name']            = 'Another product';
		$store[ $path ]            = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
		$this->assertFalse( $this->results( $store )['schema_matches_product'], 'Name' );

		remove_all_filters( 'pre_http_request' );
		$jsonld['name']            = $this->product->get_name();
		$jsonld['offers']['price'] = '99.00';
		$related                   = array(
			'@type'  => 'Product',
			'name'   => 'Related product',
			'offers' => array(
				'@type'         => 'Offer',
				'price'         => $this->product->get_price(),
				'priceCurrency' => 'USD',
			),
		);
		$store[ $path ]            = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( array( $jsonld, $related ) ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
		$this->assertFalse( $this->results( $store )['schema_matches_product'], 'A related product at the right price doesn\'t stand in for the product\'s own offer' );

		remove_all_filters( 'pre_http_request' );
		unset( $jsonld['offers']['price'] );
		$jsonld['offers']['@type']    = 'AggregateOffer';
		$jsonld['offers']['lowPrice'] = '99.00';
		$store[ $path ]               = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
		$this->assertFalse( $this->results( $store )['schema_matches_product'], 'An AggregateOffer lowPrice is the price' );

		remove_all_filters( 'pre_http_request' );
		$jsonld['offers'] = array(
			'@type'              => 'Offer',
			'price'              => '8.00',
			'priceCurrency'      => 'USD',
			'availability'       => 'https://schema.org/InStock',
			'priceSpecification' => array(
				'@type'         => 'UnitPriceSpecification',
				'priceType'     => 'https://schema.org/ListPrice',
				'price'         => $this->product->get_price(),
				'priceCurrency' => 'USD',
			),
		);
		$store[ $path ]   = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
		$this->assertFalse( $this->results( $store )['schema_matches_product'], 'A list price is not the current price' );
	}

	/**
	 * @testdox A price WooCommerce prints rounded to the store's decimals matches. A different amount does not.
	 */
	public function test_schema_price_rounded_to_store_decimals(): void {
		update_option( 'woocommerce_price_num_decimals', '0' );
		$this->product->set_regular_price( '9.99' );
		$this->product->set_price( '9.99' );
		$this->product->save();

		$path           = self::path( $this->product->get_permalink() );
		$jsonld         = array(
			'@type'  => 'Product',
			'@id'    => $this->product->get_permalink() . '#product',
			'name'   => $this->product->get_name(),
			'offers' => array(
				'@type'         => 'Offer',
				'price'         => '10',
				'priceCurrency' => 'USD',
				'availability'  => 'https://schema.org/InStock',
			),
		);
		$store          = $this->healthy_store();
		$store[ $path ] = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
		$this->assertTrue( $this->results( $store )['schema_matches_product'], '9.99 printed with 0 decimals is 10' );

		remove_all_filters( 'pre_http_request' );
		$jsonld['offers']['price'] = '12';
		$store[ $path ]            = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
		$this->assertFalse( $this->results( $store )['schema_matches_product'], 'A different rounded price is still a conflict' );
	}

	/**
	 * @testdox A served price at another tax rate of the product's tax class matches: the page was served to one location.
	 */
	public function test_schema_price_at_another_tax_rate(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$second = 0;
		$rate   = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'DE',
				'tax_rate'         => '25.0000',
				'tax_rate_name'    => 'VAT',
				'tax_rate_class'   => '',
			)
		);
		try {
			$path           = self::path( $this->product->get_permalink() );
			$jsonld         = array(
				'@type'  => 'Product',
				'@id'    => $this->product->get_permalink() . '#product',
				'name'   => $this->product->get_name(),
				'offers' => array(
					'@type'         => 'Offer',
					'price'         => number_format( (float) $this->product->get_price() * 1.25, 2, '.', '' ),
					'priceCurrency' => 'USD',
					'availability'  => 'https://schema.org/InStock',
				),
			);
			$store          = $this->healthy_store();
			$store[ $path ] = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
			$this->assertTrue( $this->results( $store )['schema_matches_product'] );

			remove_all_filters( 'pre_http_request' );
			$this->product->set_tax_status( 'none' );
			$this->product->save();
			$this->assertFalse( $this->results( $store )['schema_matches_product'], 'A non-taxable product is not priced at a stored tax rate' );

			remove_all_filters( 'pre_http_request' );
			$this->product->set_tax_status( 'taxable' );
			$this->product->save();
			$second = \WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country' => 'FR',
					'tax_rate'         => '5.0000',
					'tax_rate_name'    => 'VAT',
					'tax_rate_class'   => '',
				)
			);

			$jsonld['offers']['price'] = '99.00';
			$store[ $path ]            = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
			$this->assertNull( $this->results( $store )['schema_matches_product'], 'Two rates of one class can apply together, so an unmatched price stays unknown' );
		} finally {
			if ( $second ) {
				\WC_Tax::_delete_tax_rate( $second );
			}
			\WC_Tax::_delete_tax_rate( $rate );
			delete_option( 'woocommerce_calc_taxes' );
		}
	}

	/**
	 * @testdox A product with rated reviews needs a rating in its structured data; text-only reviews or ratings turned off need none.
	 */
	public function test_review_markup(): void {
		update_option( 'woocommerce_enable_reviews', 'yes' );
		update_option( 'woocommerce_enable_review_rating', 'yes' );
		$reviewed = WC_Helper_Product::create_simple_product();
		update_post_meta( $reviewed->get_id(), '_wc_review_count', 3 );
		$path = self::path( $reviewed->get_permalink() );

		$store          = $this->healthy_store();
		$store[ $path ] = array( 200, '<script type="application/ld+json">{"@type":"Product","name":"Mug"}</script>' );
		$this->assertTrue( $this->results( $store )['product_review_markup'], 'Reviews without a rating get no rating markup' );

		update_post_meta( $reviewed->get_id(), '_wc_average_rating', '4.50' );
		remove_all_filters( 'pre_http_request' );
		$store[ $path ] = array(
			200,
			'<script type="application/ld+json">' . wp_json_encode(
				array(
					'@type' => 'Product',
					'name'  => $reviewed->get_name(),
				)
			) . '</script>',
		);
		$this->assertFalse( $this->results( $store )['product_review_markup'] );

		update_option( 'woocommerce_enable_review_rating', 'no' );
		remove_all_filters( 'pre_http_request' );
		$this->assertTrue( $this->results( $store )['product_review_markup'], 'Ratings off: WooCommerce prints none' );

		update_option( 'woocommerce_enable_review_rating', 'yes' );
		remove_all_filters( 'pre_http_request' );
		$store[ $path ] = array(
			200,
			'<script type="application/ld+json">' . wp_json_encode(
				array(
					'@type'           => 'Product',
					'name'            => $reviewed->get_name(),
					'aggregateRating' => array(
						'@type'       => 'AggregateRating',
						'ratingValue' => '4.5',
						'reviewCount' => '3',
					),
				)
			) . '</script>',
		);
		$this->assertTrue( $this->results( $store )['product_review_markup'] );

		remove_all_filters( 'pre_http_request' );
		$store[ $path ] = array( 200, '<script type="application/ld+json">{"@type":"Product","name":"Other","aggregateRating":{"@type":"AggregateRating","ratingValue":"4.5","reviewCount":"3"}}</script>' );
		$this->assertFalse( $this->results( $store )['product_review_markup'], 'A related product\'s rating does not mark up this product' );
	}

	/**
	 * @testdox A review page cut off before its rating is couldn't check. The sampled product is read past the short page limit.
	 */
	public function test_truncated_review_page_is_unknown(): void {
		update_option( 'woocommerce_enable_reviews', 'yes' );
		update_option( 'woocommerce_enable_review_rating', 'yes' );
		$this->product->set_date_created( time() - HOUR_IN_SECONDS );
		$this->product->save();
		$reviewed = WC_Helper_Product::create_simple_product();
		update_post_meta( $reviewed->get_id(), '_wc_average_rating', '4.50' );
		$path   = self::path( $reviewed->get_permalink() );
		$rating = '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@type'           => 'Product',
				'name'            => $reviewed->get_name(),
				'aggregateRating' => array(
					'@type'       => 'AggregateRating',
					'ratingValue' => '4.5',
				),
			)
		) . '</script>';

		$store          = $this->healthy_store();
		$store[ $path ] = array( 200, str_repeat( 'x', StoreResponses::MAX_BYTES ) . $rating );
		$this->assertTrue( $this->results( $store )['product_review_markup'], 'The sampled product is read past the short page limit' );

		remove_all_filters( 'pre_http_request' );
		$store[ $path ] = array( 200, '<html></html>', array( 'content-length' => '99999999' ) );
		$this->assertNull( $this->results( $store )['product_review_markup'], 'A page cut off before the rating is couldn\'t check' );

		$reviewed->set_date_created( time() - HOUR_IN_SECONDS );
		$reviewed->save();
		$this->product->set_date_created( time() );
		$this->product->save();
		remove_all_filters( 'pre_http_request' );
		$this->assertNull( $this->results( $store )['product_review_markup'], 'A different product\'s page, cut off, is couldn\'t check' );
	}

	/**
	 * @testdox When the newest reviewed product is hidden, the next visible reviewed product is read.
	 */
	public function test_review_markup_skips_hidden_products(): void {
		update_option( 'woocommerce_enable_reviews', 'yes' );
		update_option( 'woocommerce_enable_review_rating', 'yes' );
		$visible = WC_Helper_Product::create_simple_product();
		update_post_meta( $visible->get_id(), '_wc_average_rating', '4.00' );
		$visible->set_date_created( time() - HOUR_IN_SECONDS );
		$visible->save();
		$hidden = WC_Helper_Product::create_simple_product();
		$hidden->set_catalog_visibility( 'hidden' );
		$hidden->save();
		update_post_meta( $hidden->get_id(), '_wc_average_rating', '5.00' );

		$store = $this->healthy_store();
		$store[ self::path( $visible->get_permalink() ) ] = array( 200, '<script type="application/ld+json">{"@type":"Product","name":"Mug"}</script>' );

		$this->assertFalse( $this->results( $store )['product_review_markup'] );
	}

	/**
	 * @testdox Hidden reviewed products are skipped before the sample limit, so an older visible one is still read.
	 */
	public function test_review_markup_skips_a_run_of_hidden_products(): void {
		update_option( 'woocommerce_enable_reviews', 'yes' );
		update_option( 'woocommerce_enable_review_rating', 'yes' );
		$visible = WC_Helper_Product::create_simple_product();
		$visible->set_date_created( time() - DAY_IN_SECONDS );
		$visible->save();
		update_post_meta( $visible->get_id(), '_wc_average_rating', '4.00' );
		for ( $i = 0; $i < 5; $i++ ) {
			$hidden = WC_Helper_Product::create_simple_product();
			$hidden->set_catalog_visibility( 'hidden' );
			$hidden->save();
			update_post_meta( $hidden->get_id(), '_wc_average_rating', '5.00' );
		}

		$store = $this->healthy_store();
		$store[ self::path( $visible->get_permalink() ) ] = array( 200, '<script type="application/ld+json">{"@type":"Product","name":"Mug"}</script>' );

		$this->assertFalse( $this->results( $store )['product_review_markup'] );
	}

	/**
	 * @testdox lastmod is read from the sitemaps robots.txt names and the default paths, or from the first sitemap an index lists; every candidate is tried.
	 */
	public function test_sitemap_lastmod_from_index(): void {
		$child                    = home_url( '/products.xml' );
		$store                    = $this->healthy_store();
		$store['/wp-sitemap.xml'] = array( 200, "<sitemapindex><sitemap><loc>$child</loc></sitemap></sitemapindex>" );
		$store['/products.xml']   = array( 200, '<urlset><url><loc>a</loc><lastmod>2026-01-01</lastmod></url></urlset>' );
		$this->assertTrue( $this->results( $store )['sitemap_lastmod'] );

		remove_all_filters( 'pre_http_request' );
		$store['/wp-sitemap.xml'] = array( 200, '<sitemapindex><sitemap><loc><![CDATA[' . $child . ']]></loc></sitemap></sitemapindex>' );
		$this->assertTrue( $this->results( $store )['sitemap_lastmod'], 'A CDATA child URL is still fetched' );

		remove_all_filters( 'pre_http_request' );
		$queried                  = home_url( '/products.xml?page=2&type=product' );
		$store['/wp-sitemap.xml'] = array( 200, '<sitemapindex><sitemap><loc>' . str_replace( '&', '&amp;', $queried ) . '</loc></sitemap></sitemapindex>' );
		unset( $store['/products.xml'] );
		$store[ self::path( $queried ) ] = array( 200, '<urlset><url><loc>a</loc><lastmod>2026-01-01</lastmod></url></urlset>' );
		$this->assertTrue( $this->results( $store )['sitemap_lastmod'], 'An escaped query string in a child URL is still fetched' );

		remove_all_filters( 'pre_http_request' );
		$store['/wp-sitemap.xml'] = array( 200, "<sitemapindex><sitemap><loc>$child</loc></sitemap></sitemapindex>" );
		$store['/products.xml']   = array( 200, '<urlset><url><loc>a</loc></url></urlset>' );
		$this->assertFalse( $this->results( $store )['sitemap_lastmod'] );

		remove_all_filters( 'pre_http_request' );
		$store['/sitemap.xml'] = array( 200, '<urlset><url><loc>a</loc><lastmod>2026-01-01</lastmod></url></urlset>' );
		$this->assertTrue( $this->results( $store )['sitemap_lastmod'], 'The listed sitemap has no dates, a default one has' );

		remove_all_filters( 'pre_http_request' );
		unset( $store['/sitemap.xml'] );
		$store['/wp-sitemap.xml'] = array( 200, '<sitemapindex><sitemap><loc>https://cdn.example/products.xml</loc></sitemap><sitemap><loc>' . $child . '</loc></sitemap></sitemapindex>' );
		$store['/products.xml']   = array( 200, '<urlset><url><loc>a</loc><lastmod>2026-01-01</lastmod></url></urlset>' );
		$this->assertTrue( $this->results( $store )['sitemap_lastmod'], 'An off-host first child is skipped and the next child is read' );

		remove_all_filters( 'pre_http_request' );
		$store['/wp-sitemap.xml'] = array( 200, '<sitemapindex><sitemap><loc>' . home_url( '/nested.xml' ) . '</loc></sitemap></sitemapindex>' );
		$store['/nested.xml']     = array( 200, '<sitemapindex><sitemap><loc>' . $child . '</loc></sitemap></sitemapindex>' );
		$this->assertTrue( $this->results( $store )['sitemap_lastmod'], 'A nested index is opened' );

		remove_all_filters( 'pre_http_request' );
		unset( $store['/nested.xml'], $store['/products.xml'] );
		$store['/wp-sitemap.xml'] = array( 200, '<sitemapindex><sitemap><loc>https://cdn.example/products.xml</loc></sitemap></sitemapindex>' );
		$this->assertFalse( $this->results( $store )['sitemap_lastmod'], 'A CDN-hosted child is skipped, not left unchecked' );
	}

	/**
	 * @testdox sitemap_lastmod is couldn't check when the listed sitemap fails, or robots.txt fails and no default sitemap answers.
	 */
	public function test_sitemap_lastmod_unknown(): void {
		$child                    = home_url( '/products.xml' );
		$store                    = $this->healthy_store();
		$store['/wp-sitemap.xml'] = array( 200, "<sitemapindex><sitemap><loc>$child</loc></sitemap></sitemapindex>" );
		$store['/products.xml']   = array( 503, '' );
		$this->assertNull( $this->results( $store )['sitemap_lastmod'], 'Listed sitemap fails' );

		remove_all_filters( 'pre_http_request' );
		$store['/products.xml'] = array( 403, '' );
		$this->assertNull( $this->results( $store )['sitemap_lastmod'], 'A blocked sitemap was not read' );

		remove_all_filters( 'pre_http_request' );
		$store                = $this->healthy_store();
		$store['/robots.txt'] = array( 403, '' );
		$this->assertTrue( $this->results( $store )['sitemap_lastmod'], 'robots.txt fails, default sitemap has dates' );

		remove_all_filters( 'pre_http_request' );
		unset( $store['/wp-sitemap.xml'] );
		$this->assertNull( $this->results( $store )['sitemap_lastmod'], 'robots.txt fails, no sitemap at the default paths' );
	}

	/**
	 * @testdox The shop and a category page need a meta description. A stock archive without a canonical link does not fail that check.
	 */
	public function test_shop_and_category_pages(): void {
		$shop = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'shop',
			)
		);
		update_option( 'woocommerce_shop_page_id', $shop );
		$term = wp_insert_term( 'Mugs', 'product_cat' );
		wp_set_object_terms( $this->product->get_id(), array( (int) $term['term_id'] ), 'product_cat' );
		$this->product = wc_get_product( $this->product->get_id() );

		$tagged            = static fn( string $url ): string => '<html><head><link rel="canonical" href="' . $url . '"><meta name="description" content="Mugs"></head></html>';
		$store             = $this->healthy_store();
		$path              = self::path( $this->product->get_permalink() );
		$category          = (string) get_term_link( (int) $term['term_id'], 'product_cat' );
		$store[ $path ][1] = str_replace( '<meta charset="UTF-8">', '<meta charset="UTF-8"><link rel="canonical" href="' . $this->product->get_permalink() . '"><meta name="description" content="A mug">', $store[ $path ][1] );
		$store['/']        = array( 200, str_replace( '<meta charset="UTF-8">', '<meta charset="UTF-8"><meta name="description" content="Store">', $store['/'][1] ) );
		$store[ self::path( (string) get_permalink( $shop ) ) ] = array( 200, '<html><head></head></html>' );
		$store[ self::path( $category ) ]                       = array( 200, $tagged( $category ) );
		$results = $this->results( $store );
		$this->assertTrue( $results['canonical_url'], 'A shop page without a canonical link does not fail: WordPress does not print one on an archive' );
		$this->assertFalse( $results['meta_description'], 'Shop page without a meta description' );

		remove_all_filters( 'pre_http_request' );
		$store[ self::path( (string) get_permalink( $shop ) ) ] = array( 200, $tagged( (string) get_permalink( $shop ) ) );
		$results = $this->results( $store );
		$this->assertTrue( $results['canonical_url'] );
		$this->assertTrue( $results['meta_description'] );

		remove_all_filters( 'pre_http_request' );
		$store[ $path ][1] = str_replace( '<link rel="canonical" href="' . $this->product->get_permalink() . '">', '', $store[ $path ][1] );
		$this->assertFalse( $this->results( $store )['canonical_url'], 'The product page is missing the canonical link WordPress prints' );
	}
}
