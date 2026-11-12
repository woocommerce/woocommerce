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

	private const MAX_LISTED_SITEMAPS = 5;

	private const MAX_CHILD_SITEMAPS = 5;

	/**
	 * Statuses that answer whether a file exists. Anything else (a firewall's 403, a 5xx) is couldn't check.
	 */
	private const ANSWER_STATUSES = array( 200, 404, 410 );

	/**
	 * The checks, each with a test that returns true, false, or null for couldn't check.
	 *
	 * @return array<int, array{id:string,test:callable(StoreResponses):(bool|null),blocks:string,name:string,label:string,why:string,fix:string,fix_url:string}>
	 */
	protected function checks(): array {
		return array(
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
		if ( null === $response || ! in_array( $response['status'], self::ANSWER_STATUSES, true ) ) {
			return null;
		}
		if ( in_array( $response['status'], array( 404, 410 ), true ) ) {
			return '';
		}
		// A theme or a login wall that answers every URL with a page is not a robots.txt.
		return str_starts_with( ltrim( $response['body'] ), '<' ) ? null : $response['body'];
	}

	/**
	 * Whether a sitemap the store serves names products. An index that does not
	 * has its child sitemaps on this host opened, at any depth, up to five child
	 * requests in all. Null when robots.txt or one of those sitemaps could not
	 * be loaded and none named products.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function sitemap_lists_products( StoreResponses $responses ): ?bool {
		$robots   = $this->robots_txt( $responses );
		$listed   = null === $robots ? array() : array_slice(
			array_values( array_filter( ( new RobotsTxt( $robots ) )->sitemaps(), array( StoreResponses::class, 'is_store_url' ) ) ),
			0,
			self::MAX_LISTED_SITEMAPS
		);
		$defaults = array( '/sitemap_index.xml', '/sitemap.xml' );
		if ( function_exists( 'wp_sitemaps_get_server' ) && wp_sitemaps_get_server()->sitemaps_enabled() ) {
			$defaults[] = '/wp-sitemap.xml';
		}
		$queue   = array_values( array_unique( array_map( array( self::class, 'absolute_url' ), array_merge( $listed, $defaults ) ) ) );
		$seen    = array_fill_keys( $queue, true );
		$budget  = count( $queue ) + self::MAX_CHILD_SITEMAPS;
		$unknown = null === $robots;
		for ( $i = 0; isset( $queue[ $i ] ) && $i < $budget; $i++ ) {
			$body = $this->sitemap_body( $responses, $queue[ $i ], $unknown );
			if ( null === $body ) {
				continue;
			}
			if ( self::names_products( $body ) ) {
				return true;
			}
			foreach ( self::index_child_urls( $body ) as $child ) {
				if ( ! StoreResponses::is_store_url( $child ) ) {
					continue;
				}
				$child = self::absolute_url( $child );
				if ( ! isset( $seen[ $child ] ) ) {
					$seen[ $child ] = true;
					$queue[]        = $child;
				}
			}
		}
		return $unknown ? null : false;
	}

	/**
	 * A path from the home URL as a full URL, so one sitemap is one queue entry however it was written.
	 *
	 * @param string $url URL or path.
	 */
	private static function absolute_url( string $url ): string {
		return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
	}

	/**
	 * Body of a sitemap response with status 200, or null. A failed request, or
	 * any status other than 200, 404 or 410 (a firewall's 403, a 5xx), marks the check unknown.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param string         $url       URL or path.
	 * @param bool           $unknown   Whether a sitemap could not be loaded. Passed by reference.
	 * @return string|null
	 */
	private function sitemap_body( StoreResponses $responses, string $url, bool &$unknown ): ?string {
		$response = $responses->get( $url );
		if ( null === $response || ! in_array( $response['status'], self::ANSWER_STATUSES, true ) ) {
			$unknown = true;
			return null;
		}
		return 200 === $response['status'] ? $response['body'] : null;
	}

	/**
	 * Child sitemap URLs listed in a sitemap index. Empty when the body is not an index.
	 *
	 * @param string $xml Sitemap body.
	 * @return array<int, string>
	 */
	private static function index_child_urls( string $xml ): array {
		if ( false === stripos( $xml, '<sitemapindex' ) ) {
			return array();
		}
		$urls   = array();
		$offset = 0;
		$start  = stripos( $xml, '<loc>', $offset );
		while ( false !== $start ) {
			$start += strlen( '<loc>' );
			$end    = stripos( $xml, '</loc>', $start );
			if ( false === $end ) {
				break;
			}
			$url = trim( str_replace( array( '<![CDATA[', ']]>' ), '', substr( $xml, $start, $end - $start ) ) );
			if ( '' !== $url ) {
				$urls[] = $url;
			}
			$offset = $end + strlen( '</loc>' );
			$start  = stripos( $xml, '<loc>', $offset );
		}
		return $urls;
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
		return '' !== $base && 1 === preg_match( '#<loc>[^<]*/' . preg_quote( $base, '#' ) . '/[^<]+</loc>#i', $xml );
	}
}
