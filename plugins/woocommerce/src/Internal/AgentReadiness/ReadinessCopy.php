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
	 * UCP Playground, where a merchant can test the store's connection as AI assistants see it.
	 */
	public const UCP_PLAYGROUND_URL = 'https://ucpplayground.com/';

	/**
	 * Up to how many failing products a fix title names; more are given as a count.
	 */
	public const NAMED_MAX = 3;


	/**
	 * A store check's copy.
	 *
	 * @param string $id     Check id.
	 * @param string $source What the store serves for the check (the report's source), or ''.
	 * @return array{title:string,action:string,why:string,found:string,fix:string,url:string,found_link:array{href:string,text:string}|null}|null Passed title, failing title, why it matters (shown when passed), why and what was found (shown when failing), the fix line or '', where to fix it or '', and an optional tool name linked in the found line. Null for a check without copy here.
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
		if ( 'ucp_endpoints_live' === $id && '' !== $source ) {
			/* translators: %s: HTTP status and content type, such as 403 text/html. */
			$check[3] .= ' ' . sprintf( __( 'The last answer was %s.', 'woocommerce' ), $source );
		}
		return array(
			'title'      => $check[0],
			'action'     => $check[1],
			'why'        => $check[2],
			'found'      => $check[3],
			'fix'        => $check[4] ?? '',
			'url'        => $check[5] ?? '',
			// A tool the found line names, linked where the name appears.
			'found_link' => 'ucp_profile' === $id
				? array(
					'href' => self::UCP_PLAYGROUND_URL,
					'text' => __( 'UCP Playground', 'woocommerce' ),
				)
				: null,
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
				return array( 'pass' => __( 'From WooCommerce, using your store’s details.', 'woocommerce' ) );
			case 'file':
				return array( 'pass' => __( 'From a file in your site’s root folder.', 'woocommerce' ) );
			case 'other':
				return array( 'pass' => __( 'Added by a plugin.', 'woocommerce' ) );
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
					__( 'Use the number from the packaging or your supplier, not one you create yourself.', 'woocommerce' )
				);
			case 'invalid_gtin':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Fix the barcode number of %s', 'Fix the barcode numbers of %s', $count, 'woocommerce' ), __( 'Fix the barcode number of a product', 'woocommerce' ), sprintf( _n( 'Fix the barcode number of %s product', 'Fix the barcode numbers of %s products', $count, 'woocommerce' ), $n ) ),
					_n( 'The barcode number listed isn’t valid, so AI shopping apps can’t use it to match your product. A barcode number has 8, 12, 13, or 14 digits.', 'The barcode numbers listed aren’t valid, so AI shopping apps can’t use them to match your products. A barcode number has 8, 12, 13, or 14 digits.', $count, 'woocommerce' ),
					__( 'Use the number from the packaging or your supplier.', 'woocommerce' )
				);
			case 'duplicate_gtin':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Give %s its own barcode number', 'Give %s their own barcode numbers', $count, 'woocommerce' ), __( 'Give a product its own barcode number', 'woocommerce' ), sprintf( _n( 'Give %s product its own barcode number', 'Give %s products their own barcode numbers', $count, 'woocommerce' ), $n ) ),
					__( 'Products that share a barcode number look like the same product, so AI shopping apps may show the wrong one.', 'woocommerce' ),
					__( 'Use the number from the packaging or your supplier.', 'woocommerce' )
				);
			case 'missing_regular_price':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Add a price to %s', 'Add a price to %s', $count, 'woocommerce' ), __( 'Add a price to a product', 'woocommerce' ), sprintf( _n( 'Add a price to %s product', 'Add a price to %s products', $count, 'woocommerce' ), $n ) ),
					__( 'AI shopping apps only list products that have a price.', 'woocommerce' )
				);
			case 'missing_images':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Add a main image to %s', 'Add a main image to %s', $count, 'woocommerce' ), __( 'Add a main image to a product', 'woocommerce' ), sprintf( _n( 'Add a main image to %s product', 'Add a main image to %s products', $count, 'woocommerce' ), $n ) ),
					__( 'AI shopping apps need a main image to list a product.', 'woocommerce' ),
					__( 'Add it in the Product image box on each product.', 'woocommerce' )
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
					__( 'AI shopping apps use the description to explain a product to shoppers.', 'woocommerce' )
				);
			case 'uncategorised':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Choose a category for %s', 'Choose a category for %s', $count, 'woocommerce' ), __( 'Choose a category for a product', 'woocommerce' ), sprintf( _n( 'Choose a category for %s product', 'Choose a category for %s products', $count, 'woocommerce' ), $n ) ),
					__( 'AI shopping apps narrow searches by category, so products left in “Uncategorized” are harder to find.', 'woocommerce' )
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
					__( 'Each variation is listed on its own. Without its own image, it uses the main image, which may show the wrong color.', 'woocommerce' )
				);
			case 'title_too_long':
				return self::product(
					/* translators: %s: names of up to three products, such as "Blue hoodie and Red cap", or number of products. */
					self::title( $count, $names, _n( 'Shorten the name of %s', 'Shorten the names of %s', $count, 'woocommerce' ), __( 'Shorten the name of a product', 'woocommerce' ), sprintf( _n( 'Shorten the name of %s product', 'Shorten the names of %s products', $count, 'woocommerce' ), $n ) ),
					__( 'AI shopping apps cut off product names longer than 150 characters.', 'woocommerce' ),
					__( 'Put the brand and product type first.', 'woocommerce' )
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
				__( 'Most AI assistants don’t log in, so they only read pages that are open to everyone.', 'woocommerce' ),
				__( 'Your store asks everyone to log in before they can see its pages. Most AI assistants don’t log in, so they can’t see your products.', 'woocommerce' ),
				__( 'If your store isn’t meant to be members-only, check your membership, private store or maintenance plugin, which can ask visitors to log in.', 'woocommerce' ),
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
				__( 'AI assistants can’t search your products or add them to carts. A firewall or security plugin can cause this. Check those first, or ask your host.', 'woocommerce' ),
				__( 'Ask your host or security plugin to allow requests to /wp-json/wc/ucp/.', 'woocommerce' ),
			),
			'ucp_checkout'                   => array(
				__( 'AI assistants can send shoppers to your checkout', 'woocommerce' ),
				__( 'Let AI assistants send shoppers to your checkout', 'woocommerce' ),
				__( 'Shoppers who build a cart with an AI assistant finish buying on your checkout.', 'woocommerce' ),
				__( 'Another plugin has replaced your store’s connection with one that doesn’t support checkout. AI assistants can still find your products, but shoppers who build a cart with them can’t reach your checkout.', 'woocommerce' ),
				__( 'In the other plugin, turn off any setting for AI shopping, UCP, or agentic commerce. Or deactivate the plugin.', 'woocommerce' ),
				admin_url( 'plugins.php' ),
			),
			// Search and discovery.
			'sitemap_lists_products'         => array(
				__( 'A sitemap lists your products', 'woocommerce' ),
				__( 'Add a sitemap that lists your products', 'woocommerce' ),
				__( 'Search engines and AI assistants use it to find new and updated products.', 'woocommerce' ),
				__( 'Your sitemap doesn’t list your products, so AI assistants can be slower to find new ones.', 'woocommerce' ),
				__( 'Check the sitemap settings in your SEO plugin. If you don’t use one, another plugin may have turned off the WordPress sitemap.', 'woocommerce' ),
			),
			'llms_txt_present'               => array(
				__( 'Your store has a summary file for AI assistants (llms.txt)', 'woocommerce' ),
				__( 'Add a summary file for AI assistants (llms.txt)', 'woocommerce' ),
				__( 'This file gives AI assistants a short summary of your store and links to its main pages.', 'woocommerce' ),
				__( 'Your store doesn’t have one, so AI assistants are unable to get a summary of your store.', 'woocommerce' ),
				__( 'Some SEO plugins can create one for you. You can also add it to your site’s main folder.', 'woocommerce' ),
			),
			'llms_txt_valid'                 => array(
				__( 'Your llms.txt file starts with your store’s name', 'woocommerce' ),
				__( 'Start your llms.txt file with your store’s name', 'woocommerce' ),
				__( 'AI assistants read the first heading to see which store the file belongs to.', 'woocommerce' ),
				__( 'Your llms.txt file doesn’t start with your store’s name. AI assistants read the first heading to see whose file it is.', 'woocommerce' ),
				__( 'Add your store’s name as the file’s first heading.', 'woocommerce' ),
			),
			'llms_txt_links_resolve'         => array(
				__( 'The links in your llms.txt file work', 'woocommerce' ),
				__( 'Fix the broken links in your llms.txt file', 'woocommerce' ),
				__( 'AI assistants follow these links to learn about your store’s main pages.', 'woocommerce' ),
				__( 'Some links in the file don’t open. AI assistants use these links to reach your store’s main pages.', 'woocommerce' ),
				__( 'Update or remove the broken links in the file.', 'woocommerce' ),
			),
			'open_graph_tags'                => array(
				__( 'Your pages have preview details', 'woocommerce' ),
				__( 'Add preview details to your pages', 'woocommerce' ),
				__( 'These details, also called Open Graph tags, let AI assistants show your pages as a card with a title and image.', 'woocommerce' ),
				__( 'Some pages don’t have preview details. AI assistants use these to show a page as a card with a title and image.', 'woocommerce' ),
				__( 'Most SEO plugins can add them in their social or sharing settings, where they’re often called Open Graph tags.', 'woocommerce' ),
			),
			'open_graph_from_core'           => array(
				__( 'Product pages keep WooCommerce’s preview details', 'woocommerce' ),
				__( 'Restore WooCommerce’s preview details on product pages', 'woocommerce' ),
				__( 'AI assistants read these to show your products with the right name, price, and image.', 'woocommerce' ),
				__( 'On a recent product page, WooCommerce’s preview details are missing or replaced, so AI assistants may show the wrong name, price, or image.', 'woocommerce' ),
				__( 'Check your theme and plugins for settings that change product previews, often called Open Graph tags.', 'woocommerce' ),
			),
			'meta_description'               => array(
				__( 'Store pages have a search description', 'woocommerce' ),
				__( 'Add a search description to each store page', 'woocommerce' ),
				__( 'Search engines and AI assistants can show this description to explain what a page is about. It’s sometimes called a meta description.', 'woocommerce' ),
				__( 'Some store pages don’t have a search description. Search engines and AI assistants show it to explain what a page is about.', 'woocommerce' ),
				__( 'Add one to each page in your SEO plugin, where it’s often called a meta description.', 'woocommerce' ),
			),
			'canonical_url'                  => array(
				__( 'Store pages have a main URL', 'woocommerce' ),
				__( 'Set a main URL for each store page', 'woocommerce' ),
				__( 'When a page can be reached at more than one URL, this tells AI assistants which one to show. It’s sometimes called a canonical URL.', 'woocommerce' ),
				__( 'Some store pages don’t set a main URL. When a page has more than one URL, this tells AI assistants which one to show.', 'woocommerce' ),
				__( 'Most SEO plugins set this for you, where it’s often called a canonical URL.', 'woocommerce' ),
			),
			'organization_schema'            => array(
				__( 'Your home page shares your store’s details with AI assistants', 'woocommerce' ),
				__( 'Share your store’s details with AI assistants', 'woocommerce' ),
				__( 'Your store’s name, logo, and contact details are added to your home page’s code, so AI assistants can find them in one place. This is sometimes called organization structured data.', 'woocommerce' ),
				__( 'Your home page’s code doesn’t include your store’s name, logo, and contact details. AI assistants look for them there, all in one place.', 'woocommerce' ),
				__( 'Most SEO plugins can add them in their site or organization settings.', 'woocommerce' ),
			),
			'ai_snippet_directives'          => array(
				__( 'AI assistants can quote your pages', 'woocommerce' ),
				__( 'Let AI assistants quote your pages', 'woocommerce' ),
				__( 'Short quotes from your pages can appear in AI answers.', 'woocommerce' ),
				__( 'A “nosnippet” setting is stopping AI assistants from quoting your pages. Short quotes help your pages appear in AI answers.', 'woocommerce' ),
				__( 'Turn off “nosnippet” in your SEO plugin.', 'woocommerce' ),
			),
			'sitemap_lastmod'                => array(
				__( 'Your sitemap shows when pages change', 'woocommerce' ),
				__( 'Tell AI assistants when pages change', 'woocommerce' ),
				__( 'This helps AI assistants spot new prices and stock sooner.', 'woocommerce' ),
				__( 'Your sitemap doesn’t show when pages last changed. AI assistants use these dates to spot new prices and stock sooner.', 'woocommerce' ),
				__( 'Turn on last-updated dates in your SEO plugin’s sitemap settings.', 'woocommerce' ),
			),
			'rss_feed'                       => array(
				__( 'Your home page links to a feed', 'woocommerce' ),
				__( 'Link to a feed from your home page', 'woocommerce' ),
				__( 'Some AI assistants follow your feed to see what’s new.', 'woocommerce' ),
				__( 'Your home page doesn’t link to a feed. Some AI assistants follow one to see what’s new in your store.', 'woocommerce' ),
				__( 'Your theme or a plugin may be hiding the link. Check their settings.', 'woocommerce' ),
			),
			// Product details.
			'product_jsonld_present'         => array(
				__( 'AI assistants can read your product details', 'woocommerce' ),
				__( 'Let AI assistants read your product details', 'woocommerce' ),
				__( 'WooCommerce adds each product’s name, price, and stock to the page’s code for AI assistants.', 'woocommerce' ),
				__( 'Your newest product page doesn’t include these details in its code, so AI assistants may get them wrong. A theme or plugin may have turned this off, which usually affects every product page.', 'woocommerce' ),
				__( 'Check their settings for WooCommerce structured data.', 'woocommerce' ),
			),
			'product_jsonld_from_core'       => array(
				__( 'Your product pages have one set of product details', 'woocommerce' ),
				__( 'Check why product details appear twice', 'woocommerce' ),
				__( 'This means AI assistants read the same name and price your product shows.', 'woocommerce' ),
				__( 'Your product pages have two sets of product details in their code, so AI assistants may read a different name or price.', 'woocommerce' ),
				__( 'Check the schema settings in your SEO plugin or theme. If you set this up on purpose, you can ignore this.', 'woocommerce' ),
			),
			'product_jsonld_required_fields' => array(
				__( 'Product details include a name, image, and price', 'woocommerce' ),
				__( 'Add a name, image, and price to your product details', 'woocommerce' ),
				__( 'AI assistants need all three to show a product as something to buy.', 'woocommerce' ),
				__( 'A product is missing a name, image, or price in its page code, so AI assistants can’t show it.', 'woocommerce' ),
				__( 'Check that this product has an image and a price. If it does, a theme or plugin may be removing this info from the page code.', 'woocommerce' ),
			),
			'product_offer_jsonld'           => array(
				__( 'Product details include price and stock', 'woocommerce' ),
				__( 'Add price and stock to your product details', 'woocommerce' ),
				__( 'AI assistants compare price and stock before they suggest a product.', 'woocommerce' ),
				__( 'A product is missing a price or stock status in its page code, so AI assistants can’t show it.', 'woocommerce' ),
				__( 'Check that this product has a price and stock status. If it does, a theme or plugin may be removing this info from the page code.', 'woocommerce' ),
			),
			'product_gtin_jsonld'            => array(
				__( 'Product details include barcode numbers', 'woocommerce' ),
				__( 'Check why barcode numbers are missing from your page code', 'woocommerce' ),
				__( 'AI assistants use barcode numbers to match your products with what shoppers search for.', 'woocommerce' ),
				__( 'A product’s barcode number is missing or invalid in its page code, so AI assistants can’t use it.', 'woocommerce' ),
				__( 'Check the schema settings in your SEO plugin or theme.', 'woocommerce' ),
			),
			'product_brand_jsonld'           => array(
				__( 'Product details include brands', 'woocommerce' ),
				__( 'Check why brands are missing from your page code', 'woocommerce' ),
				__( 'AI assistants use brands to match and compare products.', 'woocommerce' ),
				__( 'A product’s brand is missing from its page code, so AI assistants can’t use it.', 'woocommerce' ),
				__( 'Check the schema settings in your SEO plugin or theme.', 'woocommerce' ),
			),
			'content_in_raw_html'            => array(
				__( 'Product names and prices show without JavaScript', 'woocommerce' ),
				__( 'Show product names and prices without JavaScript', 'woocommerce' ),
				__( 'Most AI assistants read pages without running scripts, so these need to be in the page itself.', 'woocommerce' ),
				__( 'Your product names and prices only appear after JavaScript runs, so most AI assistants can’t see them. A theme or page builder plugin usually causes this.', 'woocommerce' ),
				__( 'Check its settings, or contact its support team.', 'woocommerce' ),
			),
			'viewport_meta'                  => array(
				__( 'Your home page fits small screens', 'woocommerce' ),
				__( 'Make your home page fit small screens', 'woocommerce' ),
				__( 'This helps your page display correctly for AI assistants used in browsers.', 'woocommerce' ),
				__( 'Your home page doesn’t tell browsers how to size it, so the layout can break for AI assistants used in browsers.', 'woocommerce' ),
				__( 'Add a “viewport” tag to your theme.', 'woocommerce' ),
			),
			'html_lang_and_charset'          => array(
				__( 'Your home page sets its language', 'woocommerce' ),
				__( 'Set your home page’s language', 'woocommerce' ),
				__( 'This helps AI assistants read your pages correctly and reply to shoppers in the right language.', 'woocommerce' ),
				__( 'Your home page doesn’t say what language or text format it uses, so AI assistants may show garbled text or reply in the wrong language.', 'woocommerce' ),
				__( 'Ask your theme’s developer, or try another theme.', 'woocommerce' ),
			),
			'images_have_alt'                => array(
				__( 'Your product images have alt text', 'woocommerce' ),
				__( 'Add alt text to your product images', 'woocommerce' ),
				__( 'AI assistants that can’t see images use alt text to know what they show.', 'woocommerce' ),
				__( 'Some product images don’t have alt text. AI assistants that can’t see images use it to know what they show.', 'woocommerce' ),
				__( 'Add it in each image’s Alternative Text field.', 'woocommerce' ),
				admin_url( 'upload.php' ),
			),
			'single_h1'                      => array(
				__( 'Product pages have one main heading', 'woocommerce' ),
				__( 'Use one main heading on product pages', 'woocommerce' ),
				__( 'AI assistants read the main heading as the product’s name.', 'woocommerce' ),
				__( 'Your product pages have no main heading or more than one, so AI assistants may read the wrong product name.', 'woocommerce' ),
				__( 'Ask your theme’s developer, or try another theme.', 'woocommerce' ),
			),
			'schema_matches_product'         => array(
				__( 'Product details match the product page', 'woocommerce' ),
				__( 'Make product details match the product page', 'woocommerce' ),
				__( 'This means AI assistants show shoppers the same name and price as your store.', 'woocommerce' ),
				__( 'On a product, the name or price in the page code is different from what the page shows, so AI assistants may show a price you don’t charge.', 'woocommerce' ),
				__( 'A plugin may be changing WooCommerce’s product details. Check its schema or structured data settings.', 'woocommerce' ),
			),
			'product_review_markup'          => array(
				__( 'Product details include reviews', 'woocommerce' ),
				__( 'Include reviews in your product details', 'woocommerce' ),
				__( 'AI assistants use ratings to compare products between stores.', 'woocommerce' ),
				__( 'Your product reviews aren’t in your page code, so AI assistants can’t compare your ratings with other stores.', 'woocommerce' ),
				__( 'Check that reviews are turned on.', 'woocommerce' ),
				admin_url( 'admin.php?page=wc-settings&tab=products' ),
			),
			// Buying through AI assistants.
			'product_return_policy_schema'   => array(
				__( 'Product details include your returns policy', 'woocommerce' ),
				__( 'Add your returns policy to your product details', 'woocommerce' ),
				__( 'AI assistants use it to tell shoppers whether they can return an item before they buy.', 'woocommerce' ),
				__( 'Your product page code doesn’t include your returns policy, so AI assistants can’t tell shoppers whether they can return an item.', 'woocommerce' ),
				// Shown only once a published refund and returns page is chosen, so the fix can't be to choose one.
				__( 'WooCommerce adds it from your refund and returns page. Check the schema settings in your SEO plugin or theme.', 'woocommerce' ),
			),
			'product_shipping_schema'        => array(
				__( 'Product details include shipping', 'woocommerce' ),
				__( 'Add shipping to your product details', 'woocommerce' ),
				__( 'AI assistants use this to tell shoppers what delivery costs before they buy.', 'woocommerce' ),
				__( 'Your product page code doesn’t include shipping details, so AI assistants may leave your products out.', 'woocommerce' ),
				__( 'Check that your shipping zones and rates are set up.', 'woocommerce' ),
				admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
			),
			'product_buy_action_schema'      => array(
				__( 'Product details say where to buy', 'woocommerce' ),
				__( 'Tell AI assistants where shoppers can buy', 'woocommerce' ),
				__( 'This tells AI assistants where to send shoppers to buy each product.', 'woocommerce' ),
				__( 'Your product page code doesn’t say where to buy each product, so AI assistants can’t send shoppers to it.', 'woocommerce' ),
			),
			'policy_pages_linked'            => array(
				__( 'Your home page links to your privacy and returns pages', 'woocommerce' ),
				__( 'Link to your privacy and returns pages from your home page', 'woocommerce' ),
				__( 'AI assistants look for policy links before they send a shopper to your checkout.', 'woocommerce' ),
				__( 'Your home page doesn’t link to one or both policies, so AI assistants may not send shoppers to your checkout.', 'woocommerce' ),
				__( 'Add links to both pages in your store’s footer.', 'woocommerce' ),
			),
		);
	}
}
