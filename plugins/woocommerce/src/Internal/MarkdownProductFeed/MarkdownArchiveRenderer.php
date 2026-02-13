<?php
/**
 * MarkdownArchiveRenderer class file.
 *
 * Renders archive pages (shop, category, tag) by wrapping multiple product
 * summaries into a single structured markdown document.
 *
 * @package Automattic\WooCommerce\Internal\MarkdownProductFeed
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MarkdownProductFeed;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Archive renderer for WooCommerce product feeds.
 *
 * Combines individual product summaries from MarkdownRenderer into a
 * paginated archive page with YAML front matter and navigation links.
 *
 * @since 10.6.0
 */
class MarkdownArchiveRenderer {

	/**
	 * The product renderer used for individual product summaries.
	 *
	 * @var MarkdownRenderer
	 */
	private MarkdownRenderer $renderer;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param MarkdownRenderer $renderer The product renderer.
	 */
	final public function init( MarkdownRenderer $renderer ): void {
		$this->renderer = $renderer;
	}

	/**
	 * Render an archive page as markdown.
	 *
	 * Combines product summaries with front matter, heading, and pagination
	 * navigation into a complete markdown document.
	 *
	 * @since 10.6.0
	 *
	 * @param WC_Product[] $products Array of products to render as summaries.
	 * @param array        $context  {
	 *     Archive context.
	 *
	 *     @type string $title          Archive title (e.g., "Shop", "Clothing").
	 *     @type int    $page           Current page number.
	 *     @type int    $total_pages    Total number of pages.
	 *     @type int    $total_products Total product count.
	 *     @type string $base_url       Base URL for pagination links.
	 * }
	 * @return string The complete archive markdown.
	 */
	public function render( array $products, array $context ): string {
		$title          = $context['title'];
		$page           = $context['page'];
		$total_pages    = $context['total_pages'];
		$total_products = $context['total_products'];
		$base_url       = $context['base_url'];

		$sections = array();

		// Front matter.
		$sections[] = $this->render_front_matter( $context );

		// Archive heading.
		$display_total = max( 1, $total_pages );
		$sections[]    = '# ' . $title . ' - Page ' . $page . ' of ' . $display_total;

		// Product summaries separated by horizontal rules.
		if ( empty( $products ) ) {
			$sections[] = 'No products found.';
		} else {
			$summaries = array();
			foreach ( $products as $product ) {
				$summaries[] = $this->renderer->render_summary( $product );
			}
			$sections[] = implode( "\n\n---\n\n", $summaries );
		}

		// Navigation (only if more than one page).
		if ( $total_pages > 1 ) {
			$nav = $this->render_navigation( $page, $total_pages );
			if ( '' !== $nav ) {
				$sections[] = $nav;
			}
		}

		$markdown = implode( "\n\n", $sections );

		/**
		 * Filters the final archive markdown output.
		 *
		 * @since 10.6.0
		 *
		 * @param string       $markdown The rendered archive markdown.
		 * @param WC_Product[] $products The products that were rendered.
		 * @param array        $context  The archive context array.
		 */
		return apply_filters( 'woocommerce_markdown_feed_archive', $markdown, $products, $context );
	}

	/**
	 * Render the YAML front matter block for the archive.
	 *
	 * @param array $context The archive context.
	 * @return string The front matter block including delimiters.
	 */
	private function render_front_matter( array $context ): string {
		$meta = array(
			'store'          => get_bloginfo( 'name' ),
			'currency'       => get_woocommerce_currency(),
			'generated'      => current_datetime()->format( 'c' ),
			'type'           => 'product_archive',
			'title'          => $context['title'],
			'page'           => $context['page'],
			'total_pages'    => $context['total_pages'],
			'total_products' => $context['total_products'],
		);

		/**
		 * Filters the YAML front matter metadata array.
		 *
		 * @since 10.6.0
		 *
		 * @param array<string, mixed> $meta    The metadata key-value pairs.
		 * @param WC_Product|null      $product The product being rendered, or null for archives.
		 */
		$meta = apply_filters( 'woocommerce_markdown_feed_meta', $meta, null );

		$lines = array( '---' );
		foreach ( $meta as $key => $value ) {
			$lines[] = sanitize_key( $key ) . ': ' . $this->sanitize_yaml_value( (string) $value );
		}
		$lines[] = '---';

		return implode( "\n", $lines );
	}

	/**
	 * Sanitize a value for YAML front matter output.
	 *
	 * Strips newlines and wraps in quotes if the value contains special
	 * YAML characters that could break parsing.
	 *
	 * @param string $value The value to sanitize.
	 * @return string The sanitized value.
	 */
	private function sanitize_yaml_value( string $value ): string {
		// Strip newlines to prevent injection of additional YAML keys.
		$value = str_replace( array( "\n", "\r" ), ' ', $value );

		// Quote values that contain characters with special meaning in YAML.
		if ( preg_match( '/[:#\[\]{}|>*&!%@`]/', $value ) ) {
			$value = '"' . str_replace( '"', '\\"', $value ) . '"';
		}

		return $value;
	}

	/**
	 * Render the navigation section with previous/next page links.
	 *
	 * @param int $page        Current page number.
	 * @param int $total_pages Total number of pages.
	 * @return string The navigation section markdown.
	 */
	private function render_navigation( int $page, int $total_pages ): string {
		$lines   = array();
		$lines[] = '## Navigation';
		$lines[] = '';

		if ( $page > 1 ) {
			$prev_url = add_query_arg( 'feed', 'markdown', get_pagenum_link( $page - 1 ) );
			$lines[]  = '- Previous: [Page ' . ( $page - 1 ) . '](' . $prev_url . ')';
		}

		if ( $page < $total_pages ) {
			$next_url = add_query_arg( 'feed', 'markdown', get_pagenum_link( $page + 1 ) );
			$lines[]  = '- Next: [Page ' . ( $page + 1 ) . '](' . $next_url . ')';
		}

		return implode( "\n", $lines );
	}
}
