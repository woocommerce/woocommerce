<?php
/**
 * DiscoveryHandler class file.
 *
 * Provides discovery mechanisms so AI agents can find the markdown product feed.
 *
 * @package Automattic\WooCommerce\Internal\MarkdownProductFeed
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MarkdownProductFeed;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discovery handler for the Markdown Product Feed feature.
 *
 * Implements three discovery mechanisms for AI agents:
 * A) A `<link rel="alternate">` tag in the HTML `<head>` on product/archive pages.
 * B) An HTTP `Link` response header on product/archive pages.
 * C) An `llms.txt` endpoint describing the feed capabilities.
 *
 * @since 10.6.0
 */
class DiscoveryHandler implements RegisterHooksInterface {

	/**
	 * Register hooks for the discovery mechanisms.
	 *
	 * Bails early if the `markdown_product_feed` feature is not enabled.
	 *
	 * @since 10.6.0
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! FeaturesUtil::feature_is_enabled( 'markdown_product_feed' ) ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'handle_wp_head' ) );
		add_action( 'send_headers', array( $this, 'handle_send_headers' ) );
		add_action( 'init', array( $this, 'handle_init' ) );
		add_action( 'template_redirect', array( $this, 'handle_template_redirect' ) );
		add_filter( 'query_vars', array( $this, 'handle_query_vars' ) );
	}

	/**
	 * Output a `<link rel="alternate">` tag for the markdown feed on product and archive pages.
	 *
	 * @internal
	 *
	 * @since 10.6.0
	 *
	 * @return void
	 */
	public function handle_wp_head(): void {
		if ( ! $this->is_product_or_archive() ) {
			return;
		}

		$url = esc_url( add_query_arg( 'feed', 'markdown' ) );

		echo '<link rel="alternate" type="text/markdown" href="' . $url . '" />' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $url is escaped via esc_url() above.
	}

	/**
	 * Send an HTTP `Link` response header for the markdown feed on product and archive pages.
	 *
	 * @internal
	 *
	 * @since 10.6.0
	 *
	 * @return void
	 */
	public function handle_send_headers(): void {
		if ( ! $this->is_product_or_archive() ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		$url = esc_url( add_query_arg( 'feed', 'markdown' ) );

		header( 'Link: <' . $url . '>; rel="alternate"; type="text/markdown"' );
	}

	/**
	 * Register the rewrite rule for the `llms.txt` endpoint.
	 *
	 * @internal
	 *
	 * @since 10.6.0
	 *
	 * @return void
	 */
	public function handle_init(): void {
		$regex = '^llms\.txt/?$';

		add_rewrite_rule( $regex, 'index.php?wc_llms_txt=1', 'top' );

		if ( false === get_transient( 'wc_markdown_feed_rules_flushed' ) ) {
			flush_rewrite_rules();
			set_transient( 'wc_markdown_feed_rules_flushed', 1, DAY_IN_SECONDS );
		}
	}

	/**
	 * Register the `wc_llms_txt` query variable.
	 *
	 * @internal
	 *
	 * @since 10.6.0
	 *
	 * @param array $vars The registered query variables.
	 * @return array The modified query variables.
	 */
	public function handle_query_vars( array $vars ): array {
		$vars[] = 'wc_llms_txt';

		return $vars;
	}

	/**
	 * Serve the `llms.txt` content when the `wc_llms_txt` query variable is set.
	 *
	 * @internal
	 *
	 * @since 10.6.0
	 *
	 * @return void
	 */
	public function handle_template_redirect(): void {
		if ( ! get_query_var( 'wc_llms_txt' ) ) {
			return;
		}

		if ( ! FeaturesUtil::feature_is_enabled( 'markdown_product_feed' ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return;
		}

		$store_name  = get_bloginfo( 'name' );
		$description = get_bloginfo( 'description' );
		$site_url    = home_url();
		$shop_url    = wc_get_page_permalink( 'shop' );

		$content = "# {$store_name}\n\n";

		if ( '' !== $description ) {
			$content .= "> {$description}\n\n";
		}

		$content .= "## Markdown Product Feed\n\n";
		$content .= "This store supports structured markdown output for product pages.\n";
		$content .= "Append `?feed=markdown` to any product or product archive URL.\n\n";
		$content .= "### URL Patterns\n";
		$content .= "- Single product: {$site_url}/product/{slug}/?feed=markdown\n";
		$content .= "- Shop archive: {$shop_url}?feed=markdown\n";
		$content .= "- Category: {$site_url}/product-category/{slug}/?feed=markdown\n";
		$content .= "- Tag: {$site_url}/product-tag/{slug}/?feed=markdown\n\n";
		$content .= "### Response Format\n";
		$content .= "- Content-Type: text/markdown\n";
		$content .= "- YAML front matter with store metadata\n";
		$content .= "- Structured sections: title, price, description, images, attributes, variations\n";
		$content .= "- Checkout links for direct purchase\n";

		/**
		 * Filters the llms.txt content before output.
		 *
		 * @since 10.6.0
		 *
		 * @param string $content The llms.txt content string.
		 */
		$content = apply_filters( 'woocommerce_llms_txt_content', $content );

		header( 'Content-Type: text/plain; charset=utf-8' );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Check whether the current request is for a product page or a product archive.
	 *
	 * @since 10.6.0
	 *
	 * @return bool True if the current page is a single product, the shop, or a product taxonomy archive.
	 */
	private function is_product_or_archive(): bool {
		return is_singular( 'product' ) || is_shop() || is_product_taxonomy();
	}
}
