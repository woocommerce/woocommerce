<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Store checks that read what the store returns (robots.txt, sitemaps, page
 * markup), where a plugin can add or replace what core builds. They run only in
 * the background scan, and their results are kept in the stored report in the
 * same shape as StoreConditions.
 *
 * `ok` is true when any source provides the item, false when none does, and null
 * when the store could not load its own page (couldn't check). These checks are
 * always recommended: a result that can be "couldn't check" never blocks UCP.
 *
 * @since 11.4.0
 */
class StoreOutputChecks {

	/**
	 * Coming soon mode helper.
	 *
	 * @var ComingSoonHelper
	 */
	private ComingSoonHelper $coming_soon_helper;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param ComingSoonHelper $coming_soon_helper Coming soon mode helper.
	 */
	final public function init( ComingSoonHelper $coming_soon_helper ): void {
		$this->coming_soon_helper = $coming_soon_helper;
	}

	/**
	 * Run every check. Each URL is requested at most once per call.
	 *
	 * @return array<int, array{id:string,ok:bool|null,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}>
	 */
	public function all(): array {
		$responses = new StoreResponses( $this->coming_soon_helper->is_site_live() );
		$results   = array();
		foreach ( $this->checks() as $check ) {
			try {
				$ok = ( $check['test'] )( $responses );
			} catch ( \Throwable $e ) {
				// Reported as couldn't check, so one broken check doesn't stop the scan.
				$ok = null;
				wc_get_logger()->warning(
					sprintf( 'Agent readiness check %s failed: %s', $check['id'], $e->getMessage() ),
					array( 'source' => 'agent-readiness' )
				);
			}
			$results[] = array(
				'id'      => $check['id'],
				'ok'      => $ok,
				'blocks'  => $check['blocks'],
				'level'   => StoreConditions::LEVEL_RECOMMENDED,
				'name'    => $check['name'],
				'label'   => $check['label'],
				'why'     => $check['why'],
				'fix'     => $check['fix'],
				'fix_url' => $check['fix_url'],
			);
		}
		return $results;
	}

	/**
	 * Crawlers that fetch pages while answering a shopper, as opposed to training crawlers.
	 */
	public const AI_SEARCH_BOTS = array( 'OAI-SearchBot', 'ChatGPT-User', 'Claude-User', 'PerplexityBot' );

	private const MAX_LISTED_SITEMAPS = 5;

	/**
	 * The checks, each with a test that returns true, false, or null for couldn't check.
	 *
	 * @return array<int, array{id:string,test:callable(StoreResponses):(bool|null),blocks:string,name:string,label:string,why:string,fix:string,fix_url:string}>
	 */
	protected function checks(): array {
		global $wp_rewrite;
		$robots_fix = __( 'Edit robots.txt in your SEO plugin, or ask your host to change the robots.txt file in your site\'s root folder.', 'woocommerce' );
		$plain      = ! $wp_rewrite->using_permalinks();
		return array(
			array(
				'id'      => 'robots_txt_present',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->robots_present( $responses ),
				'blocks'  => 'listing',
				'name'    => __( 'Store serves a robots.txt file', 'woocommerce' ),
				'label'   => __( 'No robots.txt file', 'woocommerce' ),
				'why'     => __( 'Crawlers, including AI shopping crawlers, read robots.txt to find your sitemap.', 'woocommerce' ),
				'fix'     => $plain ? __( 'Choose any permalink structure other than Plain under Settings > Permalinks, so WordPress serves robots.txt.', 'woocommerce' ) : $robots_fix,
				'fix_url' => $plain ? admin_url( 'options-permalink.php' ) : '',
			),
			array(
				'id'      => 'robots_allows_ai_search_bots',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->robots_allows( $responses, '/' ),
				'blocks'  => 'listing',
				'name'    => __( 'robots.txt lets AI search crawlers in', 'woocommerce' ),
				'label'   => __( 'robots.txt blocks AI search crawlers', 'woocommerce' ),
				'why'     => __( 'OAI-SearchBot, ChatGPT-User, Claude-User and PerplexityBot fetch your pages while answering shoppers; a blocked crawler can\'t show your products.', 'woocommerce' ),
				'fix'     => $robots_fix,
				'fix_url' => '',
			),
			array(
				'id'      => 'robots_not_blocking_llms_txt',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->robots_allows( $responses, '/llms.txt' ),
				'blocks'  => 'listing',
				'name'    => __( 'robots.txt lets AI crawlers read llms.txt', 'woocommerce' ),
				'label'   => __( 'robots.txt blocks llms.txt', 'woocommerce' ),
				'why'     => __( 'llms.txt tells AI agents what your store sells; a Disallow rule that covers it hides it from them.', 'woocommerce' ),
				'fix'     => $robots_fix,
				'fix_url' => '',
			),
			array(
				'id'      => 'sitemap_lists_products',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->sitemap_lists_products( $responses ),
				'blocks'  => 'listing',
				'name'    => __( 'A sitemap lists your products', 'woocommerce' ),
				'label'   => __( 'No sitemap lists your products', 'woocommerce' ),
				'why'     => __( 'Crawlers use the sitemap to find new and changed products.', 'woocommerce' ),
				'fix'     => __( 'Turn on the sitemap in your SEO plugin, or remove the code that turns off the WordPress sitemap, and include products in it.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'product_jsonld_present',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): bool => array() !== $products ),
				'blocks'  => 'listing',
				'name'    => __( 'Product pages have product structured data', 'woocommerce' ),
				'label'   => __( 'No product structured data on product pages', 'woocommerce' ),
				'why'     => __( 'AI shopping agents and search engines read the product\'s JSON-LD to get its name, price and stock without guessing from the page.', 'woocommerce' ),
				'fix'     => __( 'WooCommerce adds it to product pages. Check whether your theme or an SEO plugin removes it, or turn on product schema in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'product_jsonld_required_fields',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): bool => self::has_required_fields( $products ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Product structured data has a name, image and offer', 'woocommerce' ),
				'label'   => __( 'Product structured data is missing a name, image or offer', 'woocommerce' ),
				'why'     => __( 'Without these, agents and search engines can\'t show the product as something to buy.', 'woocommerce' ),
				'fix'     => __( 'Give the product a name, an image and a price. If it has them, check the product schema settings in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'product_offer_jsonld',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): bool => self::has_priced_offer( $products ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Product offer has a price and stock status', 'woocommerce' ),
				'label'   => __( 'Product offer is missing a price or stock status', 'woocommerce' ),
				'why'     => __( 'Agents compare price and availability before they suggest a product.', 'woocommerce' ),
				'fix'     => __( 'Set a price and stock status on the product. If they\'re set, check the product schema settings in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
		);
	}

	/**
	 * The robots.txt crawlers get. '' when the store answered that there is none.
	 * Null when it could not be read: the request failed, or the body is a page.
	 *
	 * Loaded from the host root, also when WordPress is in a subdirectory.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function robots_txt( StoreResponses $responses ): ?string {
		$home     = (array) wp_parse_url( home_url() );
		$response = $responses->get( ( $home['scheme'] ?? 'http' ) . '://' . ( $home['host'] ?? '' ) . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . '/robots.txt' );
		if ( null === $response || ! in_array( $response['status'], array( 200, 404, 410 ), true ) ) {
			return null;
		}
		if ( in_array( $response['status'], array( 404, 410 ), true ) ) {
			return '';
		}
		// A theme or a login wall that answers every URL with a page is not a robots.txt.
		return str_starts_with( ltrim( $response['body'] ), '<' ) ? null : $response['body'];
	}

	/**
	 * Whether a robots.txt was served, or null when it could not be read.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function robots_present( StoreResponses $responses ): ?bool {
		$text = $this->robots_txt( $responses );
		return null === $text ? null : '' !== $text;
	}

	/**
	 * Whether every AI search crawler may load a path under the home URL.
	 * Null when robots.txt could not be read.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param string         $path      Path from the home URL.
	 */
	private function robots_allows( StoreResponses $responses, string $path ): ?bool {
		$text = $this->robots_txt( $responses );
		if ( null === $text ) {
			return null;
		}
		$robots = new RobotsTxt( $text );
		$path   = (string) wp_parse_url( home_url( $path ), PHP_URL_PATH );
		foreach ( self::AI_SEARCH_BOTS as $bot ) {
			if ( ! $robots->allows( $bot, $path ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Whether a sitemap the store serves names products. Null when robots.txt
	 * or one of the candidate sitemaps could not be loaded and none named products.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function sitemap_lists_products( StoreResponses $responses ): ?bool {
		$robots     = $this->robots_txt( $responses );
		$listed     = null === $robots ? array() : array_slice( ( new RobotsTxt( $robots ) )->sitemaps(), 0, self::MAX_LISTED_SITEMAPS );
		$defaults   = array( '/sitemap_index.xml', '/sitemap.xml' );
		if ( function_exists( 'wp_sitemaps_get_server' ) && wp_sitemaps_get_server()->sitemaps_enabled() ) {
			$defaults[] = '/wp-sitemap.xml';
		}
		$candidates = array_unique( array_merge( $listed, $defaults ) );
		$unknown    = null === $robots;
		foreach ( $candidates as $url ) {
			$response = $responses->get( $url );
			if ( null === $response ) {
				$unknown = true;
			} elseif ( 200 === $response['status'] && self::names_products( $response['body'] ) ) {
				return true;
			}
		}
		return $unknown ? null : false;
	}

	/**
	 * Run a test on the Product items in a product page's JSON-LD, or null when no product page could be loaded.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param callable       $test      Test, given the Product items.
	 */
	private function with_products( StoreResponses $responses, callable $test ): ?bool {
		$page = $responses->product_page();
		return null === $page ? null : (bool) $test( JsonLd::items( $page['body'], 'Product', 'ProductGroup' ) );
	}

	/**
	 * Whether the Product items give a name, an image and an offer, from one source or several.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 */
	private static function has_required_fields( array $products ): bool {
		foreach ( array( 'name', 'image', 'offers' ) as $field ) {
			if ( array() === array_filter( $products, static fn( array $product ): bool => ! empty( $product[ $field ] ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Whether one offer of the Product items has both a price and an availability. A price
	 * can be the offer's price, an AggregateOffer's lowPrice or a price specification's price.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 */
	private static function has_priced_offer( array $products ): bool {
		$offers = array();
		foreach ( $products as $product ) {
			array_push( $offers, ...self::as_list( $product['offers'] ?? null ) );
		}
		// An AggregateOffer can list its offers; read them too, one level down.
		$nested = array();
		foreach ( $offers as $offer ) {
			array_push( $nested, ...self::as_list( $offer['offers'] ?? null ) );
		}
		foreach ( array_merge( $offers, $nested ) as $offer ) {
			$prices = array_merge( array( $offer['price'] ?? null, $offer['lowPrice'] ?? null ), array_column( self::as_list( $offer['priceSpecification'] ?? null ), 'price' ) );
			if ( ! empty( $offer['availability'] ) && array() !== array_filter( $prices, 'is_numeric' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * A JSON-LD value that may be one object or a list of them, as a list of objects.
	 *
	 * @param mixed $value Value.
	 * @return array<int, array<string, mixed>>
	 */
	private static function as_list( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_values( array_filter( wp_is_numeric_array( $value ) ? $value : array( $value ), 'is_array' ) );
	}

	/**
	 * Whether a sitemap index names a product sitemap (product-sitemap.xml,
	 * wp-sitemap-posts-product-1.xml), or a urlset lists a product page. A category
	 * sitemap and a page that isn't a sitemap never count.
	 *
	 * @param string $xml Sitemap body.
	 */
	private static function names_products( string $xml ): bool {
		if ( ! preg_match( '#<(urlset|sitemapindex)[\s>]#i', $xml ) ) {
			return false;
		}
		if ( 1 === preg_match( '#<loc>[^<]*(?:product-sitemap|wp-sitemap-posts-product-)[^<]*\.xml\s*</loc>#i', $xml ) ) {
			return true;
		}
		// The fixed part of the product base, before a tag such as %product_cat%.
		$base = trim( (string) strtok( (string) ( wc_get_permalink_structure()['product_base'] ?? '' ), '%' ), '/' );
		return '' !== $base && 1 === preg_match( '#<loc>[^<]*/' . preg_quote( $base, '#' ) . '/[^<]*</loc>#i', $xml );
	}
}
