<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * What Sales readiness says about each check, by check id. A failing check is headed by what to do
 * ("Add an llms.txt file"), then why it matters and what the scan found, then the fix line, which every
 * store check has so a failed row always says where to look, and a link only when WooCommerce has a screen for it.
 * Reports keep the copy the scan stored, so the page reads it from here and falls back to the report
 * for a check this class doesn't name.
 *
 * @since 11.4.0
 */
final class ReadinessCopy {

	/**
	 * Up to how many failing products a fix title names; more are given as a count.
	 */
	public const NAMED_MAX = 3;


	/**
	 * A store check's copy.
	 *
	 * @param string $id     Check id.
	 * @param string $source What the store serves for the check (the report's source), or ''.
	 * @return array{title:string,action:string,why:string,found:string,fix:string,url:string}|null Passed title, failing title, why it matters (shown when passed), why and what was found (shown when failing), the fix line or '', and where to fix it or ''. Null for a check without copy here.
	 */
	public static function store_check( string $id, string $source = '' ): ?array {
		$checks = self::store_checks();
		if ( ! isset( $checks[ $id ] ) ) {
			return null;
		}
		$check = $checks[ $id ];
		if ( 'ucp_profile_version' === $id && '' !== $source ) {
			/* translators: %s: UCP version the store serves, a date such as 2026-04-08. */
			$check[3] = sprintf( __( 'Your store uses an older version of the Universal Commerce Protocol (UCP %s).', 'woocommerce' ), $source );
		}
		return array(
			'title'  => $check[0],
			'action' => $check[1],
			'why'    => $check[2],
			'found'  => $check[3],
			'fix'    => $check[4] ?? '',
			'url'    => $check[5] ?? '',
		);
	}

	/**
	 * What Sales readiness says about where llms.txt comes from (StoreOutputChecks::llms_txt_source):
	 * a line for a served file, or the fix for one that isn't served. Null for '' or an unknown source.
	 *
	 * @param string $source Source of the served llms.txt.
	 * @return array{pass?:string,fix?:string}|null
	 */
	public static function llms_txt_source( string $source ): ?array {
		switch ( $source ) {
			case 'woocommerce':
				return array( 'pass' => __( 'WooCommerce creates it from your store’s details.', 'woocommerce' ) );
			case 'file':
				return array( 'pass' => __( 'It’s a file in your site’s root folder, so WooCommerce doesn’t add its own.', 'woocommerce' ) );
			case 'other':
				return array( 'pass' => __( 'Another plugin or your host provides it, so WooCommerce doesn’t add its own.', 'woocommerce' ) );
			case 'disabled':
				return array( 'fix' => __( 'WooCommerce’s llms.txt is turned off by code on your store (the woocommerce_enable_llms_txt filter). Remove that code, or turn on llms.txt in your SEO plugin.', 'woocommerce' ) );
			case 'not_reached':
				return array( 'fix' => __( 'WooCommerce serves one when nothing else does, so something stops /llms.txt from reaching your store. Ask your host whether a server rule or cache blocks it.', 'woocommerce' ) );
		}
		return null;
	}

	/**
	 * A failing product check's copy: what to do, why it matters, and the fix line. With up to three
	 * products the title names them ("Add a brand to Blue hoodie and Red cap"); one product without a
	 * known name is "a product".
	 *
	 * @param string   $id    Check id.
	 * @param int      $count Number of products.
	 * @param string[] $names Names of the failing products when known, or none.
	 * @return array{action:string,why:string,fix:string}|null Null for a check without copy here.
	 */
	public static function product_check( string $id, int $count, array $names = array() ): ?array {
		$n = number_format_i18n( $count );
		switch ( $id ) {
			case 'missing_brand':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Add a brand to %s', 'Add a brand to %s', $count, 'woocommerce' ), __( 'Add a brand to a product', 'woocommerce' ), sprintf( _n( 'Add a brand to %s product', 'Add a brand to %s products', $count, 'woocommerce' ), $n ) ),
					__( 'ChatGPT only lists products that have a brand. In Google and Copilot, a brand makes products easier to find.', 'woocommerce' )
				);
			case 'missing_gtin':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Add a barcode number to %s', 'Add barcode numbers to %s', $count, 'woocommerce' ), __( 'Add a barcode number to a product', 'woocommerce' ), sprintf( _n( 'Add a barcode number to %s product', 'Add barcode numbers to %s products', $count, 'woocommerce' ), $n ) ),
					__( 'Barcode numbers, like a UPC, EAN, or ISBN, help AI assistants match your products to what shoppers search for.', 'woocommerce' ),
					__( 'Use the number from the packaging or supplier. Never make one up.', 'woocommerce' )
				);
			case 'invalid_gtin':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Fix the barcode number of %s', 'Fix the barcode numbers of %s', $count, 'woocommerce' ), __( 'Fix the barcode number of a product', 'woocommerce' ), sprintf( _n( 'Fix the barcode number of %s product', 'Fix the barcode numbers of %s products', $count, 'woocommerce' ), $n ) ),
					__( 'Google doesn’t list products with a barcode number that isn’t valid. In ChatGPT and Copilot, a valid one makes products easier to find.', 'woocommerce' ),
					__( 'A barcode number has 8, 12, 13 or 14 digits. Use the number from the packaging or supplier.', 'woocommerce' )
				);
			case 'duplicate_gtin':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Give %s its own barcode number', 'Give %s their own barcode numbers', $count, 'woocommerce' ), __( 'Give a product its own barcode number', 'woocommerce' ), sprintf( _n( 'Give %s product its own barcode number', 'Give %s products their own barcode numbers', $count, 'woocommerce' ), $n ) ),
					__( 'Products that share a barcode number look like one product, so AI assistants may show the wrong one.', 'woocommerce' )
				);
			case 'missing_regular_price':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Add a price to %s', 'Add a price to %s', $count, 'woocommerce' ), __( 'Add a price to a product', 'woocommerce' ), sprintf( _n( 'Add a price to %s product', 'Add a price to %s products', $count, 'woocommerce' ), $n ) ),
					__( 'AI assistants only list products that have a price.', 'woocommerce' )
				);
			case 'missing_images':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Add a main image to %s', 'Add a main image to %s', $count, 'woocommerce' ), __( 'Add a main image to a product', 'woocommerce' ), sprintf( _n( 'Add a main image to %s product', 'Add a main image to %s products', $count, 'woocommerce' ), $n ) ),
					__( 'AI assistants only list products that have a main image.', 'woocommerce' )
				);
			case 'image_too_small':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Use a larger main image on %s', 'Use a larger main image on %s', $count, 'woocommerce' ), __( 'Use a larger main image on a product', 'woocommerce' ), sprintf( _n( 'Use a larger main image on %s product', 'Use a larger main image on %s products', $count, 'woocommerce' ), $n ) ),
					__( 'Google asks for images of at least 500 x 500 pixels.', 'woocommerce' )
				);
			case 'missing_product_text':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Add a description to %s', 'Add a description to %s', $count, 'woocommerce' ), __( 'Add a description to a product', 'woocommerce' ), sprintf( _n( 'Add a description to %s product', 'Add a description to %s products', $count, 'woocommerce' ), $n ) ),
					__( 'ChatGPT only lists products that have a description. In Google and Copilot, a description makes products easier to find.', 'woocommerce' )
				);
			case 'uncategorised':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Choose a category for %s', 'Choose a category for %s', $count, 'woocommerce' ), __( 'Choose a category for a product', 'woocommerce' ), sprintf( _n( 'Choose a category for %s product', 'Choose a category for %s products', $count, 'woocommerce' ), $n ) ),
					__( 'AI assistants narrow a search by category first, so products with only the default category are harder to find.', 'woocommerce' )
				);
			case 'variation_attributes_not_set':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Choose an option for each variation of %s', 'Choose an option for each variation of %s', $count, 'woocommerce' ), __( 'Choose an option for each variation of a product', 'woocommerce' ), sprintf( _n( 'Choose an option for each variation of %s product', 'Choose an option for each variation of %s products', $count, 'woocommerce' ), $n ) ),
					__( 'Variations need a specific option, like “Small” instead of “Any size,” to be listed on their own.', 'woocommerce' )
				);
			case 'missing_variation_images':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Add an image to each variation of %s', 'Add an image to each variation of %s', $count, 'woocommerce' ), __( 'Add an image to each variation of a product', 'woocommerce' ), sprintf( _n( 'Add an image to each variation of %s product', 'Add an image to each variation of %s products', $count, 'woocommerce' ), $n ) ),
					__( 'Each variation is listed on its own. Without its own image, it shows the main image, which may not match its color.', 'woocommerce' )
				);
			case 'title_too_long':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Shorten the title of %s', 'Shorten the titles of %s', $count, 'woocommerce' ), __( 'Shorten the title of a product', 'woocommerce' ), sprintf( _n( 'Shorten the title of %s product', 'Shorten the titles of %s products', $count, 'woocommerce' ), $n ) ),
					__( 'AI assistants cut titles over 150 characters.', 'woocommerce' ),
					__( 'Keep the brand and product type first.', 'woocommerce' )
				);
		}
		return null;
	}

	/**
	 * A fix title: with the products' names when up to NAMED_MAX products fail and every name is
	 * known, "a product" for one without a name, and the count otherwise.
	 *
	 * @param int      $count Number of products.
	 * @param string[] $names Names of the failing products, or none.
	 * @param string   $named Title with a %s for the names, singular or plural to match the count.
	 * @param string   $any   Title for one product without its name.
	 * @param string   $many  Title with the count.
	 */
	private static function title( int $count, array $names, string $named, string $any, string $many ): string {
		if ( $count <= self::NAMED_MAX && count( $names ) === $count ) {
			return sprintf( $named, wp_sprintf_l( '%l', $names ) );
		}
		return 1 === $count ? $any : $many;
	}

	/**
	 * A product check's copy.
	 *
	 * @param string $action What to do.
	 * @param string $why    Why it matters.
	 * @param string $fix    Fix line, or ''.
	 * @return array{action:string,why:string,fix:string}
	 */
	private static function product( string $action, string $why, string $fix = '' ): array {
		return array(
			'action' => $action,
			'why'    => $why,
			'fix'    => $fix,
		);
	}

	/**
	 * Store checks: passed title, failing title, why (passed), why and what was found (failing), then the fix line, and where to fix it when WooCommerce has a screen for it.
	 *
	 * @return array<string, array<int, string>>
	 */
	private static function store_checks(): array {
		$plugin_settings = __( 'Look for it in your SEO plugin’s settings.', 'woocommerce' );
		return array(
			// Can AI assistants visit your store?
			'no_noindex_directives'          => array(
				__( 'Search engines and AI assistants can show your pages', 'woocommerce' ),
				__( 'Let search engines and AI assistants show your pages', 'woocommerce' ),
				__( 'AI assistants follow the same rules as search engines to decide which pages they can show.', 'woocommerce' ),
				__( 'Your store is set to hide its pages from search engines, so AI assistants can’t find them. Check whether “Discourage search engines from indexing this site” is selected.', 'woocommerce' ),
				__( 'If it isn’t selected, check the robots settings in your SEO plugin, or ask your host.', 'woocommerce' ),
				admin_url( 'options-reading.php' ),
			),
			'pages_public'                   => array(
				__( 'Store pages open without logging in', 'woocommerce' ),
				__( 'Let shoppers open store pages without logging in', 'woocommerce' ),
				__( 'AI assistants don’t log in, so store pages need to be open to everyone.', 'woocommerce' ),
				__( 'Your store asks everyone to log in before they can see its pages. AI assistants don’t log in, so they can’t see your products.', 'woocommerce' ),
				__( 'Check your membership, private store or maintenance plugin, which can ask visitors to log in.', 'woocommerce' ),
				admin_url( 'admin.php?page=wc-settings&tab=site-visibility' ),
			),
			'ucp_profile'                    => array(
				__( 'AI assistants can connect to your store', 'woocommerce' ),
				__( 'Check why AI assistants can’t connect', 'woocommerce' ),
				__( 'They use your connection URL to find your products.', 'woocommerce' ),
				__( 'AI assistants can’t reach your connection URL, so they can’t find your products. Test it in UCP Playground, or ask your host.', 'woocommerce' ),
				__( 'Save your settings under Settings › Permalinks once. If AI assistants still can’t connect, ask your host whether caching or a firewall blocks your connection URL.', 'woocommerce' ),
			),
			'ucp_profile_version'            => array(
				__( 'Your store’s connection is up to date', 'woocommerce' ),
				__( 'Update your store’s connection', 'woocommerce' ),
				__( 'AI assistants check its version to see what your store supports.', 'woocommerce' ),
				__( 'Your store uses an older version of the Universal Commerce Protocol (UCP).', 'woocommerce' ),
				__( 'Another plugin may add its own connection. Turn off AI shopping (also called UCP or agentic commerce) in that plugin, or deactivate it.', 'woocommerce' ),
			),
			'ucp_endpoints_live'             => array(
				__( 'AI assistants can search your catalog', 'woocommerce' ),
				__( 'Check why AI assistants can’t search your catalog', 'woocommerce' ),
				__( 'This is how they find products and add them to a shopper’s cart.', 'woocommerce' ),
				__( 'AI assistants can’t search your products or add them to carts. A firewall or security plugin can cause this, so check those first or ask your host.', 'woocommerce' ),
				__( 'Ask your host or security plugin to allow requests to /wp-json/wc/ucp/.', 'woocommerce' ),
			),
			'ucp_checkout'                   => array(
				__( 'AI assistants can send shoppers to your checkout', 'woocommerce' ),
				__( 'Let AI assistants send shoppers to your checkout', 'woocommerce' ),
				__( 'Shoppers who build a cart with an AI assistant finish buying on your checkout.', 'woocommerce' ),
				__( 'Another plugin changed your store’s connection, so AI assistants can’t send shoppers to your checkout. Shoppers can still find your products.', 'woocommerce' ),
				__( 'Turn off AI shopping (also called UCP or agentic commerce) in the other plugin, or deactivate it.', 'woocommerce' ),
			),
			// Can AI assistants find your store?
			'sitemap_lists_products'         => array(
				__( 'A sitemap lists your products', 'woocommerce' ),
				__( 'Add a sitemap that lists your products', 'woocommerce' ),
				__( 'Crawlers use the sitemap to find new and changed products.', 'woocommerce' ),
				__( 'A sitemap is a list of your pages. Crawlers use it to find new and changed products. We found no sitemap that lists your products.', 'woocommerce' ),
				__( 'Turn on the sitemap in your SEO plugin and include products. Without an SEO plugin, check whether another plugin turns off the WordPress sitemap.', 'woocommerce' ),
			),
			'llms_txt_present'               => array(
				__( 'Your store has an llms.txt file', 'woocommerce' ),
				__( 'Add an llms.txt file', 'woocommerce' ),
				__( 'llms.txt gives AI assistants a short summary of your store and links to its main pages.', 'woocommerce' ),
				__( 'llms.txt is a short summary of your store with links to its main pages, which AI assistants use to describe your store. We found no file at /llms.txt.', 'woocommerce' ),
				__( 'Turn it on in your SEO plugin, or add the file to your site’s root folder.', 'woocommerce' ),
			),
			'llms_txt_valid'                 => array(
				__( 'llms.txt starts with your store’s name', 'woocommerce' ),
				__( 'Start llms.txt with your store’s name', 'woocommerce' ),
				__( 'AI assistants use it to know whose file they read.', 'woocommerce' ),
				__( 'AI assistants use the first heading to know whose file they read. We found no “# Store name” heading at the top.', 'woocommerce' ),
				__( 'Add a first line such as “# Your store name” in your SEO plugin, or in the llms.txt file in your site’s root folder.', 'woocommerce' ),
			),
			'llms_txt_links_resolve'         => array(
				__( 'Links in llms.txt open', 'woocommerce' ),
				__( 'Fix the broken links in llms.txt', 'woocommerce' ),
				__( 'A broken link wastes the visit.', 'woocommerce' ),
				__( 'A broken link wastes the AI assistant’s visit. We found links in llms.txt that don’t open.', 'woocommerce' ),
				__( 'Update or remove the broken links in your SEO plugin, or in the llms.txt file in your site’s root folder.', 'woocommerce' ),
			),
			'open_graph_tags'                => array(
				__( 'Your pages have preview details', 'woocommerce' ),
				__( 'Add preview details to your pages', 'woocommerce' ),
				__( 'AI assistants use og:title and og:image to show a page as a card.', 'woocommerce' ),
				__( 'AI assistants use them (Open Graph tags) to show a page as a card with a title and image. We found pages without them.', 'woocommerce' ),
				__( 'WooCommerce adds them to product, shop and category pages. For other pages, turn on social or Open Graph tags in your SEO plugin.', 'woocommerce' ),
			),
			'open_graph_from_core'           => array(
				__( 'Product pages keep WooCommerce’s preview details', 'woocommerce' ),
				__( 'Restore WooCommerce’s preview details on product pages', 'woocommerce' ),
				__( 'AI assistants read these tags to show your product with its WooCommerce name, price and image.', 'woocommerce' ),
				__( 'AI assistants read them (Open Graph tags) to show your product with its WooCommerce name, price and image. We found other values on a recent product page.', 'woocommerce' ),
				__( 'An SEO, social or caching plugin, or your theme, can change them. Look at its social or Open Graph settings.', 'woocommerce' ),
			),
			'meta_description'               => array(
				__( 'Store pages have a short description', 'woocommerce' ),
				__( 'Add a short description to each store page', 'woocommerce' ),
				__( 'Search engines and AI answers often quote the meta description to say what a page is about.', 'woocommerce' ),
				__( 'AI answers often quote a page’s short description (meta description) to say what it’s about. We found store pages without one.', 'woocommerce' ),
				__( 'Add them in your SEO plugin.', 'woocommerce' ),
			),
			'canonical_url'                  => array(
				__( 'Store pages name their main address', 'woocommerce' ),
				__( 'Tell AI assistants the main address of each page', 'woocommerce' ),
				__( 'The canonical link tells AI assistants which address to show.', 'woocommerce' ),
				__( 'When a page has several addresses, the canonical URL tells AI assistants which one to show. We found store pages without one.', 'woocommerce' ),
				$plugin_settings,
			),
			'organization_schema'            => array(
				__( 'Your home page has your store’s details', 'woocommerce' ),
				__( 'Add your store’s name, logo and contact details to your home page', 'woocommerce' ),
				__( 'Gives AI assistants your store’s name, logo and contact details in one place.', 'woocommerce' ),
				__( 'AI assistants read them in one place, as organization structured data. We found none on your home page.', 'woocommerce' ),
				$plugin_settings,
			),
			'ai_snippet_directives'          => array(
				__( 'AI assistants can quote your pages', 'woocommerce' ),
				__( 'Let AI assistants quote your pages', 'woocommerce' ),
				__( 'A nosnippet rule keeps AI answers from quoting your pages.', 'woocommerce' ),
				__( 'A nosnippet rule keeps AI answers from quoting your pages. We found one on your store pages.', 'woocommerce' ),
				__( 'Remove nosnippet and max-snippet from the robots settings in your SEO plugin, or ask your host.', 'woocommerce' ),
			),
			'sitemap_lastmod'                => array(
				__( 'Your sitemap says when pages change', 'woocommerce' ),
				__( 'Tell AI assistants when pages change', 'woocommerce' ),
				__( 'Crawlers come back for changed prices and stock first.', 'woocommerce' ),
				__( 'Your sitemap has no lastmod dates, which say when a page last changed. With them, crawlers come back for new prices and stock first.', 'woocommerce' ),
				__( 'WooCommerce can’t add this.', 'woocommerce' ),
			),
			'rss_feed'                       => array(
				__( 'Your home page links to a feed', 'woocommerce' ),
				__( 'Link to a feed from your home page', 'woocommerce' ),
				__( 'Some AI assistants follow your feed to see what is new.', 'woocommerce' ),
				__( 'A feed lists your newest content. Some AI assistants follow it to see what’s new. We found no feed link on your home page.', 'woocommerce' ),
				__( 'Check whether your theme or a plugin removes the feed links WordPress adds.', 'woocommerce' ),
			),
			// Can AI assistants read your product details?
			'product_jsonld_present'         => array(
				__( 'Product pages have product details AI assistants can read', 'woocommerce' ),
				__( 'Add product details AI assistants can read', 'woocommerce' ),
				__( 'Crawlers read the product’s JSON-LD to get its name, price and stock.', 'woocommerce' ),
				__( 'WooCommerce adds the name, price and stock to product pages as structured data, so AI assistants don’t have to guess. A theme or SEO plugin may have removed it. We found none on a recent product page.', 'woocommerce' ),
				__( 'Test a product page in Google’s Rich Results Test, then look for a theme or plugin setting that turns off WooCommerce structured data.', 'woocommerce' ),
			),
			'product_jsonld_from_core'       => array(
				__( 'Product details come from WooCommerce, once', 'woocommerce' ),
				__( 'Check why product details appear twice', 'woocommerce' ),
				__( 'AI assistants may read a different name or price than your product shows.', 'woocommerce' ),
				__( 'AI assistants may read a different name or price than your product shows. We found two sets of structured data, or another set than WooCommerce’s, on a recent product page.', 'woocommerce' ),
				__( 'Look at the schema settings in your SEO plugin or theme. If you set this up on purpose, you can ignore it.', 'woocommerce' ),
			),
			'product_jsonld_required_fields' => array(
				__( 'Product details have a name, image and price', 'woocommerce' ),
				__( 'Add the name, image and price to your product details', 'woocommerce' ),
				__( 'Without these, AI assistants can’t show the product as something to buy.', 'woocommerce' ),
				__( 'Without them in the structured data, AI assistants can’t show the product as something to buy. We found a recent product page without one of them.', 'woocommerce' ),
				__( 'Give the product a name, an image and a price. If it has them, look at the schema settings in your SEO plugin or theme.', 'woocommerce' ),
			),
			'product_offer_jsonld'           => array(
				__( 'Product details have price and stock', 'woocommerce' ),
				__( 'Add price and stock to your product details', 'woocommerce' ),
				__( 'AI assistants compare price and availability before they suggest a product.', 'woocommerce' ),
				__( 'AI assistants compare price and availability before they suggest a product. We found a recent product page without one of them.', 'woocommerce' ),
				__( 'Set a price and stock status on the product. If it has them, look at the schema settings in your SEO plugin or theme.', 'woocommerce' ),
			),
			'product_gtin_jsonld'            => array(
				__( 'Product details have the barcode number', 'woocommerce' ),
				__( 'Check why product details have no barcode number', 'woocommerce' ),
				__( 'AI assistants match products by their barcode number.', 'woocommerce' ),
				__( 'AI assistants match products by their barcode number. Your product has one, but we found none, or an invalid one, in its structured data.', 'woocommerce' ),
				__( 'Look at the schema settings in your SEO plugin or theme.', 'woocommerce' ),
			),
			'product_brand_jsonld'           => array(
				__( 'Product details have the brand', 'woocommerce' ),
				__( 'Check why product details have no brand', 'woocommerce' ),
				__( 'AI assistants use the brand to match and compare products.', 'woocommerce' ),
				__( 'AI assistants use the brand to match and compare products. Your product has one, but we found none in its structured data.', 'woocommerce' ),
				__( 'Look at the schema settings in your SEO plugin or theme.', 'woocommerce' ),
			),
			'content_in_raw_html'            => array(
				__( 'Product name and price show without JavaScript', 'woocommerce' ),
				__( 'Make product name and price visible without JavaScript', 'woocommerce' ),
				__( 'Most AI crawlers don’t run JavaScript.', 'woocommerce' ),
				__( 'Most AI crawlers don’t run JavaScript, so they miss what loads with it. We found a recent product page that shows its name or price only with JavaScript.', 'woocommerce' ),
				__( 'Ask your theme or page builder’s developer, or try another theme.', 'woocommerce' ),
			),
			'viewport_meta'                  => array(
				__( 'Your home page fits small screens', 'woocommerce' ),
				__( 'Make your home page fit small screens', 'woocommerce' ),
				__( 'Without it, the layout can break for browser AI assistants.', 'woocommerce' ),
				__( 'Your home page has no viewport tag, which tells browsers how to size the page. Without it, the layout can break for AI assistants that browse like people.', 'woocommerce' ),
				__( 'Ask your theme’s developer, or try another theme.', 'woocommerce' ),
			),
			'html_lang_and_charset'          => array(
				__( 'Your home page sets its language', 'woocommerce' ),
				__( 'Set your home page’s language', 'woocommerce' ),
				__( 'AI assistants answer shoppers in their language without garbled text.', 'woocommerce' ),
				__( 'Your home page doesn’t declare its language or text encoding. AI assistants use them to answer shoppers in the right language, without garbled text.', 'woocommerce' ),
				__( 'Ask your theme’s developer, or try another theme.', 'woocommerce' ),
			),
			'images_have_alt'                => array(
				__( 'Your images are described', 'woocommerce' ),
				__( 'Describe your product images', 'woocommerce' ),
				__( 'AI assistants that can’t see images rely on alt text to know what a picture shows.', 'woocommerce' ),
				__( 'AI assistants that can’t see images rely on alt text, a short description of each picture, to understand them. We found product images with no alt text.', 'woocommerce' ),
				__( 'Add alt text to images in the media library and to product images.', 'woocommerce' ),
				admin_url( 'upload.php' ),
			),
			'single_h1'                      => array(
				__( 'Product pages have one main heading', 'woocommerce' ),
				__( 'Use one main heading on product pages', 'woocommerce' ),
				__( 'AI assistants read the H1 as the name of the product.', 'woocommerce' ),
				__( 'AI assistants read the main heading (H1) as the product name. We found a recent product page with none, or more than one.', 'woocommerce' ),
				__( 'Ask your theme’s developer, or try another theme.', 'woocommerce' ),
			),
			'schema_matches_product'         => array(
				__( 'Product details match the product page', 'woocommerce' ),
				__( 'Make product details match the product page', 'woocommerce' ),
				__( 'Otherwise shoppers may see a price your store doesn’t charge.', 'woocommerce' ),
				__( 'Your structured data shows a different name or price than the product page, so shoppers may see a price you don’t charge.', 'woocommerce' ),
				__( 'Look for a plugin that changes WooCommerce structured data.', 'woocommerce' ),
			),
			'product_review_markup'          => array(
				__( 'Product details include reviews', 'woocommerce' ),
				__( 'Add product reviews to your product details', 'woocommerce' ),
				__( 'AI assistants compare ratings between stores.', 'woocommerce' ),
				__( 'AI assistants compare ratings between stores. We found a reviewed product with no reviews in its structured data.', 'woocommerce' ),
				__( 'WooCommerce adds ratings to product details. Check whether your theme or SEO plugin removes them.', 'woocommerce' ),
			),
			// Can shoppers buy through AI assistants?
			'product_return_policy_schema'   => array(
				__( 'Product details link your returns policy', 'woocommerce' ),
				__( 'Add your returns policy to your product details', 'woocommerce' ),
				__( 'AI assistants tell shoppers whether they can return an item before they buy.', 'woocommerce' ),
				__( 'AI assistants tell shoppers whether they can return an item before they buy. We found no returns policy in the structured data of a recent product page.', 'woocommerce' ),
				__( 'WooCommerce adds it once you choose a refund and returns page. If you have, check the schema settings in your SEO plugin or theme.', 'woocommerce' ),
			),
			'policy_pages_linked'            => array(
				__( 'Your home page links to your privacy and returns pages', 'woocommerce' ),
				__( 'Link to your privacy and returns pages from your home page', 'woocommerce' ),
				__( 'AI assistants look for policy links before they send a shopper to your checkout.', 'woocommerce' ),
				__( 'AI assistants look for them before they send a shopper to your checkout. We found no link to one of them on your home page.', 'woocommerce' ),
				__( 'Add your privacy policy and refund and returns pages to your footer or a menu.', 'woocommerce' ),
			),
		);
	}
}
