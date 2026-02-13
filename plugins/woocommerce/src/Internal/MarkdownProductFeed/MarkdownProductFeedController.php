<?php
/**
 * MarkdownProductFeedController class file.
 *
 * Main entry point for the markdown product feed feature. Hooks into
 * template_redirect to intercept requests with ?feed=markdown and routes
 * them to the appropriate renderer.
 *
 * @package Automattic\WooCommerce\Internal\MarkdownProductFeed
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MarkdownProductFeed;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller for the Markdown Product Feed feature.
 *
 * Intercepts requests with `?feed=markdown` on product and product archive
 * pages, renders the appropriate markdown output, and serves it with the
 * correct headers. Implements caching to avoid re-rendering on every request.
 *
 * @since 10.6.0
 */
class MarkdownProductFeedController implements RegisterHooksInterface {

	/**
	 * The single-product renderer.
	 *
	 * @var MarkdownRenderer
	 */
	private MarkdownRenderer $renderer;

	/**
	 * The archive renderer.
	 *
	 * @var MarkdownArchiveRenderer
	 */
	private MarkdownArchiveRenderer $archive_renderer;

	/**
	 * The cache layer.
	 *
	 * @var MarkdownProductFeedCache
	 */
	private MarkdownProductFeedCache $cache;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param MarkdownRenderer         $renderer         The product renderer.
	 * @param MarkdownArchiveRenderer  $archive_renderer The archive renderer.
	 * @param MarkdownProductFeedCache $cache            The cache layer.
	 */
	final public function init(
		MarkdownRenderer $renderer,
		MarkdownArchiveRenderer $archive_renderer,
		MarkdownProductFeedCache $cache
	): void {
		$this->renderer         = $renderer;
		$this->archive_renderer = $archive_renderer;
		$this->cache            = $cache;
	}

	/**
	 * Register hooks.
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

		add_action( 'template_redirect', array( $this, 'handle_template_redirect' ), 5 );
	}

	/**
	 * Handle the template_redirect action.
	 *
	 * Routes markdown feed requests to the appropriate renderer based on
	 * the current query context.
	 *
	 * @internal
	 *
	 * @since 10.6.0
	 *
	 * @return void
	 */
	public function handle_template_redirect(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$feed = sanitize_text_field( wp_unslash( $_GET['feed'] ?? '' ) );

		if ( 'markdown' !== $feed ) {
			return;
		}

		if ( is_singular( 'product' ) ) {
			$this->serve_single_product();
		} elseif ( is_shop() || is_product_taxonomy() ) {
			$this->serve_archive();
		} else {
			$this->send_error( 404, 'The markdown feed is only available on product and product archive pages.' );
		}
	}

	/**
	 * Serve a single product as markdown.
	 *
	 * @return void
	 */
	private function serve_single_product(): void {
		global $post;

		$product = wc_get_product( $post );

		if ( ! $product || ! $this->renderer->is_feed_visible( $product ) ) {
			$this->send_error( 404, 'Product not found.' );
			return;
		}

		$cached = $this->cache->get_single( $product->get_id() );

		if ( null !== $cached ) {
			$this->send_markdown( $cached );
			return;
		}

		$markdown = $this->renderer->render( $product );

		$this->cache->set_single( $product->get_id(), $markdown );

		$this->send_markdown( $markdown );
	}

	/**
	 * Serve an archive page as markdown.
	 *
	 * @return void
	 */
	private function serve_archive(): void {
		global $wp_query;

		$products = array();

		if ( ! empty( $wp_query->posts ) ) {
			foreach ( $wp_query->posts as $archive_post ) {
				$product = wc_get_product( $archive_post );

				if ( $product instanceof WC_Product && $this->renderer->is_feed_visible( $product ) ) {
					$products[] = $product;
				}
			}
		}

		$queried_object = get_queried_object();

		if ( $queried_object instanceof \WP_Term ) {
			$type    = $queried_object->taxonomy;
			$term_id = $queried_object->term_id;
			$title   = wp_strip_all_tags( get_the_archive_title() );
		} else {
			$type    = 'shop';
			$term_id = 0;
			$title   = __( 'Shop', 'woocommerce' );
		}

		$page           = max( 1, get_query_var( 'paged', 1 ) );
		$total_pages    = (int) $wp_query->max_num_pages;
		$total_products = (int) $wp_query->found_posts;
		$base_url       = add_query_arg( 'feed', 'markdown' );

		// Check cache.
		$cached = $this->cache->get_archive( $type, $term_id, $page );

		if ( null !== $cached ) {
			$this->send_markdown( $cached );
			return;
		}

		$context = array(
			'title'          => $title,
			'page'           => $page,
			'total_pages'    => $total_pages,
			'total_products' => $total_products,
			'base_url'       => $base_url,
		);

		$markdown = $this->archive_renderer->render( $products, $context );

		$this->cache->set_archive( $type, $term_id, $page, $markdown );

		$this->send_markdown( $markdown );
	}

	/**
	 * Send a markdown response with appropriate headers.
	 *
	 * @param string $content The markdown content to output.
	 * @return void
	 */
	private function send_markdown( string $content ): void {
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		header( 'Cache-Control: public, max-age=3600' );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Send an error response as markdown.
	 *
	 * @param int    $status  HTTP status code.
	 * @param string $message Error message.
	 * @return void
	 */
	private function send_error( int $status, string $message ): void {
		status_header( $status );
		$this->send_markdown( "# Error\n\n" . $message );
	}
}
