<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils as UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Store checks that read what the store returns (robots.txt, sitemaps, page
 * markup), where a plugin can add or replace what core builds. They run only in
 * the background scan, and their results are kept in the stored report in the
 * same shape as StoreConditions.
 *
 * `ok` is true when any source provides the item, false when none does, and null
 * when the store could not load its own page (couldn't check). The product field
 * checks are also null when the page has no Product JSON-LD. These checks are
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
	 * The responses of one scan, shared by everything that reads the store during it.
	 */
	public function responses(): StoreResponses {
		return new StoreResponses( $this->coming_soon_helper->is_site_live() );
	}

	/**
	 * Run every check. Each URL is requested at most once.
	 *
	 * A likely conflict also names the address where it was seen. It doesn't name a plugin: any list of
	 * plugins goes stale, and several can change the same output.
	 *
	 * @return array<int, array{id:string,ok:bool|null,blocks:string,group:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string,conflict:bool,pending:bool,seen_at:string}>
	 */
	public function all(): array {
		return $this->all_for( $this->responses() );
	}

	/**
	 * Run every check against one scan's responses, so other checks can read the same pages.
	 *
	 * @since 11.4.0
	 *
	 * @param StoreResponses $responses Responses of the scan.
	 * @return array<int, array{id:string,ok:bool|null,blocks:string,group:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string,conflict:bool,pending:bool,seen_at:string}>
	 */
	public function all_for( StoreResponses $responses ): array {
		// The home page first: when it can't be reached, no other request is made.
		$responses->home_page();
		$results = array();
		foreach ( $this->checks() as $check ) {
			$pending = in_array( $check['id'], self::PENDING, true );
			try {
				$ok = $pending ? null : ( $check['test'] )( $responses );
			} catch ( \Throwable $e ) {
				// Reported as couldn't check, so one broken check doesn't stop the scan.
				$ok = null;
				wc_get_logger()->warning(
					sprintf( 'Agent readiness check %s failed: %s', $check['id'], $e->getMessage() ),
					array( 'source' => 'agent-readiness' )
				);
			}
			$conflict  = false === $ok && ! empty( $check['conflict'] );
			$results[] = array(
				'id'       => $check['id'],
				'ok'       => $ok,
				'blocks'   => $check['blocks'],
				'group'    => $check['group'],
				'level'    => StoreConditions::LEVEL_RECOMMENDED,
				'name'     => $check['name'],
				'label'    => $check['label'],
				'why'      => $check['why'],
				'fix'      => $check['fix'],
				'fix_url'  => $check['fix_url'],
				'conflict' => $conflict,
				'pending'  => $pending,
				'seen_at'  => $conflict ? self::seen_at( (string) ( $check['seen_at'] ?? '' ), $responses ) : '',
			);
		}
		return $results;
	}

	/**
	 * Checks for output WooCommerce doesn't build yet. They don't run and are reported as
	 * pending, not failed; remove the entry when the output ships. The output is built by
	 * WOOPRD-3912 (returns policy), WOOPRD-3908 (shipping), WOOPRD-3918 (buy action) and
	 * WOOAI-1435 (UCP checkout).
	 */
	private const PENDING = array(
		'product_return_policy_schema',
		'product_shipping_schema',
		'product_buy_action_schema',
		'ucp_checkout',
	);

	private const MAX_LISTED_SITEMAPS = 5;

	private const MAX_CHILD_SITEMAPS = 5;

	/**
	 * Statuses that answer whether a file exists. Anything else (a firewall's 403, a 5xx) is couldn't check.
	 */
	private const ANSWER_STATUSES = array( 200, 404, 410 );

	private const MAX_LLMS_TXT_LINKS = 5;

	/**
	 * Organization and the subtypes stores use for themselves.
	 */
	public const ORGANIZATION_TYPES = array( 'Organization', 'OnlineStore', 'OnlineBusiness', 'Corporation', 'LocalBusiness', 'Store' );

	/**
	 * The checks, each with a test that returns true, false, or null for couldn't check.
	 *
	 * @return array<int, array{id:string,test:callable(StoreResponses):(bool|null),blocks:string,group:string,name:string,label:string,why:string,fix:string,fix_url:string,conflict?:bool,seen_at?:string}>
	 */
	protected function checks(): array {
		$checks = array(
			array(
				'id'      => 'sitemap_lists_products',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->sitemap_lists_products( $responses ),
				'blocks'  => 'discovery',
				'name'    => __( 'A sitemap lists your products', 'woocommerce' ),
				'label'   => __( 'No sitemap lists your products', 'woocommerce' ),
				'why'     => __( 'Crawlers use the sitemap to find new and changed products.', 'woocommerce' ),
				'fix'     => __( 'Turn on the sitemap in your SEO plugin, or remove the code that turns off the WordPress sitemap, and include products in it.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'product_jsonld_present',
				'group'   => 'understand',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): bool => array() !== $products ),
				'blocks'  => 'discovery',
				'name'    => __( 'A recent product page has product structured data', 'woocommerce' ),
				'label'   => __( 'A recent product page has no product structured data', 'woocommerce' ),
				'why'     => __( 'Shopping crawlers and search engines read the product\'s JSON-LD to get its name, price and stock without guessing from the page.', 'woocommerce' ),
				'fix'     => __( 'WooCommerce adds it to the product page. Check whether your theme or an SEO plugin turns WooCommerce\'s structured data off.', 'woocommerce' ),
				'fix_url' => '',
				'seen_at' => 'product',
			),
			array(
				'id'       => 'product_jsonld_from_core',
				'group'    => 'understand',
				'test'     => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): ?bool => array() === $products ? null : self::has_only_core_product( $products, $responses ) ),
				'blocks'   => 'discovery',
				'name'     => __( 'Product structured data comes from WooCommerce, once', 'woocommerce' ),
				'label'    => __( 'Something replaces or repeats WooCommerce\'s product structured data', 'woocommerce' ),
				'why'      => __( 'Agents may read values that differ from your product, or two versions of it.', 'woocommerce' ),
				'fix'      => __( 'Usually an SEO plugin or your theme. Check its schema settings; if you chose this, you can ignore it.', 'woocommerce' ),
				'fix_url'  => '',
				'conflict' => true,
				'seen_at'  => 'product',
			),
			array(
				'id'      => 'product_jsonld_required_fields',
				'group'   => 'understand',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): ?bool => array() === $products ? null : self::has_required_fields( $products ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'Product structured data has a name, image and offer', 'woocommerce' ),
				'label'   => __( 'Product structured data is missing a name, image or offer', 'woocommerce' ),
				'why'     => __( 'Without these, agents and search engines can\'t show the product as something to buy.', 'woocommerce' ),
				'fix'     => __( 'Give the product a name, an image and a price. If it has them, check the product schema settings in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'product_offer_jsonld',
				'group'   => 'understand',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): ?bool => array() === $products ? null : self::has_priced_offer( $products ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'Product offer has a price and stock status', 'woocommerce' ),
				'label'   => __( 'Product offer is missing a price or stock status', 'woocommerce' ),
				'why'     => __( 'Agents compare price and availability before they suggest a product.', 'woocommerce' ),
				'fix'     => __( 'Set a price and stock status on the product. If they\'re set, check the product schema settings in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'llms_txt_present',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_llms_txt( $responses, '__return_true' ),
				'blocks'  => 'discovery',
				'name'    => __( 'Store serves an llms.txt file', 'woocommerce' ),
				'label'   => __( 'No llms.txt file', 'woocommerce' ),
				'why'     => __( 'llms.txt gives AI agents a short summary of your store and links to its main pages.', 'woocommerce' ),
				'fix'     => __( 'Turn on llms.txt in your SEO plugin, or add an llms.txt file to your site\'s root folder.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'llms_txt_valid',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_llms_txt( $responses, static fn( string $llms_txt ): bool => 1 === preg_match( '/\A\s*#[ \t]+\S/', $llms_txt ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'llms.txt starts with the store\'s name as a heading', 'woocommerce' ),
				'label'   => __( 'llms.txt doesn\'t follow the format', 'woocommerce' ),
				'why'     => __( 'The format asks for a "# Name" heading first; agents use it to know whose file they read.', 'woocommerce' ),
				'fix'     => __( 'Start llms.txt with a line such as "# Your store name", in your SEO plugin or the file in your site\'s root folder.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'llms_txt_links_resolve',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->llms_txt_links_resolve( $responses ),
				'blocks'  => 'discovery',
				'name'    => __( 'Links in llms.txt open', 'woocommerce' ),
				'label'   => __( 'llms.txt links to pages that don\'t open', 'woocommerce' ),
				'why'     => __( 'Agents follow the links in llms.txt to read your store; a broken link wastes the visit.', 'woocommerce' ),
				'fix'     => __( 'Update or remove the broken links in llms.txt, in your SEO plugin or the file in your site\'s root folder.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'open_graph_tags',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_pages( $responses, static fn( string $html ): bool => self::has_open_graph( $html, 'og:title' ) && self::has_open_graph( $html, 'og:image' ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'Pages have Open Graph tags', 'woocommerce' ),
				'label'   => __( 'Pages are missing Open Graph tags', 'woocommerce' ),
				'why'     => __( 'Agents and social apps use og:title and og:image to show a page as a card with a picture.', 'woocommerce' ),
				'fix'     => __( 'WooCommerce adds them to product pages. For your home page, turn on Open Graph or social tags in your SEO plugin, or in Jetpack.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'meta_description',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => self::on_loaded( array( $responses->home_page(), $responses->product_page(), $responses->shop_page(), $responses->category_page() ), static fn( string $html ): bool => PageMarkup::has_meta( $html, 'name', 'description' ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'Home, product, shop and category pages have a meta description', 'woocommerce' ),
				'label'   => __( 'Some store pages are missing a meta description', 'woocommerce' ),
				'why'     => __( 'Search and AI answers often quote the meta description to say what a page is about.', 'woocommerce' ),
				'fix'     => __( 'Add meta descriptions for your home page, shop, categories and products in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'canonical_url',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_product_page( $responses, static fn( string $html ): bool => '' !== PageMarkup::canonical( $html ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'Product pages name their canonical URL', 'woocommerce' ),
				'label'   => __( 'Product pages don\'t name a canonical URL', 'woocommerce' ),
				'why'     => __( 'A product can be reached by several URLs; the canonical link tells agents which one to show.', 'woocommerce' ),
				'fix'     => __( 'WordPress adds a canonical link to product pages. If it\'s missing, a plugin or your theme is removing it.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'organization_schema',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_home_page( $responses, static fn( string $html ): bool => array() !== JsonLd::items( $html, ...self::ORGANIZATION_TYPES ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'Home page describes the store as an organization', 'woocommerce' ),
				'label'   => __( 'Home page has no organization structured data', 'woocommerce' ),
				'why'     => __( 'Organization JSON-LD gives agents your store\'s name, logo and contact details in one place.', 'woocommerce' ),
				'fix'     => __( 'Fill in your organization or business details in your SEO plugin.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'ai_snippet_directives',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->snippets_allowed( $responses ),
				'blocks'  => 'discovery',
				'name'    => __( 'Pages let search and AI answers quote them', 'woocommerce' ),
				'label'   => __( 'Pages ask search and AI answers not to quote them', 'woocommerce' ),
				'why'     => __( 'A nosnippet or max-snippet robots rule keeps AI answers from quoting your pages, so your products get left out.', 'woocommerce' ),
				'fix'     => __( 'Remove nosnippet and max-snippet from the robots settings in your SEO plugin, or ask your host about an X-Robots-Tag header.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'content_in_raw_html',
				'group'   => 'understand',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->product_in_raw_html( $responses ),
				'blocks'  => 'discovery',
				'name'    => __( 'Product name and price are in the page without JavaScript', 'woocommerce' ),
				'label'   => __( 'Product name or price only shows with JavaScript', 'woocommerce' ),
				'why'     => __( 'Most AI crawlers don\'t run JavaScript, so they miss what only appears after it runs.', 'woocommerce' ),
				'fix'     => __( 'Use a theme or page builder that prints the product name and price in the page, or ask its developer.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'no_noindex_directives',
				'group'   => 'access',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_pages( $responses, static fn( string $html, array $headers ): bool => ! self::restricts_indexing( self::robots_rules( $html, $headers ) ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Pages let search and AI crawlers index them', 'woocommerce' ),
				'label'   => __( 'Pages ask search and AI crawlers not to index them', 'woocommerce' ),
				'why'     => __( 'A noindex, noai or noimageai robots rule keeps your pages and products out of search and AI answers.', 'woocommerce' ),
				'fix'     => __( 'Remove noindex, noai and noimageai from the robots settings in your SEO plugin, or ask your host about an X-Robots-Tag header.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'pages_public',
				'group'   => 'access',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->pages_public( $responses ),
				'blocks'  => 'listing',
				'name'    => __( 'Store pages open without signing in', 'woocommerce' ),
				'label'   => __( 'Store pages ask visitors to sign in', 'woocommerce' ),
				'why'     => __( 'Agents don\'t sign in, so they can\'t read your home, shop or product pages.', 'woocommerce' ),
				'fix'     => __( 'Check the membership, private store or maintenance plugin that asks visitors to sign in.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'sitemap_lastmod',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->sitemap_has_lastmod( $responses ),
				'blocks'  => 'discovery',
				'name'    => __( 'Sitemap says when pages changed', 'woocommerce' ),
				'label'   => __( 'Sitemap has no lastmod dates', 'woocommerce' ),
				'why'     => __( 'Crawlers use lastmod dates to come back for changed prices and stock first.', 'woocommerce' ),
				'fix'     => __( 'Turn on lastmod dates in your SEO plugin\'s sitemap settings.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'rss_feed',
				'group'   => 'find',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_home_page( $responses, static fn( string $html ): bool => self::has_feed_link( $html ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'Home page links to a feed', 'woocommerce' ),
				'label'   => __( 'Home page doesn\'t link to a feed', 'woocommerce' ),
				'why'     => __( 'Some agents follow your RSS or Atom feed to see what is new.', 'woocommerce' ),
				'fix'     => __( 'Check whether your theme or a plugin removes the feed links WordPress adds.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'viewport_meta',
				'group'   => 'understand',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_home_page( $responses, static fn( string $html ): bool => PageMarkup::has_meta( $html, 'name', 'viewport' ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'Home page sets a viewport', 'woocommerce' ),
				'label'   => __( 'Home page has no viewport tag', 'woocommerce' ),
				'why'     => __( 'Browser agents load your pages at a phone or desktop size; without a viewport tag the layout can break.', 'woocommerce' ),
				'fix'     => __( 'Use a theme that adds a viewport meta tag, or ask its developer.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'html_lang_and_charset',
				'group'   => 'understand',
				'test'    => fn( StoreResponses $responses ): ?bool => self::has_lang_and_charset( $responses->home_page() ),
				'blocks'  => 'discovery',
				'name'    => __( 'Home page declares its language and encoding', 'woocommerce' ),
				'label'   => __( 'Home page doesn\'t declare its language or encoding', 'woocommerce' ),
				'why'     => __( 'Agents use the language to answer shoppers in theirs, and the encoding to read names and prices without garbled text.', 'woocommerce' ),
				'fix'     => __( 'Use a theme that sets the lang attribute and a charset meta tag, or ask its developer.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'images_have_alt',
				'group'   => 'understand',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_pages( $responses, static fn( string $html ): bool => self::images_have_alt( $html ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'Images have alt text', 'woocommerce' ),
				'label'   => __( 'Some images have no alt text', 'woocommerce' ),
				'why'     => __( 'Agents that can\'t see images rely on alt text to know what a picture shows.', 'woocommerce' ),
				'fix'     => __( 'Add alt text to images in the media library and to product images. Images your theme adds need its developer.', 'woocommerce' ),
				'fix_url' => admin_url( 'upload.php' ),
			),
			array(
				'id'      => 'single_h1',
				'group'   => 'understand',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->on_product_page( $responses, static fn( string $html ): bool => 1 === self::count_tags( $html, 'h1' ) ),
				'blocks'  => 'discovery',
				'name'    => __( 'Product pages have one main heading', 'woocommerce' ),
				'label'   => __( 'Product pages have no main heading, or more than one', 'woocommerce' ),
				'why'     => __( 'Agents read the H1 heading as the name of the product the page is about.', 'woocommerce' ),
				'fix'     => __( 'Check the product template of your theme or page builder: the product title should be the only H1.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'       => 'schema_matches_product',
				'group'    => 'understand',
				'test'     => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): ?bool => array() === $products ? null : self::schema_matches( $products, $responses->product() ) ),
				'blocks'   => 'discovery',
				'name'     => __( 'Product structured data matches the product', 'woocommerce' ),
				'label'    => __( 'Product structured data shows a different name or price', 'woocommerce' ),
				'why'      => __( 'Agents quote the structured data, so shoppers may see a name or price your store doesn\'t charge.', 'woocommerce' ),
				'fix'      => __( 'Usually an SEO plugin or your theme. Check its schema settings, or clear your page cache.', 'woocommerce' ),
				'fix_url'  => '',
				'conflict' => true,
				'seen_at'  => 'product',
			),
			array(
				'id'       => 'product_gtin_jsonld',
				'group'    => 'understand',
				'test'     => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): ?bool => array() === $products ? null : self::served_gtin_ok( $products, $responses->product() ) ),
				'blocks'   => 'listing',
				'name'     => __( 'Product structured data has a valid GTIN', 'woocommerce' ),
				'label'    => __( 'Product structured data has no valid GTIN', 'woocommerce' ),
				'why'      => __( 'Agents match products by GTIN, and the product page gives none or an invalid one.', 'woocommerce' ),
				'fix'      => __( 'WooCommerce adds the GTIN from the product\'s GTIN field. If it\'s filled in, an SEO plugin or your theme is likely replacing it: check its schema settings.', 'woocommerce' ),
				'fix_url'  => '',
				'conflict' => true,
				'seen_at'  => 'product',
			),
			array(
				'id'       => 'product_brand_jsonld',
				'group'    => 'understand',
				'test'     => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): ?bool => array() === $products ? null : self::served_brand_ok( $products, $responses->product() ) ),
				'blocks'   => 'listing',
				'name'     => __( 'Product structured data has a brand', 'woocommerce' ),
				'label'    => __( 'Product structured data has no brand', 'woocommerce' ),
				'why'      => __( 'ChatGPT needs a brand to list a product. Your product has one, but the page gives agents none.', 'woocommerce' ),
				'fix'      => __( 'WooCommerce adds the product\'s brand. Usually an SEO plugin or your theme replaces it: check its schema settings.', 'woocommerce' ),
				'fix_url'  => '',
				'conflict' => true,
				'seen_at'  => 'product',
			),
			array(
				'id'      => 'product_review_markup',
				'group'   => 'understand',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->reviews_marked_up( $responses ),
				'blocks'  => 'discovery',
				'name'    => __( 'Product reviews are in structured data', 'woocommerce' ),
				'label'   => __( 'Product reviews are missing from structured data', 'woocommerce' ),
				'why'     => __( 'Agents compare ratings between stores; reviews only on the page are easy to miss.', 'woocommerce' ),
				'fix'     => __( 'WooCommerce adds the rating to product structured data. Check whether your theme or an SEO plugin removes it.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'policy_pages_linked',
				'group'   => 'buy',
				'test'    => fn( StoreResponses $responses ): ?bool => self::links_policy_pages( $responses->home_page() ),
				'blocks'  => 'checkout',
				'name'    => __( 'Home page links to your privacy and returns pages', 'woocommerce' ),
				'label'   => __( 'Home page doesn\'t link to your privacy or returns page', 'woocommerce' ),
				'why'     => __( 'Agents look for policy links on the home page before they send a shopper to your checkout.', 'woocommerce' ),
				'fix'     => __( 'Add your privacy policy and refund and returns pages to the footer or a menu.', 'woocommerce' ),
				'fix_url' => '',
			),
			array(
				'id'      => 'product_return_policy_schema',
				'group'   => 'buy',
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
				'group'   => 'buy',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): ?bool => self::ships( $responses ) ? self::offer_has( $products, 'shippingDetails' ) : null ),
				'blocks'  => 'listing',
				'name'    => __( 'Product structured data gives shipping details', 'woocommerce' ),
				'label'   => __( 'Product structured data has no shipping details', 'woocommerce' ),
				'why'     => __( 'Agents compare delivery options, and leave out products whose shipping they can\'t tell.', 'woocommerce' ),
				'fix'     => __( 'Set up shipping zones so WooCommerce can add shipping details to your products.', 'woocommerce' ),
				'fix_url' => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
			),
			array(
				'id'      => 'product_buy_action_schema',
				'group'   => 'buy',
				'test'    => fn( StoreResponses $responses ): ?bool => $this->with_products( $responses, static fn( array $products ): ?bool => self::has_buy_action( $products, $responses->product() ) ),
				'blocks'  => 'listing',
				'name'    => __( 'Product structured data says how to buy', 'woocommerce' ),
				'label'   => __( 'Product structured data has no buy action', 'woocommerce' ),
				'why'     => __( 'A BuyAction tells agents where to send a shopper to buy the product.', 'woocommerce' ),
				'fix'     => __( 'Check whether another plugin replaces WooCommerce\'s product structured data.', 'woocommerce' ),
				'fix_url' => '',
			),
		);
		if ( self::core_open_graph_on() ) {
			$checks[] = array(
				'id'       => 'open_graph_from_core',
				'group'    => 'find',
				'test'     => fn( StoreResponses $responses ): ?bool => $this->on_product_page( $responses, static fn( string $html ): bool => self::has_open_graph( $html, 'og:title' ) ),
				'blocks'   => 'discovery',
				'name'     => __( 'Product pages keep WooCommerce\'s Open Graph tags', 'woocommerce' ),
				'label'    => __( 'Product pages are missing WooCommerce\'s Open Graph tags', 'woocommerce' ),
				'why'      => __( 'WooCommerce adds og:title and og:image to product pages, so agents and social apps can show the product as a card.', 'woocommerce' ),
				'fix'      => __( 'Usually an SEO, social or caching plugin, or your theme, rewrites the page. Check their Open Graph settings.', 'woocommerce' ),
				'fix_url'  => '',
				'conflict' => true,
				'seen_at'  => 'product',
			);
		}
		if ( ! UcpUtils::is_enabled() ) {
			return $checks;
		}
		// With UCP off nothing is served, which the ucp_on store condition reports already.
		$checks[] = array(
			'id'       => 'ucp_profile',
			'group'    => 'access',
			'test'     => fn( StoreResponses $responses ): ?bool => ( $responses->get( '/.well-known/ucp' )['status'] ?? 500 ) >= 500 ? null : null !== self::ucp_endpoint( $responses ),
			'blocks'   => 'ucp',
			'name'     => __( 'Store serves its UCP profile', 'woocommerce' ),
			'label'    => __( 'UCP profile isn\'t served', 'woocommerce' ),
			'why'      => __( 'Agents read /.well-known/ucp to find your catalog. UCP is on, but visitors don\'t get the profile.', 'woocommerce' ),
			'fix'      => __( 'Save Settings > Permalinks to refresh the rules. If it still fails, ask your host whether caching or a firewall blocks /.well-known/ucp.', 'woocommerce' ),
			'fix_url'  => admin_url( 'options-permalink.php' ),
			'conflict' => true,
			'seen_at'  => home_url( '/.well-known/ucp' ),
		);
		$checks[] = array(
			'id'       => 'ucp_profile_version',
			'group'    => 'access',
			'test'     => fn( StoreResponses $responses ): ?bool => self::profile_version_matches( $responses ),
			'blocks'   => 'ucp',
			/* translators: %s: UCP version, a date such as 2026-04-08. */
			'name'     => sprintf( __( 'UCP profile declares version %s', 'woocommerce' ), UcpUtils::VERSION ),
			'label'    => __( 'UCP profile declares a different version', 'woocommerce' ),
			'why'      => __( 'Agents use the version to know which requests your store accepts. A different version usually means another plugin serves its own UCP profile.', 'woocommerce' ),
			'fix'      => __( 'Look for another plugin that adds UCP or agentic commerce, and turn off its UCP profile.', 'woocommerce' ),
			'fix_url'  => admin_url( 'plugins.php' ),
			'conflict' => true,
			'seen_at'  => home_url( '/.well-known/ucp' ),
		);
		$checks[] = array(
			'id'      => 'ucp_endpoints_live',
			'group'   => 'access',
			'test'    => fn( StoreResponses $responses ): ?bool => self::ucp_endpoint_answers( $responses ),
			'blocks'  => 'ucp',
			'name'    => __( 'UCP endpoint answers', 'woocommerce' ),
			'label'   => __( 'UCP endpoint is blocked or doesn\'t answer', 'woocommerce' ),
			'why'     => __( 'Agents search your catalog and build carts through this endpoint; when it\'s blocked they can\'t find your products.', 'woocommerce' ),
			'fix'     => __( 'Ask your host or security plugin to allow requests to the WordPress REST API under /wp-json/wc/ucp/.', 'woocommerce' ),
			'fix_url' => '',
		);
		$checks[] = array(
			'id'      => 'ucp_checkout',
			'group'   => 'access',
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
	public static function host_root( string $path ): string {
		$home = (array) wp_parse_url( home_url() );
		return ( $home['scheme'] ?? 'http' ) . '://' . ( $home['host'] ?? '' ) . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . $path;
	}

	/**
	 * The address a conflict was seen at: 'product' for the product page the scan read, else the address itself.
	 *
	 * @param string         $seen_at   'product' or an address.
	 * @param StoreResponses $responses Store responses.
	 */
	private static function seen_at( string $seen_at, StoreResponses $responses ): string {
		if ( 'product' !== $seen_at ) {
			return $seen_at;
		}
		$product = $responses->product();
		return null === $product ? '' : (string) $product->get_permalink();
	}

	/**
	 * Whether WooCommerce adds Open Graph tags to product pages. It needs WordPress's template output buffer.
	 */
	private static function core_open_graph_on(): bool {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in StructuredData\OpenGraph.
		return function_exists( 'wp_start_template_enhancement_output_buffer' ) && (bool) apply_filters( 'woocommerce_enable_open_graph', true );
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
	 * Where a store's sitemaps may be: those robots.txt lists on this host (the first five), then the usual paths.
	 *
	 * @param string $robots The robots.txt text.
	 * @return string[]
	 */
	private static function sitemap_urls( string $robots ): array {
		$listed   = array_slice(
			array_values( array_filter( ( new RobotsTxt( $robots ) )->sitemaps(), array( StoreResponses::class, 'is_store_url' ) ) ),
			0,
			self::MAX_LISTED_SITEMAPS
		);
		$defaults = array( '/sitemap_index.xml', '/sitemap.xml' );
		if ( function_exists( 'wp_sitemaps_get_server' ) && wp_sitemaps_get_server()->sitemaps_enabled() ) {
			$defaults[] = '/wp-sitemap.xml';
		}
		return array_values( array_unique( array_merge( $listed, $defaults ) ) );
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
		return $this->sitemap_matches( $responses, array( self::class, 'names_products' ) );
	}

	/**
	 * Whether a test passes for a sitemap the store serves. Child sitemaps on this host are opened
	 * at any depth, up to five child requests. A child on another host is skipped. Null when
	 * robots.txt or one of those sitemaps could not be loaded and the test never passed.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param callable       $test      Test, given a sitemap body.
	 */
	private function sitemap_matches( StoreResponses $responses, callable $test ): ?bool {
		$robots  = $this->robots_txt( $responses );
		$queue   = array_values( array_unique( array_map( array( self::class, 'absolute_url' ), self::sitemap_urls( (string) $robots ) ) ) );
		$seen    = array_fill_keys( $queue, true );
		$budget  = count( $queue ) + self::MAX_CHILD_SITEMAPS;
		$unknown = null === $robots;
		for ( $i = 0; isset( $queue[ $i ] ) && $i < $budget; $i++ ) {
			$body = $this->sitemap_body( $responses, $queue[ $i ], $unknown );
			if ( null === $body ) {
				continue;
			}
			if ( $test( $body ) ) {
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
			$url = html_entity_decode( trim( str_replace( array( '<![CDATA[', ']]>' ), '', substr( $xml, $start, $end - $start ) ) ), ENT_QUOTES | ENT_XML1 );
			if ( '' !== $url ) {
				$urls[] = $url;
			}
			$offset = $end + strlen( '</loc>' );
			$start  = stripos( $xml, '<loc>', $offset );
		}
		return $urls;
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
		$products = JsonLd::items( $page['body'], 'Product', 'ProductGroup' );
		// A cut-off page can hide the footer JSON-LD. That is "couldn't check", not "missing".
		if ( array() === $products && $page['truncated'] ) {
			return null;
		}
		$result = $test( $products );
		return null === $result ? null : (bool) $result;
	}

	/**
	 * Whether the Product items give a name, an image and an offer, from one source or several.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 */
	private static function has_required_fields( array $products ): bool {
		$products = self::with_variants( $products );
		foreach ( array( 'name', 'image', 'offers' ) as $field ) {
			if ( array() === array_filter( $products, static fn( array $product ): bool => ! empty( $product[ $field ] ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Whether the page has one Product item and it is WooCommerce's, which it marks with the @id
	 * `<permalink>#product`. Values aren't compared: tax, currency and formatting differ without a conflict.
	 *
	 * @param array<int, array<string, mixed>> $products  Product items.
	 * @param StoreResponses                   $responses Store responses.
	 */
	private static function has_only_core_product( array $products, StoreResponses $responses ): ?bool {
		$product = $responses->product();
		if ( null === $product ) {
			return null;
		}
		return 1 === count( $products ) && self::comparable_url( $product->get_permalink() . '#product' ) === self::comparable_url( (string) ( $products[0]['@id'] ?? '' ) );
	}

	/**
	 * Whether one offer of the Product items has both a price and an availability. A price
	 * can be the offer's price, an AggregateOffer's lowPrice or a price specification's price.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 */
	private static function has_priced_offer( array $products ): bool {
		foreach ( self::offers( $products ) as $offer ) {
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
	 * Whether a test passes on the home page and the product page. A failure on a page that loaded
	 * counts even when the other page didn't. Null when a page that had to load didn't, and nothing failed.
	 * A store with no product to check is judged on its home page.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param callable       $test      Test, given the HTML and the response headers.
	 */
	private function on_pages( StoreResponses $responses, callable $test ): ?bool {
		$home    = $responses->home_page();
		$product = $responses->product_page();
		$result  = self::on_loaded( array( $home, $product ), $test );
		if ( false === $result ) {
			return false;
		}
		if ( null === $home || ( null === $product && null !== $responses->product() ) ) {
			return null;
		}
		return $result;
	}

	/**
	 * Whether a test passes on each page that loaded. Null when none loaded.
	 *
	 * @param array<int, array{status:int,body:string,headers:array<string,string>}|null> $pages Pages, null for one that didn't load.
	 * @param callable                                                                    $test  Test, given the HTML and the response headers.
	 */
	private static function on_loaded( array $pages, callable $test ): ?bool {
		$pages = array_filter( $pages );
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
		if ( null === $response || self::blocked( $response['status'] ) ) {
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
		$links   = array_map( static fn( string $link ): string => explode( '#', $link, 2 )[0], $links );
		$links   = array_filter( $links, static fn( string $link ): bool => $responses->is_store_url( $link ) && ! str_contains( $link, '?' ) );
		$links   = array_slice( array_values( array_unique( $links ) ), 0, self::MAX_LLMS_TXT_LINKS );
		$unknown = false;
		foreach ( $links as $link ) {
			$response = $responses->get( $link );
			if ( null === $response || self::blocked( $response['status'] ) ) {
				$unknown = true;
			} elseif ( $response['status'] >= 400 ) {
				return false;
			}
		}
		return $unknown ? null : true;
	}

	/**
	 * Whether a status says the store's request to itself was refused or failed, not that the
	 * page is missing: a server error, or a firewall or security plugin answering 401 or 403.
	 * A check then reports couldn't check, as it does when the page times out.
	 *
	 * @since 11.4.0
	 *
	 * @param int $status HTTP status.
	 */
	public static function blocked( int $status ): bool {
		return $status >= 500 || in_array( $status, array( 401, 403 ), true );
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
				$rules = self::robots_rules( $html, $headers );
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
				$amount = (string) preg_replace( '/[^\d.,\x{00A0}\x{2009}\x{202F}\' ]/u', '', $amount );
				$amount = (string) preg_replace( '/^\D+|\D+$/u', '', $amount );
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
		foreach ( self::with_variants( $products ) as $product ) {
			foreach ( self::as_list( $product['offers'] ?? null ) as $offer ) {
				if ( ! empty( $offer[ $property ] ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Whether the scanned product needs shipping. A product that doesn't ship has no shipping details to give.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private static function ships( StoreResponses $responses ): bool {
		$product = $responses->product();
		return null === $product || $product->needs_shipping();
	}

	/**
	 * The Product items and the variants a ProductGroup lists in hasVariant. A variable product
	 * printed as a ProductGroup carries its offers and buy actions on the variants, not the group.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @return array<int, array<string, mixed>>
	 */
	private static function with_variants( array $products ): array {
		$items = $products;
		foreach ( $products as $product ) {
			array_push( $items, ...self::as_list( $product['hasVariant'] ?? null ) );
		}
		return $items;
	}

	/**
	 * The product's own items, including a variant listed separately and linked with isVariantOf.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @param \WC_Product                      $product  The product of the page.
	 * @return array<int, array<string, mixed>>
	 */
	private static function identifier_items( array $products, \WC_Product $product ): array {
		$own = self::own_items( $products, $product );
		return self::with_variants( self::with_referenced_variants( array_merge( $own, self::variants_of( $own, $products ) ), $products ) );
	}

	/**
	 * Replace a hasVariant entry that is only an @id with the Product node that id names.
	 *
	 * @param array<int, array<string, mixed>> $items     Selected items.
	 * @param array<int, array<string, mixed>> $products Every Product item on the page.
	 * @return array<int, array<string, mixed>>
	 */
	private static function with_referenced_variants( array $items, array $products ): array {
		$by_id = array();
		foreach ( $products as $item ) {
			$id = $item['@id'] ?? null;
			if ( is_string( $id ) && '' !== $id ) {
				$by_id[ self::comparable_url( $id ) ] = $item;
			}
		}
		foreach ( $items as $index => $item ) {
			$variants = array();
			foreach ( self::as_list( $item['hasVariant'] ?? null ) as $variant ) {
				$id = $variant['@id'] ?? null;
				if ( ! is_string( $id ) || '' === $id || array() !== array_diff( array_keys( $variant ), array( '@id', '@type' ) ) ) {
					$variants[] = $variant;
					continue;
				}
				$key        = self::comparable_url( $id );
				$variants[] = $by_id[ $key ] ?? $variant;
			}
			if ( array() !== $variants ) {
				$items[ $index ]['hasVariant'] = $variants;
			}
		}
		return $items;
	}

	/**
	 * Items that say they are a variant of one of the selected items.
	 *
	 * @param array<int, array<string, mixed>> $own      Selected items.
	 * @param array<int, array<string, mixed>> $products Every Product item on the page.
	 * @return array<int, array<string, mixed>>
	 */
	private static function variants_of( array $own, array $products ): array {
		$ids = array();
		foreach ( $own as $item ) {
			if ( is_string( $item['@id'] ?? null ) && '' !== $item['@id'] ) {
				$ids[ self::comparable_url( $item['@id'] ) ] = true;
			}
		}
		if ( array() === $ids ) {
			return array();
		}
		$linked = array();
		foreach ( $products as $item ) {
			foreach ( self::variant_of_ids( $item ) as $id ) {
				if ( isset( $ids[ self::comparable_url( $id ) ] ) ) {
					$linked[] = $item;
					break;
				}
			}
		}
		return $linked;
	}

	/**
	 * Identifiers an item's isVariantOf property gives.
	 *
	 * @param array<string, mixed> $item Product item.
	 * @return array<int, string>
	 */
	private static function variant_of_ids( array $item ): array {
		$of = $item['isVariantOf'] ?? null;
		if ( is_string( $of ) ) {
			return '' === $of ? array() : array( $of );
		}
		$ids = array();
		foreach ( self::as_list( $of ) as $one ) {
			if ( is_string( $one['@id'] ?? null ) && '' !== $one['@id'] ) {
				$ids[] = $one['@id'];
			}
		}
		return $ids;
	}

	/**
	 * Whether a Product item, a variant or one of their offers has a BuyAction potentialAction.
	 * Null for a variable product without one: WooCommerce doesn't link a variable product's
	 * page to checkout, only a chosen variation.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @param \WC_Product|null                 $scanned  The scanned product.
	 */
	private static function has_buy_action( array $products, ?\WC_Product $scanned = null ): ?bool {
		foreach ( self::with_variants( $products ) as $product ) {
			$items = array_merge( array( $product ), self::as_list( $product['offers'] ?? null ) );
			foreach ( $items as $item ) {
				foreach ( self::as_list( $item['potentialAction'] ?? null ) as $action ) {
					if ( in_array( 'BuyAction', (array) ( $action['@type'] ?? array() ), true ) ) {
						return true;
					}
				}
			}
		}
		return null !== $scanned && $scanned->is_type( 'variable' ) ? null : false;
	}

	/**
	 * Whether catalog search, the route agents call, answers with JSON. The endpoint is the one the
	 * served profile names, or WooCommerce's when the profile isn't served. The body is empty, so
	 * WooCommerce refuses it with a JSON 400: a query or a filter is required. That 400 means the
	 * route answered. A firewall or plugin that blocks the route blocks this request too.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private static function ucp_endpoint_answers( StoreResponses $responses ): ?bool {
		$endpoint = untrailingslashit( self::ucp_endpoint( $responses ) ?? rest_url( 'wc/ucp/v1' ) );
		$response = $responses->post_json( $endpoint . '/catalog/search', array() );
		if ( null === $response || $response['status'] >= 500 ) {
			return null;
		}
		$json = is_array( json_decode( $response['body'], true ) );
		return $json && ( 200 === $response['status'] || 400 === $response['status'] );
	}

	/**
	 * The number in a JSON-LD price, or null when it isn't one. `10,00`, `99.00 USD`, `R$ 10,00` and `EUR 10,-` count.
	 * A dot or slash in the currency, as in `S/.` or `kr.`, is not part of the number.
	 *
	 * @param mixed $value Price value.
	 */
	private static function price_amount( $value ): ?float {
		if ( is_int( $value ) || is_float( $value ) ) {
			return is_finite( $value ) ? (float) $value : null;
		}
		if ( ! is_string( $value ) ) {
			return null;
		}
		$chars = self::amount_characters( $value );
		if ( null === $chars ) {
			return null;
		}
		// "10,-" and "10.-" are zero cents. The dash is not a minus sign.
		$count = count( $chars );
		if ( $count >= 2 && '-' === $chars[ $count - 1 ] && ( ',' === $chars[ $count - 2 ] || '.' === $chars[ $count - 2 ] ) ) {
			array_pop( $chars );
		}
		$kept  = str_replace( "'", '', implode( '', $chars ) );
		$comma = strrpos( $kept, ',' );
		$dot   = strrpos( $kept, '.' );
		if ( false !== $comma && false !== $dot ) {
			$decimal   = $dot > $comma ? '.' : ',';
			$thousands = '.' === $decimal ? ',' : '.';
			$kept      = str_replace( $decimal, '.', str_replace( $thousands, '', $kept ) );
		} elseif ( false !== $comma && strlen( $kept ) - $comma - 1 <= 2 ) {
			$kept = str_replace( ',', '.', $kept );
		} else {
			$kept = str_replace( ',', '', $kept );
		}
		return is_numeric( $kept ) ? (float) $kept : null;
	}

	/**
	 * Whether a JSON-LD price is a number, or text that is one with an optional currency symbol or code
	 * (`10.00`, `10,00`, `$10`, `R$ 10,00`, `US$10`, `S/. 10`, `EUR 10,-`, `10,00 kr.`). A trailing
	 * `,-` or `.-` means zero cents. Text such as `call 555` is not a price.
	 *
	 * @param mixed $value Price value.
	 */
	private static function has_price_amount( $value ): bool {
		if ( is_int( $value ) || is_float( $value ) ) {
			return is_finite( $value );
		}
		return is_string( $value ) && null !== self::amount_characters( $value );
	}

	/**
	 * The amount characters of a price, currency removed from either end. Null when it isn't a price.
	 *
	 * @param string $value Price text.
	 * @return array<int, string>|null
	 */
	private static function amount_characters( string $value ): ?array {
		$chars = self::price_characters( $value );
		if ( array() === $chars ) {
			return null;
		}
		$chars = self::without_leading_currency( $chars );
		$chars = array_reverse( self::without_leading_currency( array_reverse( $chars ) ) );
		return self::characters_are_amount( $chars ) ? $chars : null;
	}

	/**
	 * Characters of a price, without the spaces a store uses between the amount and the currency.
	 *
	 * @param string $value Price text.
	 * @return array<int, string>
	 */
	private static function price_characters( string $value ): array {
		$chars = mb_str_split( $value );
		if ( ! is_array( $chars ) ) {
			return array();
		}
		$kept = array();
		foreach ( $chars as $char ) {
			if ( ' ' === $char || "\t" === $char || "\n" === $char || "\r" === $char || "\u{00A0}" === $char || "\u{2009}" === $char || "\u{202F}" === $char ) {
				continue;
			}
			$kept[] = $char;
		}
		return $kept;
	}

	/**
	 * Drop one currency token from the front. A token is currency symbols and up to three letters,
	 * plus the "." and "/" in abbreviations such as "kr." and "S/.". A longer word, such as "call", stays.
	 *
	 * @param array<int, string> $chars Characters.
	 * @return array<int, string>
	 */
	private static function without_leading_currency( array $chars ): array {
		$letters = 0;
		$marked  = false;
		$taken   = 0;
		foreach ( $chars as $char ) {
			$kind = self::price_char_kind( $char );
			if ( 'letter' === $kind ) {
				++$letters;
				if ( $letters > 3 ) {
					return $chars;
				}
				$marked = true;
				++$taken;
				continue;
			}
			if ( 'symbol' === $kind ) {
				$marked = true;
				++$taken;
				continue;
			}
			// "." and "/" only belong to a token that also has a letter or a symbol, so "10.00" is left whole.
			if ( 'mark' === $kind ) {
				++$taken;
				continue;
			}
			break;
		}
		return $marked ? array_slice( $chars, $taken ) : $chars;
	}

	/**
	 * Whether a character is part of a currency token: a letter, a currency symbol, or "." or "/".
	 *
	 * Currency symbols have no other test than Unicode's Sc category.
	 *
	 * @param string $char One character.
	 * @return string 'letter', 'symbol', 'mark' or ''.
	 */
	private static function price_char_kind( string $char ): string {
		if ( '.' === $char || '/' === $char ) {
			return 'mark';
		}
		if ( 1 === strlen( $char ) ) {
			$byte = ord( $char );
			if ( ( $byte >= 65 && $byte <= 90 ) || ( $byte >= 97 && $byte <= 122 ) ) {
				return 'letter';
			}
			return '$' === $char ? 'symbol' : '';
		}
		return 1 === preg_match( '/^\p{Sc}$/u', $char ) ? 'symbol' : '';
	}

	/**
	 * Whether the characters are a number, with "," or "." as a separator and an optional leading minus.
	 * A trailing ",-" or ".-" means zero cents. A dash anywhere else is not an amount.
	 *
	 * @param array<int, string> $chars Characters.
	 * @return bool
	 */
	private static function characters_are_amount( array $chars ): bool {
		if ( array() !== $chars && '-' === $chars[0] ) {
			array_shift( $chars );
		}
		$count = count( $chars );
		if ( $count >= 2 && '-' === $chars[ $count - 1 ] && ( ',' === $chars[ $count - 2 ] || '.' === $chars[ $count - 2 ] ) ) {
			array_pop( $chars );
			array_pop( $chars );
		}
		if ( array() === $chars ) {
			return false;
		}
		foreach ( $chars as $char ) {
			if ( ! ctype_digit( $char ) && '.' !== $char && ',' !== $char && "'" !== $char ) {
				return false;
			}
		}
		$last = count( $chars ) - 1;
		return ctype_digit( $chars[0] ) && ctype_digit( $chars[ $last ] );
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
	 * A JSON-LD property that may repeat, as a list of its values.
	 *
	 * @param mixed $value The property.
	 * @return array<int, mixed>
	 */
	private static function values( $value ): array {
		return is_array( $value ) && wp_is_numeric_array( $value ) ? $value : array( $value );
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

	/**
	 * The page's robots meta tags and X-Robots-Tag header in lowercase, rules for one crawler included.
	 *
	 * @param string                $html    Page HTML.
	 * @param array<string, string> $headers Response headers.
	 */
	private static function robots_rules( string $html, array $headers ): string {
		// googlebot-news contains "bot" without ending in it. "robots" does too.
		$rules   = PageMarkup::meta( $html, 'name', static fn( string $name ): bool => str_contains( $name, 'bot' ) );
		$rules[] = $headers['x-robots-tag'] ?? '';
		return strtolower( implode( ', ', $rules ) );
	}

	/**
	 * Whether robots rules ask crawlers not to index the page. `none` as the value of
	 * max-image-preview, max-snippet, max-video-preview or unavailable_after does not.
	 *
	 * @param string $rules Lowercase robots rules.
	 */
	private static function restricts_indexing( string $rules ): bool {
		$blocking = array( 'noindex', 'none', 'noai', 'noimageai' );
		$valued   = array( 'max-image-preview', 'max-snippet', 'max-video-preview', 'unavailable_after' );
		foreach ( explode( ',', $rules ) as $directive ) {
			$directive = trim( $directive );
			if ( '' === $directive ) {
				continue;
			}
			$colon = strpos( $directive, ':' );
			if ( false === $colon ) {
				// A comma is the usual separator; "noindex nofollow" is the same pair written with a space.
				foreach ( explode( ' ', str_replace( "\t", ' ', $directive ) ) as $token ) {
					if ( in_array( $token, $blocking, true ) ) {
						return true;
					}
				}
				continue;
			}
			$name  = trim( substr( $directive, 0, $colon ) );
			$value = trim( substr( $directive, $colon + 1 ) );
			if ( in_array( $name, $valued, true ) ) {
				continue;
			}
			if ( in_array( $name, $blocking, true ) || in_array( $value, $blocking, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The home, shop and product page URLs a visitor would open first; shop and product only when the store has them.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @return string[]
	 */
	private static function store_page_urls( StoreResponses $responses ): array {
		$product = $responses->product();
		return array_values( array_filter( array( home_url( '/' ), wc_get_page_permalink( 'shop', '' ), null === $product ? '' : (string) $product->get_permalink() ) ) );
	}

	/**
	 * Whether the home, shop and product page open without sending the visitor to sign in.
	 * False when one redirects to wp-login.php or My account. A 401 or 403 is couldn't check:
	 * a firewall or security plugin can refuse the store's request to itself. Null when one
	 * couldn't be loaded and none redirected to sign in.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function pages_public( StoreResponses $responses ): ?bool {
		$unknown = false;
		$account = (string) wp_parse_url( wc_get_page_permalink( 'myaccount', '' ), PHP_URL_PATH );
		foreach ( self::store_page_urls( $responses ) as $url ) {
			$response = $responses->get( $url );
			$followed = $responses->redirects( $url );
			if ( self::sign_in_path( $followed['stopped_at'], $account ) || self::sign_in_path( $followed['final_url'], $account ) ) {
				return false;
			}
			if ( null === $response || self::blocked( $response['status'] ) ) {
				$unknown = true;
			}
		}
		return $unknown ? null : true;
	}

	/**
	 * Whether a URL is the sign-in page or wp-login.php.
	 *
	 * @param string $url     URL, or '' when there is none.
	 * @param string $account Path of the My Account page.
	 */
	private static function sign_in_path( string $url, string $account ): bool {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return '' !== $path && ( str_ends_with( $path, '/wp-login.php' ) || ( '' !== $account && '/' !== $account && untrailingslashit( $path ) === untrailingslashit( $account ) ) );
	}

	/**
	 * Whether a sitemap the store serves has lastmod dates, in the index or in a child sitemap on this host.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function sitemap_has_lastmod( StoreResponses $responses ): ?bool {
		return $this->sitemap_matches(
			$responses,
			static fn( string $xml ): bool => false !== stripos( $xml, '<lastmod' )
		);
	}

	/**
	 * Whether the page links to an RSS or Atom feed with a link tag.
	 *
	 * @param string $html Page HTML.
	 */
	private static function has_feed_link( string $html ): bool {
		$tags = new \WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag( array( 'tag_name' => 'link' ) ) ) {
			$rel  = strtolower( (string) $tags->get_attribute( 'rel' ) );
			$type = strtolower( trim( (string) $tags->get_attribute( 'type' ) ) );
			if ( in_array( 'alternate', explode( ' ', (string) preg_replace( '/\s+/', ' ', $rel ) ), true ) && in_array( $type, array( 'application/rss+xml', 'application/atom+xml' ), true ) && '' !== trim( (string) $tags->get_attribute( 'href' ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether the page's html element has a lang attribute and the page declares its encoding,
	 * in a meta tag or the Content-Type header. Null when the page didn't load.
	 *
	 * @param array{status:int,body:string,headers:array<string,string>}|null $page Page.
	 */
	private static function has_lang_and_charset( ?array $page ): ?bool {
		if ( null === $page ) {
			return null;
		}
		$tags    = new \WP_HTML_Tag_Processor( $page['body'] );
		$lang    = $tags->next_tag( array( 'tag_name' => 'html' ) ) && '' !== trim( (string) $tags->get_attribute( 'lang' ) );
		$charset = false !== stripos( $page['headers']['content-type'] ?? '', 'charset=' );
		$tags    = new \WP_HTML_Tag_Processor( $page['body'] );
		while ( ! $charset && $tags->next_tag( array( 'tag_name' => 'meta' ) ) ) {
			$charset = null !== $tags->get_attribute( 'charset' ) || false !== stripos( (string) $tags->get_attribute( 'content' ), 'charset=' );
		}
		return $lang && $charset;
	}

	/**
	 * Whether every image has an alt attribute. An empty alt marks a decorative image and counts;
	 * images hidden from assistive technology don't need one.
	 *
	 * @param string $html Page HTML.
	 */
	private static function images_have_alt( string $html ): bool {
		$tags     = new \WP_HTML_Tag_Processor( $html );
		$noscript = 0;
		while ( $tags->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			if ( 'NOSCRIPT' === $tags->get_tag() ) {
				$noscript = max( 0, $noscript + ( $tags->is_tag_closer() ? -1 : 1 ) );
				continue;
			}
			if ( 'IMG' !== $tags->get_tag() || $noscript > 0 || null !== $tags->get_attribute( 'alt' ) ) {
				continue;
			}
			// A tracking pixel or an image hidden from assistive technology needs no alt.
			$pixel  = '1' === (string) $tags->get_attribute( 'width' ) && '1' === (string) $tags->get_attribute( 'height' );
			$hidden = 'true' === strtolower( (string) $tags->get_attribute( 'aria-hidden' ) ) || in_array( strtolower( (string) $tags->get_attribute( 'role' ) ), array( 'presentation', 'none' ), true );
			if ( ! $pixel && ! $hidden ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * How many tags of a name the page has.
	 *
	 * @param string $html Page HTML.
	 * @param string $name Tag name.
	 */
	private static function count_tags( string $html, string $name ): int {
		$tags  = new \WP_HTML_Tag_Processor( $html );
		$count = 0;
		while ( $tags->next_tag( array( 'tag_name' => $name ) ) ) {
			++$count;
		}
		return $count;
	}

	/**
	 * Whether the page gives the product a valid GTIN, from WooCommerce or any other source. When it doesn't, a
	 * failure only when WooCommerce would have printed one, the product's own valid GTIN; otherwise null, since
	 * missing_gtin and invalid_gtin report the stored value.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @param \WC_Product|null                 $product  The product of the page.
	 */
	private static function served_gtin_ok( array $products, ?\WC_Product $product ): ?bool {
		if ( null === $product || ! ProductFields::needs_identifiers( $product ) ) {
			return null;
		}
		foreach ( self::served_gtins( self::identifier_items( $products, $product ) ) as $gtin ) {
			if ( ProductFields::is_valid_gtin( $gtin ) ) {
				return true;
			}
		}
		// WooCommerce prints the product's own GTIN on its page, never a variation's.
		$own = (string) preg_replace( '/\D/', '', (string) $product->get_global_unique_id() );
		return ProductFields::is_valid_gtin( $own ) ? false : null;
	}

	/**
	 * GTINs the Product items and their offers give, digits only.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @return array<int, string>
	 */
	private static function served_gtins( array $products ): array {
		$gtins = array();
		// The caller already included variants. Expanding hasVariant again only repeats them.
		foreach ( array_merge( $products, self::offers_of( $products ) ) as $item ) {
			foreach ( array( 'gtin', 'gtin8', 'gtin12', 'gtin13', 'gtin14' ) as $key ) {
				foreach ( self::values( $item[ $key ] ?? null ) as $value ) {
					$gtin = is_string( $value ) || is_int( $value ) ? (string) preg_replace( '/\D/', '', (string) $value ) : '';
					if ( '' !== $gtin ) {
						$gtins[] = $gtin;
					}
				}
			}
		}
		return array_values( array_unique( $gtins ) );
	}

	/**
	 * Whether the page gives a brand, from WooCommerce or any other source. When it doesn't, a failure only when
	 * WooCommerce would have printed one: it prints the product's first WooCommerce brand. A brand kept in an
	 * attribute or another plugin's taxonomy isn't printed by WooCommerce, so its absence is null, not a conflict.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @param \WC_Product|null                 $product  The product of the page.
	 */
	private static function served_brand_ok( array $products, ?\WC_Product $product ): ?bool {
		if ( null === $product ) {
			return null;
		}
		foreach ( self::identifier_items( $products, $product ) as $item ) {
			foreach ( self::values( $item['brand'] ?? null ) as $brand ) {
				$name = is_array( $brand ) ? ( $brand['name'] ?? null ) : $brand;
				if ( is_string( $name ) && '' !== trim( $name ) ) {
					return true;
				}
			}
		}
		if ( ! taxonomy_exists( 'product_brand' ) ) {
			return null;
		}
		$terms = get_the_terms( $product->get_id(), 'product_brand' );
		return is_array( $terms ) && array() !== $terms ? false : null;
	}

	/**
	 * The Product items that are this product: the ones that name it, or, when none does, the ones whose
	 * url or @id is its permalink. An upsell that matches neither is left out.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @param \WC_Product                      $product  The product of the page.
	 * @return array<int, array<string, mixed>>
	 */
	private static function own_items( array $products, \WC_Product $product ): array {
		$named = self::naming( $products, $product );
		if ( array() !== $named ) {
			return $named;
		}
		$permalink = (string) $product->get_permalink();
		return array_values(
			array_filter(
				$products,
				static function ( array $item ) use ( $permalink ): bool {
					foreach ( array( 'url', '@id' ) as $key ) {
						$value = $item[ $key ] ?? null;
						if ( is_string( $value ) && self::same_page( $value, $permalink ) ) {
							return true;
						}
					}
					return false;
				}
			)
		);
	}

	/**
	 * Whether a JSON-LD url or @id points at this product's permalink. A fragment after # is ignored.
	 *
	 * @param string $value     url or @id.
	 * @param string $permalink Product permalink.
	 */
	private static function same_page( string $value, string $permalink ): bool {
		$hash = strpos( $value, '#' );
		if ( false !== $hash ) {
			$value = substr( $value, 0, $hash );
		}
		return '' !== $value && self::comparable_url( $value ) === self::comparable_url( $permalink );
	}

	/**
	 * A URL compared the way a served page can differ from the stored permalink: entities decoded, scheme and trailing slash dropped.
	 *
	 * @param string $url URL.
	 */
	private static function comparable_url( string $url ): string {
		$url   = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$lower = strtolower( $url );
		foreach ( array( 'https:', 'http:' ) as $scheme ) {
			if ( str_starts_with( $lower, $scheme ) ) {
				$url = substr( $url, strlen( $scheme ) );
				break;
			}
		}
		return untrailingslashit( $url );
	}

	/**
	 * The Product items whose name is the product's.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @param \WC_Product                      $product  The product of the page.
	 * @return array<int, array<string, mixed>>
	 */
	private static function naming( array $products, \WC_Product $product ): array {
		$name = strtolower( trim( html_entity_decode( $product->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
		return array_values( array_filter( $products, static fn( array $item ): bool => is_string( $item['name'] ?? null ) && strtolower( trim( html_entity_decode( $item['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) === $name ) );
	}

	/**
	 * Whether a Product item names the product, and, for a product with one price, one of its own items
	 * offers that price with or without tax; a related product's offer at that price doesn't count.
	 * Offers without a price aren't judged here: product_offer_jsonld reports them.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @param \WC_Product|null                 $product  The product of the page.
	 */
	private static function schema_matches( array $products, ?\WC_Product $product ): ?bool {
		if ( null === $product ) {
			return null;
		}
		if ( array() === self::naming( $products, $product ) ) {
			return false;
		}
		if ( '' === $product->get_price() || $product->is_type( 'variable' ) ) {
			return true;
		}
		$prices   = array();
		$unparsed = false;
		foreach ( self::offers( self::own_items( $products, $product ) ) as $offer ) {
			$values = array( $offer['price'] ?? null, $offer['lowPrice'] ?? null );
			foreach ( self::as_list( $offer['priceSpecification'] ?? null ) as $specification ) {
				$type = (string) ( $specification['priceType'] ?? '' );
				// ListPrice is a price that is not the current one, such as the price before a sale.
				if ( 'ListPrice' === $type || str_ends_with( $type, '/ListPrice' ) ) {
					continue;
				}
				$values[] = $specification['price'] ?? null;
			}
			foreach ( $values as $price ) {
				if ( null === $price || '' === $price ) {
					continue;
				}
				$amount = self::price_amount( $price );
				if ( null === $amount ) {
					$unparsed = true;
					continue;
				}
				$prices[] = $amount;
			}
		}
		if ( array() === $prices ) {
			return $unparsed ? null : true;
		}
		$excl     = (float) wc_get_price_excluding_tax( $product );
		$expected = array( (float) $product->get_price(), (float) wc_get_price_including_tax( $product ), $excl );
		// The page was served to one location; its tax may differ from the store's base rate.
		$rates = $product->is_taxable() ? \WC_Tax::get_rates_for_tax_class( $product->get_tax_class() ) : array();
		$rates = is_array( $rates ) ? $rates : array();
		foreach ( $rates as $rate ) {
			$expected[] = $excl * ( 1 + (float) $rate->tax_rate / 100 );
		}
		// WooCommerce prints the JSON-LD price rounded with wc_format_decimal(). With 0 decimals, 9.99 is served as 10.
		$decimals = wc_get_price_decimals();
		$printed  = array();
		foreach ( $expected as $amount ) {
			$printed[] = (float) wc_format_decimal( $amount, $decimals );
		}
		$expected = array_merge( $expected, $printed );
		foreach ( $prices as $price ) {
			foreach ( $expected as $amount ) {
				if ( abs( $price - $amount ) < 0.01 ) {
					return true;
				}
			}
		}
		// Several rates of one class can apply together, so a price that matches none of them stays unknown.
		return count( $rates ) > 1 ? null : false;
	}

	/**
	 * Whether the page of a published product with reviews has a rating in its JSON-LD. True when
	 * reviews are off or no product has any, since there is nothing to mark up.
	 *
	 * @param StoreResponses $responses Store responses.
	 */
	private function reviews_marked_up( StoreResponses $responses ): ?bool {
		// WooCommerce prints a rating only when ratings are on and the product has rated reviews; the rating count is stored per star, the average as a number.
		if ( ! wc_review_ratings_enabled() ) {
			return true;
		}
		// wc_get_products() ignores meta_query, so the reviewed product is looked up as a post.
		$ids = get_posts(
			array(
				'post_type'        => 'product',
				'post_status'      => ProductStatus::PUBLISH,
				'has_password'     => false,
				'posts_per_page'   => 5,
				'fields'           => 'ids',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Five rows, once per background scan.
				'tax_query'        => array(
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'slug',
						'terms'    => array( 'exclude-from-catalog' ),
						'operator' => 'NOT IN',
					),
				),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Five rows, once per background scan.
				'meta_query'       => array(
					array(
						'key'     => '_wc_average_rating',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'DECIMAL',
					),
				),
			)
		);
		$product = null;
		foreach ( $ids as $id ) {
			$candidate = wc_get_product( (int) $id );
			if ( $candidate instanceof \WC_Product && $candidate->is_visible() ) {
				$product = $candidate;
				break;
			}
		}
		if ( null === $product ) {
			return true;
		}
		$sampled = $responses->product();
		if ( null !== $sampled && $sampled->get_id() === $product->get_id() ) {
			$page = $responses->product_page();
			if ( null === $page ) {
				return null;
			}
		} else {
			$page = $responses->get( (string) $product->get_permalink() );
			if ( null === $page || 200 !== $page['status'] ) {
				return null;
			}
		}
		foreach ( self::naming( JsonLd::items( $page['body'], 'Product', 'ProductGroup' ), $product ) as $item ) {
			if ( ! empty( $item['aggregateRating'] ) || ! empty( $item['review'] ) ) {
				return true;
			}
		}
		// A cut-off page can hide the rating. That is "couldn't check", not "missing".
		return $page['truncated'] ? null : false;
	}

	/**
	 * Whether the home page links to the privacy policy and refund and returns pages the store set.
	 * Null when the page didn't load or neither page is set: the page checks report a missing page.
	 *
	 * @param array{status:int,body:string,headers:array<string,string>}|null $home Home page.
	 */
	private static function links_policy_pages( ?array $home ): ?bool {
		$pages = array_filter(
			array_map(
				static fn( string $option ): int => (int) get_option( $option ),
				array( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION )
			),
			static fn( int $id ): bool => $id > 0 && 'publish' === get_post_status( $id )
		);
		if ( null === $home || array() === $pages ) {
			return null;
		}
		$links = array();
		$tags  = new \WP_HTML_Tag_Processor( $home['body'] );
		while ( $tags->next_tag( array( 'tag_name' => 'a' ) ) ) {
			$links[] = (string) \WP_Http::make_absolute_url( (string) $tags->get_attribute( 'href' ), home_url( '/' ) );
		}
		foreach ( $pages as $id ) {
			$permalink = (string) get_permalink( $id );
			foreach ( $links as $link ) {
				if ( self::same_page( $link, $permalink ) ) {
					continue 2;
				}
			}
			return false;
		}
		return true;
	}

	/**
	 * The offers of the Product items, with those an AggregateOffer lists, one level down.
	 *
	 * @param array<int, array<string, mixed>> $products Product items.
	 * @return array<int, array<string, mixed>>
	 */
	private static function offers( array $products ): array {
		return self::offers_of( self::with_variants( $products ) );
	}

	/**
	 * The offers of these items, with those an AggregateOffer lists, one level down.
	 * Variants are not expanded: the caller includes them when it has them.
	 *
	 * @param array<int, array<string, mixed>> $items Product items.
	 * @return array<int, array<string, mixed>>
	 */
	private static function offers_of( array $items ): array {
		$offers = array();
		foreach ( $items as $item ) {
			array_push( $offers, ...self::as_list( $item['offers'] ?? null ) );
		}
		$nested = array();
		foreach ( $offers as $offer ) {
			array_push( $nested, ...self::as_list( $offer['offers'] ?? null ) );
		}
		return array_merge( $offers, $nested );
	}
}
