<?php
/**
 * MarkdownRenderer class file.
 *
 * Transforms WC_Product objects into structured markdown for the product feed.
 *
 * @package Automattic\WooCommerce\Internal\MarkdownProductFeed
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MarkdownProductFeed;

use WC_Product;
use WC_Product_Variable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core renderer for WooCommerce products as markdown.
 *
 * Converts a WC_Product into a structured markdown document suitable for
 * AI consumption. Supports full single-product pages and short archive
 * summaries.
 *
 * @since 10.6.0
 */
class MarkdownRenderer {

	/**
	 * HTML-to-markdown converter.
	 *
	 * @var HtmlToMarkdown
	 */
	private HtmlToMarkdown $html_to_markdown;

	/**
	 * Map of WooCommerce stock status values to human-readable labels.
	 *
	 * @var array<string, string>
	 */
	private const STOCK_STATUS_MAP = array(
		'instock'     => 'In stock',
		'outofstock'  => 'Out of stock',
		'onbackorder' => 'On backorder',
	);

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param HtmlToMarkdown $html_to_markdown The HTML-to-markdown converter.
	 */
	final public function init( HtmlToMarkdown $html_to_markdown ): void {
		$this->html_to_markdown = $html_to_markdown;
	}

	/**
	 * Render full product markdown for single product pages.
	 *
	 * @since 10.6.0
	 *
	 * @param WC_Product $product The product to render.
	 * @return string The full markdown representation of the product.
	 */
	public function render( WC_Product $product ): string {
		$sections = array();

		// Front matter.
		$sections[] = $this->render_front_matter( $product );

		// Title.
		$name       = $product->get_name();
		$sections[] = '# ' . ( '' !== $name ? $name : 'Product #' . $product->get_id() );

		// Product meta block.
		$sections[] = $this->render_meta_block( $product );

		// Short description as blockquote.
		$short_desc = $this->convert_short_description( $product );
		if ( '' !== $short_desc ) {
			$sections[] = '> ' . str_replace( "\n", "\n> ", $short_desc );
		}

		// Full description.
		$description = $this->convert_description( $product );
		if ( '' !== $description ) {
			$sections[] = "## Description\n\n" . $description;
		}

		// Images.
		$images = $this->render_images( $product );
		if ( '' !== $images ) {
			$sections[] = "## Images\n\n" . $images;
		}

		// Attributes.
		$attributes = $this->render_attributes( $product );
		if ( '' !== $attributes ) {
			$sections[] = "## Attributes\n\n" . $attributes;
		}

		// Variations (variable products only).
		if ( $product instanceof WC_Product_Variable ) {
			$variations = $this->render_variations( $product );
			if ( '' !== $variations ) {
				$sections[] = "## Variations\n\n" . $variations;
			}
		}

		// Buy link.
		$sections[] = "## Buy\n\n[Add to cart](" . $this->get_checkout_link( $product->get_id() ) . ')';

		/**
		 * Filters the array of markdown sections before joining.
		 *
		 * @since 10.6.0
		 *
		 * @param string[]   $sections Array of markdown section strings.
		 * @param WC_Product $product  The product being rendered.
		 */
		$sections = apply_filters( 'woocommerce_markdown_feed_product_sections', $sections, $product );

		$markdown = implode( "\n\n", $sections );

		/**
		 * Filters the final single-product markdown output.
		 *
		 * @since 10.6.0
		 *
		 * @param string     $markdown The rendered markdown string.
		 * @param WC_Product $product  The product that was rendered.
		 */
		return apply_filters( 'woocommerce_markdown_feed_single_product', $markdown, $product );
	}

	/**
	 * Render a short summary for archive listings.
	 *
	 * @since 10.6.0
	 *
	 * @param WC_Product $product The product to summarize.
	 * @return string The summary markdown.
	 */
	public function render_summary( WC_Product $product ): string {
		$lines = array();

		// Title.
		$name    = $product->get_name();
		$lines[] = '## ' . ( '' !== $name ? $name : 'Product #' . $product->get_id() );

		// Compact meta line.
		$meta_parts = array();

		$sku = $product->get_sku();
		if ( '' !== $sku ) {
			$meta_parts[] = '**SKU:** ' . $sku;
		}

		$price_str = $this->format_price_inline( $product );
		if ( '' !== $price_str ) {
			$meta_parts[] = '**Price:** ' . $price_str;
		}

		$meta_parts[] = '**Stock:** ' . $this->format_stock_status( $product->get_stock_status() );

		$lines[] = implode( ' | ', $meta_parts );

		// Categories.
		$categories = $this->get_term_names( $product->get_category_ids(), 'product_cat' );
		if ( '' !== $categories ) {
			$lines[] = '**Categories:** ' . $categories;
		}

		// Short description.
		$short_desc = $this->convert_short_description( $product );
		if ( '' !== $short_desc ) {
			$lines[] = '> ' . str_replace( "\n", "\n> ", $short_desc );
		}

		// Featured image only.
		$image_id = (int) $product->get_image_id();
		if ( $image_id ) {
			$url = wp_get_attachment_image_url( $image_id, 'full' );
			if ( $url ) {
				$alt     = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
				$alt_str = is_string( $alt ) && '' !== $alt ? $alt : $product->get_name();
				$lines[] = '![' . $alt_str . '](' . $url . ')';
			}
		}

		// Buy and Details links.
		$lines[] = '**Buy:** [Add to cart](' . $this->get_checkout_link( $product->get_id() ) . ')';
		$lines[] = '**Details:** [View product](' . add_query_arg( 'feed', 'markdown', $product->get_permalink() ) . ')';

		return implode( "\n", $lines );
	}

	/**
	 * Check whether a product should be visible in the markdown feed.
	 *
	 * @since 10.6.0
	 *
	 * @param WC_Product $product The product to check.
	 * @return bool Whether the product is feed-visible.
	 */
	public function is_feed_visible( WC_Product $product ): bool {
		$visible = 'publish' === $product->get_status()
			&& 'hidden' !== $product->get_catalog_visibility()
			&& '' === $product->get_post_password();

		/**
		 * Filters whether a product is visible in the markdown feed.
		 *
		 * @since 10.6.0
		 *
		 * @param bool       $visible Whether the product is visible.
		 * @param WC_Product $product The product being checked.
		 */
		return (bool) apply_filters( 'woocommerce_markdown_feed_product_visible', $visible, $product );
	}

	/**
	 * Render product images as a markdown list.
	 *
	 * Includes the featured image and all gallery images with alt text.
	 *
	 * @since 10.6.0
	 *
	 * @param WC_Product $product The product whose images to render.
	 * @return string The images as a markdown list, or empty string if none.
	 */
	public function render_images( WC_Product $product ): string {
		$image_ids = array();

		$featured_id = (int) $product->get_image_id();
		if ( $featured_id ) {
			$image_ids[] = $featured_id;
		}

		$gallery_ids = $product->get_gallery_image_ids();
		if ( ! empty( $gallery_ids ) ) {
			$image_ids = array_merge( $image_ids, $gallery_ids );
		}

		if ( empty( $image_ids ) ) {
			return '';
		}

		$lines = array();
		foreach ( $image_ids as $id ) {
			$id  = (int) $id;
			$url = wp_get_attachment_image_url( $id, 'full' );
			if ( ! $url ) {
				continue;
			}

			$alt     = get_post_meta( $id, '_wp_attachment_image_alt', true );
			$alt_str = is_string( $alt ) && '' !== $alt ? $alt : 'Product image';

			$lines[] = '- ![' . $alt_str . '](' . $url . ')' . "\n" . '  Alt: ' . $alt_str;
		}

		return implode( "\n", $lines );
	}

	/**
	 * Render variations for a variable product.
	 *
	 * @since 10.6.0
	 *
	 * @param WC_Product_Variable $product The variable product.
	 * @return string The variations as markdown, or empty string if none.
	 */
	public function render_variations( WC_Product_Variable $product ): string {
		$children = $product->get_children();

		if ( empty( $children ) ) {
			return '';
		}

		/**
		 * Filters the maximum number of variations to render in the markdown feed.
		 *
		 * @since 10.6.0
		 *
		 * @param int $max_variations Maximum number of variations. Defaults to 50.
		 */
		$max_variations = (int) apply_filters( 'woocommerce_markdown_feed_max_variations', 50 );

		$children = array_slice( $children, 0, $max_variations );
		$blocks   = array();

		foreach ( $children as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation ) {
				continue;
			}

			$attributes = $variation->get_attributes();
			$attr_parts = ! empty( $attributes ) ? array_filter(
				array_values( $attributes ),
				static function ( $v ): bool {
					return '' !== (string) $v;
				}
			) : array();
			$attr_label = ! empty( $attr_parts ) ? implode( ' / ', $attr_parts ) : 'Variation #' . $child_id;

			$lines   = array();
			$lines[] = '### ' . $attr_label;

			$sku = $variation->get_sku();
			if ( '' !== $sku ) {
				$lines[] = '- **SKU:** ' . $sku;
			}

			$price = $variation->get_price();
			if ( '' !== $price && null !== $price ) {
				$lines[] = '- **Price:** ' . $this->format_price( (float) $price );
			}

			$lines[] = '- **Stock Status:** ' . $this->format_stock_status( $variation->get_stock_status() );
			$lines[] = '- **Buy:** [Add to cart](' . $this->get_checkout_link( $child_id ) . ')';

			$blocks[] = implode( "\n", $lines );
		}

		return implode( "\n\n", $blocks );
	}

	/**
	 * Generate a checkout link URL for a product.
	 *
	 * @since 10.6.0
	 *
	 * @param int $product_id The product ID.
	 * @param int $quantity   The quantity. Defaults to 1.
	 * @return string The checkout URL.
	 */
	public function get_checkout_link( int $product_id, int $quantity = 1 ): string {
		$url = add_query_arg(
			array(
				'products' => $product_id . ':' . $quantity,
			),
			home_url( '/checkout-link' )
		);

		/**
		 * Filters the checkout link URL for the markdown feed.
		 *
		 * @since 10.6.0
		 *
		 * @param string $url        The checkout URL.
		 * @param int    $product_id The product ID.
		 * @param int    $quantity   The quantity.
		 */
		return (string) apply_filters( 'woocommerce_markdown_feed_checkout_link', $url, $product_id, $quantity );
	}

	/**
	 * Render the YAML front matter block.
	 *
	 * @param WC_Product $product The product for context.
	 * @return string The front matter block including delimiters.
	 */
	private function render_front_matter( WC_Product $product ): string {
		$meta = array(
			'store'     => get_bloginfo( 'name' ),
			'currency'  => get_woocommerce_currency(),
			'generated' => current_datetime()->format( 'c' ),
			'type'      => 'single_product',
		);

		/**
		 * Filters the YAML front matter metadata array.
		 *
		 * @since 10.6.0
		 *
		 * @param array<string, string> $meta    The metadata key-value pairs.
		 * @param WC_Product            $product The product being rendered.
		 */
		$meta = apply_filters( 'woocommerce_markdown_feed_meta', $meta, $product );

		$lines = array( '---' );
		foreach ( $meta as $key => $value ) {
			$lines[] = sanitize_key( $key ) . ': ' . $this->sanitize_yaml_value( (string) $value );
		}
		$lines[] = '---';

		return implode( "\n", $lines );
	}

	/**
	 * Escape a string for safe inline markdown output.
	 *
	 * Escapes pipe characters (which break table cells) and leading hash
	 * characters (which create unintended headings).
	 *
	 * @param string $text The text to escape.
	 * @return string The escaped text.
	 */
	private function escape_markdown( string $text ): string {
		$text = str_replace( '|', '\\|', $text );
		// Escape leading '#' that could be interpreted as headings.
		$text = (string) preg_replace( '/^(#+)/m', '\\\\$1', $text );
		return $text;
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
	 * Render the product meta block (SKU, price, stock, categories, tags).
	 *
	 * @param WC_Product $product The product.
	 * @return string The meta block markdown.
	 */
	private function render_meta_block( WC_Product $product ): string {
		$lines = array();

		$sku = $product->get_sku();
		if ( '' !== $sku ) {
			$lines[] = '**SKU:** ' . $sku;
		}

		$price = $product->get_price();
		if ( '' !== $price && null !== $price ) {
			$lines[] = '**Price:** ' . $this->format_price( (float) $price );
		}

		if ( $product->is_on_sale() ) {
			$regular = $product->get_regular_price();
			if ( '' !== $regular && null !== $regular ) {
				$lines[] = '**Regular Price:** ' . $this->format_price( (float) $regular );
			}

			$sale = $product->get_sale_price();
			if ( '' !== $sale && null !== $sale ) {
				$lines[] = '**Sale Price:** ' . $this->format_price( (float) $sale );
			}
		}

		$lines[] = '**Stock Status:** ' . $this->format_stock_status( $product->get_stock_status() );

		$categories = $this->get_term_names( $product->get_category_ids(), 'product_cat' );
		if ( '' !== $categories ) {
			$lines[] = '**Categories:** ' . $categories;
		}

		$tags = $this->get_term_names( $product->get_tag_ids(), 'product_tag' );
		if ( '' !== $tags ) {
			$lines[] = '**Tags:** ' . $tags;
		}

		return implode( "\n", $lines );
	}

	/**
	 * Render the product attributes as a markdown table.
	 *
	 * @param WC_Product $product The product.
	 * @return string The attributes table markdown, or empty string if no attributes.
	 */
	private function render_attributes( WC_Product $product ): string {
		$attributes = $product->get_attributes();

		if ( empty( $attributes ) ) {
			return '';
		}

		$lines   = array();
		$lines[] = '| Attribute | Options |';
		$lines[] = '|-----------|---------|';

		foreach ( $attributes as $attribute ) {
			if ( is_a( $attribute, 'WC_Product_Attribute' ) ) {
				$name = wc_attribute_label( $attribute->get_name() );

				if ( $attribute->is_taxonomy() ) {
					$terms   = wp_get_post_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
					$options = is_array( $terms ) ? implode( ', ', $terms ) : '';
				} else {
					$options = implode( ', ', $attribute->get_options() );
				}

				$lines[] = '| ' . $this->escape_markdown( $name ) . ' | ' . $this->escape_markdown( $options ) . ' |';
			}
		}

		// Only header rows means no attributes were valid.
		if ( 2 === count( $lines ) ) {
			return '';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Convert the product full description HTML to markdown.
	 *
	 * @param WC_Product $product The product.
	 * @return string The converted description markdown, or empty string.
	 */
	private function convert_description( WC_Product $product ): string {
		$html = $product->get_description();
		if ( '' === $html ) {
			return '';
		}

		return $this->html_to_markdown->convert( $html );
	}

	/**
	 * Convert the product short description HTML to markdown.
	 *
	 * @param WC_Product $product The product.
	 * @return string The converted short description markdown, or empty string.
	 */
	private function convert_short_description( WC_Product $product ): string {
		$html = $product->get_short_description();
		if ( '' === $html ) {
			return '';
		}

		return $this->html_to_markdown->convert( $html );
	}

	/**
	 * Format a stock status value to a human-readable string.
	 *
	 * @param string $status The stock status (instock, outofstock, onbackorder).
	 * @return string The formatted stock status label.
	 */
	private function format_stock_status( string $status ): string {
		return self::STOCK_STATUS_MAP[ $status ] ?? $status;
	}

	/**
	 * Format price for inline display in summaries.
	 *
	 * Shows current price with strikethrough regular price if on sale.
	 *
	 * @param WC_Product $product The product.
	 * @return string Formatted price string, or empty string if no price.
	 */
	private function format_price_inline( WC_Product $product ): string {
		$price = $product->get_price();
		if ( '' === $price || null === $price ) {
			return '';
		}

		$formatted = $this->format_price( (float) $price );

		if ( $product->is_on_sale() ) {
			$regular = $product->get_regular_price();
			if ( '' !== $regular && null !== $regular ) {
				$formatted .= ' ~~' . $this->format_price( (float) $regular ) . '~~';
			}
		}

		return $formatted;
	}

	/**
	 * Format a WooCommerce price as plain text (no HTML, no entities).
	 *
	 * @param float $amount The price amount.
	 * @return string The formatted price string.
	 */
	private function format_price( float $amount ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Get comma-separated term names for a list of term IDs.
	 *
	 * @param int[]  $term_ids The term IDs.
	 * @param string $taxonomy The taxonomy name.
	 * @return string Comma-separated term names, or empty string.
	 */
	private function get_term_names( array $term_ids, string $taxonomy ): string {
		if ( empty( $term_ids ) ) {
			return '';
		}

		$names = array();
		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$names[] = $term->name;
			}
		}

		return implode( ', ', $names );
	}
}
