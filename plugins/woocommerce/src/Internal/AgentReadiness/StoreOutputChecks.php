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
		$robots_fix = __( 'Edit robots.txt in your SEO plugin, or ask your host to change the robots.txt file in your site\'s root folder.', 'woocommerce' );
		return array(
			array(
				'id'      => 'robots_txt_present',
				'test'    => fn( StoreResponses $responses ): bool => '' !== $this->robots_txt( $responses ),
				'blocks'  => 'listing',
				'name'    => __( 'Store serves a robots.txt file', 'woocommerce' ),
				'label'   => __( 'No robots.txt file', 'woocommerce' ),
				'why'     => __( 'Crawlers, including AI shopping crawlers, read robots.txt to find your sitemap.', 'woocommerce' ),
				'fix'     => __( 'Choose any permalink structure other than Plain under Settings > Permalinks, so WordPress serves robots.txt.', 'woocommerce' ),
				'fix_url' => admin_url( 'options-permalink.php' ),
			),
			array(
				'id'      => 'robots_allows_ai_search_bots',
				'test'    => fn( StoreResponses $responses ): bool => $this->robots_allows( $responses, '/' ),
				'blocks'  => 'listing',
				'name'    => __( 'robots.txt lets AI search crawlers in', 'woocommerce' ),
				'label'   => __( 'robots.txt blocks AI search crawlers', 'woocommerce' ),
				'why'     => __( 'OAI-SearchBot, ChatGPT-User, Claude-User and PerplexityBot fetch your pages while answering shoppers; a blocked crawler can\'t show your products.', 'woocommerce' ),
				'fix'     => $robots_fix,
				'fix_url' => '',
			),
			array(
				'id'      => 'robots_not_blocking_llms_txt',
				'test'    => fn( StoreResponses $responses ): bool => $this->robots_allows( $responses, '/llms.txt' ),
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
		);
	}

	/**
	 * The robots.txt crawlers get, or '' when there is none.
	 *
	 * Loaded from the store first, so a file at the site root and a host's or plugin's
	 * changes count. When the store can't load it, or answers with an error or a login,
	 * it is read the way WordPress serves it.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function robots_txt( StoreResponses $responses ): string {
		// Crawlers read robots.txt at the host root, also when WordPress is in a subdirectory.
		$home     = (array) wp_parse_url( home_url() );
		$response = $responses->get( ( $home['scheme'] ?? 'http' ) . '://' . ( $home['host'] ?? '' ) . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . '/robots.txt' );
		if ( null !== $response && 200 === $response['status'] ) {
			// A theme that answers every URL with a page has no robots.txt.
			return str_starts_with( ltrim( $response['body'] ), '<' ) ? '' : $response['body'];
		}
		if ( null !== $response && in_array( $response['status'], array( 404, 410 ), true ) ) {
			return '';
		}
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$file = get_home_path() . 'robots.txt';
		if ( is_readable( $file ) ) {
			return (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local file.
		}
		global $wp_rewrite;
		if ( ! $wp_rewrite->using_permalinks() ) {
			return '';
		}
		$public = (string) get_option( 'blog_public' );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Core's hook, documented in do_robots().
		return (string) apply_filters( 'robots_txt', "User-agent: *\n" . ( '0' === $public ? "Disallow: /\n" : '' ), $public );
	}

	/**
	 * Whether every AI search crawler may load a path under the home URL.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param string         $path      Path from the home URL.
	 */
	private function robots_allows( StoreResponses $responses, string $path ): bool {
		$robots = new RobotsTxt( $this->robots_txt( $responses ) );
		$path   = (string) wp_parse_url( home_url( $path ), PHP_URL_PATH );
		foreach ( self::AI_SEARCH_BOTS as $bot ) {
			if ( ! $robots->allows( $bot, $path ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Whether the WordPress sitemap includes products, or a sitemap the store serves names them.
	 * Null when no sitemap was found and one of the candidates couldn't be loaded.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function sitemap_lists_products( StoreResponses $responses ): ?bool {
		if ( function_exists( 'wp_sitemaps_get_server' ) && wp_sitemaps_get_server()->sitemaps_enabled() ) {
			$provider = wp_sitemaps_get_server()->registry->get_provider( 'posts' );
			if ( $provider && array_key_exists( 'product', $provider->get_object_subtypes() ) ) {
				return true;
			}
		}

		// A few sitemaps from robots.txt at most, so a long list can't hold up the scan.
		$listed     = array_slice( ( new RobotsTxt( $this->robots_txt( $responses ) ) )->sitemaps(), 0, self::MAX_LISTED_SITEMAPS );
		$candidates = array_unique( array_merge( $listed, array( '/sitemap_index.xml', '/sitemap.xml' ) ) );
		$unknown    = false;
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
	 * Whether a sitemap index names a product sitemap (product-sitemap.xml,
	 * wp-sitemap-posts-product-1.xml), or a sitemap lists a product page. A page
	 * that isn't a sitemap, such as a theme's page for any URL, never counts.
	 *
	 * @param string $xml Sitemap body.
	 */
	private static function names_products( string $xml ): bool {
		if ( ! preg_match( '#<(urlset|sitemapindex)[\s>]#i', $xml ) ) {
			return false;
		}
		// The fixed part of the product base, before a tag such as %product_cat%.
		$base = trim( (string) strtok( (string) ( wc_get_permalink_structure()['product_base'] ?? '' ), '%' ), '/' );
		return 1 === preg_match( '#<loc>[^<]*product[^<]*\.xml\s*</loc>#i', $xml ) || ( '' !== $base && str_contains( $xml, '/' . $base . '/' ) );
	}
}
