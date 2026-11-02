<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

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
	 * @return array<int, array{id:string,ok:bool|null,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string,pending:string}>
	 */
	public function all(): array {
		$responses = new StoreResponses( $this->coming_soon_helper->is_site_live() );
		// The home page first: when it can't be reached, no other request is made.
		$responses->home_page();
		$results = array();
		foreach ( $this->checks() as $check ) {
			$pending = self::PENDING[ $check['id'] ] ?? '';
			try {
				$ok = '' === $pending ? ( $check['test'] )( $responses ) : null;
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
				'pending' => $pending,
			);
		}
		return $results;
	}

	/**
	 * Checks for output WooCommerce doesn't build yet, with the issue that builds it. They don't run
	 * and are reported as pending, not failed; remove the entry when the output ships.
	 */
	private const PENDING = array(
		'product_return_policy_schema' => 'WOOPRD-3912',
		'product_shipping_schema'      => 'WOOPRD-3908',
		'product_buy_action_schema'    => 'WOOPRD-3918',
		'ucp_checkout'                 => 'WOOAI-1435',
	);

	private const MAX_LISTED_SITEMAPS = 5;

	private const MAX_LLMS_TXT_LINKS = 5;

	/**
	 * Organization and the subtypes stores use for themselves.
	 */
	private const ORGANIZATION_TYPES = array( 'Organization', 'OnlineStore', 'OnlineBusiness', 'Corporation', 'LocalBusiness', 'Store' );

	/**
	 * The checks, each with a test that returns true, false, or null for couldn't check.
	 *
	 * @return array<int, array{id:string,test:callable(StoreResponses):(bool|null),blocks:string,name:string,label:string,why:string,fix:string,fix_url:string}>
	 */
	protected function checks(): array {
		$checks = array(
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
				'name'    => __( 'The newest product page has product structured data', 'woocommerce' ),
				'label'   => __( 'The newest product page has no product structured data', 'woocommerce' ),
				'why'     => __( 'Shopping crawlers and search engines read the product\'s JSON-LD to get its name, price and stock without guessing from the page.', 'woocommerce' ),
				'fix'     => __( 'WooCommerce adds it to the product page. Check whether your theme or an SEO plugin removes it, or turn on product schema in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'product_jsonld_required_fields',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): ?bool => array() === $products ? null : self::has_required_fields( $products ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Product structured data has a name, image and offer', 'woocommerce' ),
				'label'   => __( 'Product structured data is missing a name, image or offer', 'woocommerce' ),
				'why'     => __( 'Without these, agents and search engines can\'t show the product as something to buy.', 'woocommerce' ),
				'fix'     => __( 'Give the product a name, an image and a price. If it has them, check the product schema settings in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'product_offer_jsonld',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): ?bool => array() === $products ? null : self::has_priced_offer( $products ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Product offer has a price and stock status', 'woocommerce' ),
				'label'   => __( 'Product offer is missing a price or stock status', 'woocommerce' ),
				'why'     => __( 'Agents compare price and availability before they suggest a product.', 'woocommerce' ),
				'fix'     => __( 'Set a price and stock status on the product. If they\'re set, check the product schema settings in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'llms_txt_present',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_llms_txt( $responses, '__return_true' ),
				'blocks'  => 'listing',
				'name'    => __( 'Store serves an llms.txt file', 'woocommerce' ),
				'label'   => __( 'No llms.txt file', 'woocommerce' ),
				'why'     => __( 'llms.txt gives AI agents a short summary of your store and links to its main pages.', 'woocommerce' ),
				'fix'     => __( 'Turn on llms.txt in your SEO plugin, or add an llms.txt file to your site\'s root folder.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'llms_txt_valid',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_llms_txt( $responses, static fn( string $llms_txt ): bool => 1 === preg_match( '/\A\s*#[ \t]+\S/', $llms_txt ) ),
				'blocks'  => 'listing',
				'name'    => __( 'llms.txt starts with the store\'s name as a heading', 'woocommerce' ),
				'label'   => __( 'llms.txt doesn\'t follow the format', 'woocommerce' ),
				'why'     => __( 'The format asks for a "# Name" heading first; agents use it to know whose file they read.', 'woocommerce' ),
				'fix'     => __( 'Start llms.txt with a line such as "# Your store name", in your SEO plugin or the file in your site\'s root folder.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'llms_txt_links_resolve',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->llms_txt_links_resolve( $responses ),
				'blocks'  => 'listing',
				'name'    => __( 'Links in llms.txt open', 'woocommerce' ),
				'label'   => __( 'llms.txt links to pages that don\'t open', 'woocommerce' ),
				'why'     => __( 'Agents follow the links in llms.txt to read your store; a broken link wastes the visit.', 'woocommerce' ),
				'fix'     => __( 'Update or remove the broken links in llms.txt, in your SEO plugin or the file in your site\'s root folder.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'open_graph_tags',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_pages( $responses, static fn( string $html ): bool => self::has_open_graph( $html, 'og:title' ) && self::has_open_graph( $html, 'og:image' ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Pages have Open Graph tags', 'woocommerce' ),
				'label'   => __( 'Pages are missing Open Graph tags', 'woocommerce' ),
				'why'     => __( 'Agents and social apps use og:title and og:image to show a page as a card with a picture.', 'woocommerce' ),
				'fix'     => __( 'Turn on Open Graph or social tags in your SEO plugin, or in Jetpack.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'meta_description',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_pages( $responses, static fn( string $html ): bool => PageMarkup::has_meta( $html, 'name', 'description' ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Pages have a meta description', 'woocommerce' ),
				'label'   => __( 'Pages are missing a meta description', 'woocommerce' ),
				'why'     => __( 'Search and AI answers often quote the meta description to say what a page is about.', 'woocommerce' ),
				'fix'     => __( 'Add a meta description for your home page and products in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'canonical_url',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_product_page( $responses, static fn( string $html ): bool => '' !== PageMarkup::canonical( $html ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Product pages name their canonical URL', 'woocommerce' ),
				'label'   => __( 'Product pages don\'t name a canonical URL', 'woocommerce' ),
				'why'     => __( 'A product can be reached by several URLs; the canonical link tells agents which one to show.', 'woocommerce' ),
				'fix'     => __( 'WordPress adds it to product pages. Check whether your theme or a plugin removes it.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'organization_schema',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_home_page( $responses, static fn( string $html ): bool => array() !== JsonLd::items( $html, ...self::ORGANIZATION_TYPES ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Home page describes the store as an organization', 'woocommerce' ),
				'label'   => __( 'Home page has no organization structured data', 'woocommerce' ),
				'why'     => __( 'Organization JSON-LD gives agents your store\'s name, logo and contact details in one place.', 'woocommerce' ),
				'fix'     => __( 'Fill in your organization or business details in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'ai_snippet_directives',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->snippets_allowed( $responses ),
				'blocks'  => 'listing',
				'name'    => __( 'Pages let search and AI answers quote them', 'woocommerce' ),
				'label'   => __( 'Pages ask search and AI answers not to quote them', 'woocommerce' ),
				'why'     => __( 'A nosnippet or max-snippet robots rule keeps AI answers from quoting your pages, so your products get left out.', 'woocommerce' ),
				'fix'     => __( 'Remove nosnippet and max-snippet from the robots settings in your SEO plugin, or ask your host about an X-Robots-Tag header.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'content_in_raw_html',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->product_in_raw_html( $responses ),
				'blocks'  => 'listing',
				'name'    => __( 'Product name and price are in the page without JavaScript', 'woocommerce' ),
				'label'   => __( 'Product name or price only shows with JavaScript', 'woocommerce' ),
				'why'     => __( 'Most AI crawlers don\'t run JavaScript, so they miss what only appears after it runs.', 'woocommerce' ),
				'fix'     => __( 'Use a theme or page builder that prints the product name and price in the page, or ask its developer.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'product_return_policy_schema',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): bool => self::offer_has( $products, 'hasMerchantReturnPolicy' ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Product structured data links your returns policy', 'woocommerce' ),
				'label'   => __( 'Product structured data has no returns policy', 'woocommerce' ),
				'why'     => __( 'Agents tell shoppers whether they can return an item before they buy, and read it from the product\'s structured data.', 'woocommerce' ),
				'fix'     => __( 'Publish your refund and returns page and choose it under Store policies.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'product_shipping_schema',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): bool => self::offer_has( $products, 'shippingDetails' ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Product structured data gives shipping details', 'woocommerce' ),
				'label'   => __( 'Product structured data has no shipping details', 'woocommerce' ),
				'why'     => __( 'Agents compare delivery options, and leave out products whose shipping they can\'t tell.', 'woocommerce' ),
				'fix'     => __( 'Set up shipping zones so WooCommerce can add shipping details to your products.', 'woocommerce' ),
				'fix_url' => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
			),
			array(
				'id'      => 'product_buy_action_schema',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): bool => self::has_buy_action( $products ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Product structured data says how to buy', 'woocommerce' ),
				'label'   => __( 'Product structured data has no buy action', 'woocommerce' ),
				'why'     => __( 'A BuyAction tells agents where to send a shopper to buy the product.', 'woocommerce' ),
				'fix'     => __( 'Check whether another plugin replaces WooCommerce\'s product structured data.', 'woocommerce' ),
				'fix_url' => '',
			),
		);
		if ( ! UcpUtils::is_enabled() ) {
			return $checks;
		}
		// With UCP off nothing is served, which the ucp_on store condition reports already.
		$checks[] = array(
			'id'      => 'ucp_profile',
			'test'    => fn( StoreResponses $responses ): ?bool => ( $responses->get( '/.well-known/ucp' )['status'] ?? 500 ) >= 500 ? null : null !== self::ucp_endpoint( $responses ),
			'blocks'  => 'ucp',
			'name'    => __( 'Store serves its UCP profile', 'woocommerce' ),
			'label'   => __( 'UCP profile isn\'t served', 'woocommerce' ),
			'why'     => __( 'Agents read /.well-known/ucp to find your catalog. UCP is on, but visitors don\'t get the profile.', 'woocommerce' ),
			'fix'     => __( 'Save Settings > Permalinks to refresh the rules. If it still fails, ask your host whether caching or a firewall blocks /.well-known/ucp.', 'woocommerce' ),
			'fix_url' => admin_url( 'options-permalink.php' ),
		);
		$checks[] = array(
			'id'      => 'ucp_profile_version',
			'test'    => fn( StoreResponses $responses ): ?bool => self::profile_version_matches( $responses ),
			'blocks'  => 'ucp',
			/* translators: %s: UCP version, a date such as 2026-04-08. */
			'name'    => sprintf( __( 'UCP profile declares version %s', 'woocommerce' ), UcpUtils::VERSION ),
			'label'   => __( 'UCP profile declares a different version', 'woocommerce' ),
			'why'     => __( 'Agents use the version to know which requests your store accepts. A different version usually means another plugin serves its own UCP profile.', 'woocommerce' ),
			'fix'     => __( 'Look for another plugin that adds UCP or agentic commerce, and turn off its UCP profile.', 'woocommerce' ),
			'fix_url' => admin_url( 'plugins.php' ),
		);
		$checks[] = array(
			'id'      => 'ucp_endpoints_live',
			'test'    => fn( StoreResponses $responses ): ?bool => self::catalog_search_answers( $responses ),
			'blocks'  => 'ucp',
			'name'    => __( 'UCP catalog search answers', 'woocommerce' ),
			'label'   => __( 'UCP catalog search doesn\'t answer', 'woocommerce' ),
			'why'     => __( 'Agents search your catalog through UCP; when it fails they can\'t find your products.', 'woocommerce' ),
			'fix'     => __( 'Ask your host or security plugin to allow requests to the WordPress REST API under /wp-json/wc/ucp/.', 'woocommerce' ),
			'fix_url' => '',
		);
		$checks[] = array(
			'id'      => 'ucp_checkout',
			'test'    => fn( StoreResponses $responses ): ?bool => self::profile_declares( $responses, 'dev.ucp.shopping.checkout' ),
			'blocks'  => 'ucp',
			'name'    => __( 'UCP profile offers checkout', 'woocommerce' ),
			'label'   => __( 'UCP profile doesn\'t offer checkout', 'woocommerce' ),
			'why'     => __( 'Agents build a cart and hand the shopper to your checkout through UCP checkout.', 'woocommerce' ),
			'fix'     => __( 'Look for another plugin that adds UCP or agentic commerce, and turn off its UCP profile.', 'woocommerce' ),
			'fix_url' => admin_url( 'plugins.php' ),
		);
		return $checks;
	}

	/**
	 * A path at the host root, including when WordPress lives in a subdirectory.
	 *
	 * @param string $path Path from the host root, such as /llms.txt.
	 */
	private static function host_root( string $path ): string {
		$home = (array) wp_parse_url( home_url() );
		return ( $home['scheme'] ?? 'http' ) . '://' . ( $home['host'] ?? '' ) . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . $path;
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
		$response = $responses->get( self::host_root( '/robots.txt' ) );
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
	 * Whether a sitemap the store serves names products. Null when robots.txt
	 * or one of the candidate sitemaps could not be loaded and none named products.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function sitemap_lists_products( StoreResponses $responses ): ?bool {
		$robots   = $this->robots_txt( $responses );
		$listed   = null === $robots ? array() : array_values(
			array_filter(
				array_slice( ( new RobotsTxt( $robots ) )->sitemaps(), 0, self::MAX_LISTED_SITEMAPS ),
				array( StoreResponses::class, 'is_store_url' )
			)
		);
		$defaults = array( '/sitemap_index.xml', '/sitemap.xml' );
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
		if ( null === $page ) {
			return null;
		}
		$result = $test( JsonLd::items( $page['body'], 'Product', 'ProductGroup' ) );
		return null === $result ? null : (bool) $result;
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
			if ( ! empty( $offer['availability'] ) && array() !== array_filter( $prices, array( self::class, 'has_price_amount' ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Run a test on the product page's HTML, or null when it couldn't be loaded.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param callable       $test      Test, given the HTML.
	 */
	private function on_product_page( StoreResponses $responses, callable $test ): ?bool {
		$page = $responses->product_page();
		return null === $page ? null : (bool) $test( $page['body'] );
	}

	/**
	 * Run a test on the home page's HTML, or null when it couldn't be loaded.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param callable       $test      Test, given the HTML.
	 */
	private function on_home_page( StoreResponses $responses, callable $test ): ?bool {
		$page = $responses->home_page();
		return null === $page ? null : (bool) $test( $page['body'] );
	}

	/**
	 * Whether a test passes on the home page and the product page, of those that loaded.
	 * Null when neither loaded.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param callable       $test      Test, given the HTML and the response headers.
	 */
	private function on_pages( StoreResponses $responses, callable $test ): ?bool {
		$pages = array_filter( array( $responses->home_page(), $responses->product_page() ) );
		if ( array() === $pages ) {
			return null;
		}
		foreach ( $pages as $page ) {
			if ( ! $test( $page['body'], $page['headers'] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Whether an Open Graph tag has content. Open Graph uses the property attribute; some plugins write name.
	 *
	 * @param string $html Page HTML.
	 * @param string $tag  Tag, such as og:title.
	 */
	private static function has_open_graph( string $html, string $tag ): bool {
		return PageMarkup::has_meta( $html, 'property', $tag ) || PageMarkup::has_meta( $html, 'name', $tag );
	}

	/**
	 * Run a test on the served llms.txt: false when there is none, null when it couldn't be loaded.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param callable       $test      Test, given the file.
	 */
	private function with_llms_txt( StoreResponses $responses, callable $test ): ?bool {
		$response = $responses->get( self::host_root( '/llms.txt' ) );
		if ( null === $response || $response['status'] >= 500 ) {
			return null;
		}
		$body = trim( $response['body'] );
		// A theme that answers every URL with a page has no llms.txt.
		if ( 200 !== $response['status'] || '' === $body || str_starts_with( $body, '<' ) ) {
			return false;
		}
		return (bool) $test( $body );
	}

	/**
	 * Whether the first few links in llms.txt to the store's own pages open. Links to other sites
	 * aren't loaded. Null when one couldn't be loaded and none was broken.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function llms_txt_links_resolve( StoreResponses $responses ): ?bool {
		$links = array();
		$has   = $this->with_llms_txt(
			$responses,
			static function ( string $llms_txt ) use ( &$links ): bool {
				preg_match_all( '/\]\(\s*(https?:\/\/[^\s)]+)/i', $llms_txt, $matches );
				$links = $matches[1];
				return true;
			}
		);
		if ( true !== $has ) {
			return $has;
		}
		// Links with a query string are skipped: one such as ?add-to-cart=12 would act on the store.
		$links   = array_filter( $links, static fn( string $link ): bool => $responses->is_store_url( $link ) && ! str_contains( $link, '?' ) );
		$links   = array_slice( array_values( array_unique( $links ) ), 0, self::MAX_LLMS_TXT_LINKS );
		$unknown = false;
		foreach ( $links as $link ) {
			$response = $responses->get( $link );
			if ( null === $response || $response['status'] >= 500 ) {
				$unknown = true;
			} elseif ( $response['status'] >= 400 ) {
				return false;
			}
		}
		return $unknown ? null : true;
	}

	/**
	 * Whether no robots meta tag or X-Robots-Tag header on the home and product page sets
	 * nosnippet, or a max-snippet other than -1 (no limit). Rules for one crawler count too.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function snippets_allowed( StoreResponses $responses ): ?bool {
		return $this->on_pages(
			$responses,
			static function ( string $html, array $headers ): bool {
				$rules   = PageMarkup::meta( $html, 'name', static fn( string $name ): bool => 'robots' === $name || str_ends_with( $name, 'bot' ) );
				$rules[] = $headers['x-robots-tag'] ?? '';
				$rules   = strtolower( implode( ', ', $rules ) );
				if ( preg_match( '/(?<![\w-])nosnippet\b/', $rules ) ) {
					return false;
				}
				preg_match_all( '/max-snippet\s*:\s*(-?\d+)/', $rules, $limits );
				return array() === array_diff( $limits[1], array( '-1' ) );
			}
		);
	}

	/**
	 * Whether the product page's text, without running JavaScript, has the product's name and, when it has one, its price.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function product_in_raw_html( StoreResponses $responses ): ?bool {
		$product = $responses->product();
		return $this->on_product_page(
			$responses,
			static function ( string $html ) use ( $product ): bool {
				$text = PageMarkup::text( $html );
				if ( null === $product || ! str_contains( $text, html_entity_decode( $product->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) {
					return false;
				}
				if ( '' === $product->get_price() ) {
					return true;
				}
				// The amount as the store prints it, with its separators and filters, without the currency symbol.
				$amount = PageMarkup::text( wc_price( wc_get_price_to_display( $product ) ) );
				$amount = trim( (string) preg_replace( '/[^\d.,\x{00A0}\x{2009}\x{202F}\' ]/u', '', $amount ) );
				if ( '' === $amount ) {
					return false;
				}
				return 1 === preg_match( '/(?<!\d)' . preg_quote( $amount, '/' ) . '(?!\d)/', $text );
			}
		);
	}

	/**
	 * The UCP REST endpoint the served profile names, or null when the profile isn't served or names none.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private static function ucp_endpoint( StoreResponses $responses ): ?string {
		$profile = self::served_profile( $responses );
		foreach ( (array) ( $profile['ucp']['services']['dev.ucp.shopping'] ?? array() ) as $service ) {
			if ( is_array( $service ) && 'rest' === ( $service['transport'] ?? '' ) && is_string( $service['endpoint'] ?? null ) && '' !== $service['endpoint'] ) {
				return $service['endpoint'];
			}
		}
		return null;
	}

	/**
	 * The served UCP profile, or null when it isn't served as a UCP profile.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @return array<string, mixed>|null
	 */
	private static function served_profile( StoreResponses $responses ): ?array {
		$response = $responses->get( '/.well-known/ucp' );
		$profile  = null !== $response && 200 === $response['status'] ? json_decode( $response['body'], true ) : null;
		return is_array( $profile ) && is_array( $profile['ucp'] ?? null ) ? $profile : null;
	}

	/**
	 * Whether the served profile declares the UCP version WooCommerce implements. Null when no
	 * profile is served, which the ucp_profile check reports.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private static function profile_version_matches( StoreResponses $responses ): ?bool {
		$profile = self::served_profile( $responses );
		return null === $profile ? null : UcpUtils::VERSION === ( $profile['ucp']['version'] ?? null );
	}

	/**
	 * Whether the served profile declares a capability. Null when no profile is served.
	 *
	 * @param StoreResponses $responses  Store responses.
	 * @param string         $capability Capability name.
	 */
	private static function profile_declares( StoreResponses $responses, string $capability ): ?bool {
		$profile = self::served_profile( $responses );
		return null === $profile ? null : ! empty( $profile['ucp']['capabilities'][ $capability ] );
	}

	/**
	 * Whether an offer of the Product items has a property.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @param string                           $property Offer property.
	 */
	private static function offer_has( array $products, string $property ): bool {
		foreach ( $products as $product ) {
			foreach ( self::as_list( $product['offers'] ?? null ) as $offer ) {
				if ( ! empty( $offer[ $property ] ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Whether a Product item or one of its offers has a BuyAction potentialAction.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 */
	private static function has_buy_action( array $products ): bool {
		foreach ( $products as $product ) {
			$items = array_merge( array( $product ), self::as_list( $product['offers'] ?? null ) );
			foreach ( $items as $item ) {
				foreach ( self::as_list( $item['potentialAction'] ?? null ) as $action ) {
					if ( in_array( 'BuyAction', (array) ( $action['@type'] ?? array() ), true ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Whether a catalog search through the UCP endpoint answers with a product list, using
	 * the endpoint in the served profile, or WooCommerce's when the profile isn't served.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private static function catalog_search_answers( StoreResponses $responses ): ?bool {
		$endpoint = self::ucp_endpoint( $responses ) ?? rest_url( 'wc/ucp/v1' );
		$response = $responses->post_json(
			untrailingslashit( $endpoint ) . '/catalog/search',
			array(
				'filters'    => array( 'price' => array( 'min' => 0 ) ),
				'pagination' => array( 'limit' => 1 ),
			)
		);
		if ( null === $response || $response['status'] >= 500 ) {
			return null;
		}
		$body = json_decode( $response['body'], true );
		return 200 === $response['status'] && is_array( $body['products'] ?? null );
	}

	/**
	 * Whether a JSON-LD price is a number, or text that contains one (`10.00`, `10,00`, `10.00 USD`).
	 *
	 * @param mixed $value Price value.
	 */
	private static function has_price_amount( $value ): bool {
		if ( is_int( $value ) || is_float( $value ) ) {
			return is_finite( $value );
		}
		if ( ! is_string( $value ) ) {
			return false;
		}
		$normalized = str_replace( array( ',', ' ', "\u{00A0}" ), array( '.', '', '' ), $value );
		return 1 === preg_match( '/-?\d+(?:\.\d+)?/', $normalized );
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
		return '' !== $base && 1 === preg_match( '#<loc>[^<]*/' . preg_quote( $base, '#' ) . '/[^<]+</loc>#i', $xml );
	}
}
