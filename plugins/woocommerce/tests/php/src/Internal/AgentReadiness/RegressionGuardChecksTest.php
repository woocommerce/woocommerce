<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettings;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
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
			'/'                                           => array( 200, $head . '<link rel="alternate" type="application/rss+xml" href="/feed/"></head><body><h1>Store</h1><img src="a.jpg" alt="Mug"><img src="line.png" alt=""><footer><a href="' . $privacy . '">Privacy</a> <a href="' . $returns . '">Returns</a></footer></body></html>' ),
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

		foreach ( array( '<meta name="robots" content="noindex, follow">', '<meta name="googlebot" content="none">', '<meta name="robots" content="noai, noimageai">' ) as $tag ) {
			remove_all_filters( 'pre_http_request' );
			$store['/'] = array( 200, '<html><head>' . $tag . '</head></html>' );
			$this->assertFalse( $this->results( $store )['no_noindex_directives'], $tag );
		}

		remove_all_filters( 'pre_http_request' );
		$store             = $this->healthy_store();
		$path              = self::path( $this->product->get_permalink() );
		$store[ $path ][2] = array( 'x-robots-tag' => 'noindex' );
		$this->assertFalse( $this->results( $store )['no_noindex_directives'], 'Header' );
	}

	/**
	 * @testdox A 403 or a redirect to the sign-in page is a login wall; other failures are couldn't check.
	 */
	public function test_login_wall(): void {
		$path           = self::path( $this->product->get_permalink() );
		$store          = $this->healthy_store();
		$store[ $path ] = array( 403, 'Members only' );
		$this->assertFalse( $this->results( $store )['pages_public'] );

		remove_all_filters( 'pre_http_request' );
		$store[ $path ] = array( 302, '', array( 'location' => site_url( '/wp-login.php?redirect_to=x' ) ) );
		$this->assertFalse( $this->results( $store )['pages_public'] );

		remove_all_filters( 'pre_http_request' );
		$store[ $path ] = array( 503, '' );
		$this->assertTrue( $this->results( $store )['pages_public'], 'An error is not a login wall' );
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
		$jsonld['offers']['price'] = $this->product->get_price();
		$jsonld['name']            = 'Another product';
		$store[ $path ]            = array( 200, '<html><script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script><h1>' . $this->product->get_name() . '</h1></html>' );
		$this->assertFalse( $this->results( $store )['schema_matches_product'], 'Name' );
	}

	/**
	 * @testdox A served price at another tax rate of the product's tax class matches: the page was served to one location.
	 */
	public function test_schema_price_at_another_tax_rate(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$rate = \WC_Tax::_insert_tax_rate(
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
		} finally {
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
		$this->assertFalse( $this->results( $store )['product_review_markup'] );

		update_option( 'woocommerce_enable_review_rating', 'no' );
		remove_all_filters( 'pre_http_request' );
		$this->assertTrue( $this->results( $store )['product_review_markup'], 'Ratings off: WooCommerce prints none' );

		remove_all_filters( 'pre_http_request' );
		$store[ $path ] = array( 200, '<script type="application/ld+json">{"@type":"Product","name":"Mug","aggregateRating":{"@type":"AggregateRating","ratingValue":"4.5","reviewCount":"3"}}</script>' );
		$this->assertTrue( $this->results( $store )['product_review_markup'] );
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
	 * @testdox lastmod is read from the sitemaps robots.txt names and the default paths, or from the first sitemap an index lists; every candidate is tried.
	 */
	public function test_sitemap_lastmod_from_index(): void {
		$child                    = home_url( '/products.xml' );
		$store                    = $this->healthy_store();
		$store['/wp-sitemap.xml'] = array( 200, "<sitemapindex><sitemap><loc>$child</loc></sitemap></sitemapindex>" );
		$store['/products.xml']   = array( 200, '<urlset><url><loc>a</loc><lastmod>2026-01-01</lastmod></url></urlset>' );
		$this->assertTrue( $this->results( $store )['sitemap_lastmod'] );

		remove_all_filters( 'pre_http_request' );
		$store['/products.xml'] = array( 200, '<urlset><url><loc>a</loc></url></urlset>' );
		$this->assertFalse( $this->results( $store )['sitemap_lastmod'] );

		remove_all_filters( 'pre_http_request' );
		$store['/sitemap.xml'] = array( 200, '<urlset><url><loc>a</loc><lastmod>2026-01-01</lastmod></url></urlset>' );
		$this->assertTrue( $this->results( $store )['sitemap_lastmod'], 'The listed sitemap has no dates, a default one has' );
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
		$store                = $this->healthy_store();
		$store['/robots.txt'] = array( 403, '' );
		$this->assertTrue( $this->results( $store )['sitemap_lastmod'], 'robots.txt fails, default sitemap has dates' );

		remove_all_filters( 'pre_http_request' );
		unset( $store['/wp-sitemap.xml'] );
		$this->assertNull( $this->results( $store )['sitemap_lastmod'], 'robots.txt fails, no sitemap at the default paths' );
	}

	/**
	 * @testdox The shop and a category page need a canonical link and a meta description too; a stock shop page without them fails.
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
		$this->assertFalse( $results['canonical_url'], 'Shop page without a canonical link' );
		$this->assertFalse( $results['meta_description'], 'Shop page without a meta description' );

		remove_all_filters( 'pre_http_request' );
		$store[ self::path( (string) get_permalink( $shop ) ) ] = array( 200, $tagged( (string) get_permalink( $shop ) ) );
		$results = $this->results( $store );
		$this->assertTrue( $results['canonical_url'] );
		$this->assertTrue( $results['meta_description'] );
	}
}
